#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Cutover/rollback da homepage Home5 demo (config web/default/cms_home_page).
 *
 * - Faz backup JSON do estado atual (config + render mode) antes do apply
 * - Permite rollback para o último backup (ou arquivo informado)
 * - Opcionalmente sincroniza render mode (cms/template) e limpa cache
 */

function out(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function warn(string $message): void
{
    fwrite(STDOUT, '[WARN] ' . $message . PHP_EOL);
}

function fail(string $message): void
{
    fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL);
}

function rootDir(): string
{
    return dirname(__DIR__, 2);
}

/**
 * @param array<int,string> $argv
 * @return array{
 *   command:string,
 *   store-code:string,
 *   target-homepage:string,
 *   backup-dir:string,
 *   backup-file:?string,
 *   sync-render-mode:bool,
 *   cache-clean:bool
 * }
 */
function parseArgs(array $argv): array
{
    $command = $argv[1] ?? 'status';
    $allowed = ['status', 'apply', 'rollback'];
    if (!in_array($command, $allowed, true)) {
        throw new InvalidArgumentException(
            'Uso: ayo_home5_homepage_cutover.php [status|apply|rollback] [--store-code <code>] '
            . '[--target-homepage <identifier>] [--backup-dir <path>] [--backup-file <path>] '
            . '[--sync-render-mode] [--cache-clean]'
        );
    }

    $opts = [
        'store-code' => 'default',
        'target-homepage' => 'homepage_ayo_home5_demo_stage',
        'backup-dir' => rootDir() . '/var/tmp/ayo-home5-homepage-cutover-backups',
        'backup-file' => null,
        'sync-render-mode' => false,
        'cache-clean' => false,
    ];

    for ($i = 2, $max = count($argv); $i < $max; $i++) {
        $arg = (string) $argv[$i];
        if ($arg === '--sync-render-mode') {
            $opts['sync-render-mode'] = true;
            continue;
        }
        if ($arg === '--cache-clean') {
            $opts['cache-clean'] = true;
            continue;
        }
        if (!str_starts_with($arg, '--')) {
            throw new InvalidArgumentException('Opção inválida: ' . $arg);
        }

        $key = substr($arg, 2);
        if (!array_key_exists($key, $opts)) {
            throw new InvalidArgumentException('Opção não suportada: --' . $key);
        }

        $value = $argv[$i + 1] ?? null;
        if (!is_string($value) || $value === '') {
            throw new InvalidArgumentException('Valor ausente para --' . $key);
        }

        $opts[$key] = $key === 'backup-dir' ? rtrim($value, '/') : $value;
        $i++;
    }

    return [
        'command' => $command,
        'store-code' => (string) $opts['store-code'],
        'target-homepage' => (string) $opts['target-homepage'],
        'backup-dir' => (string) $opts['backup-dir'],
        'backup-file' => is_string($opts['backup-file']) ? $opts['backup-file'] : null,
        'sync-render-mode' => (bool) $opts['sync-render-mode'],
        'cache-clean' => (bool) $opts['cache-clean'],
    ];
}

/**
 * @return array<string,mixed>
 */
function loadEnvConfig(): array
{
    $path = rootDir() . '/app/etc/env.php';
    if (!is_file($path)) {
        throw new RuntimeException('env.php não encontrado: ' . $path);
    }

    /** @var array<string,mixed> $cfg */
    $cfg = require $path;
    return $cfg;
}

/**
 * @param array<string,mixed> $env
 */
function pdoFromEnv(array $env): PDO
{
    $db = $env['db']['connection']['default'] ?? null;
    if (!is_array($db)) {
        throw new RuntimeException('Config DB default não encontrada em env.php');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        (string) ($db['host'] ?? '127.0.0.1'),
        (string) ($db['port'] ?? '3306'),
        (string) ($db['dbname'] ?? '')
    );

    return new PDO(
        $dsn,
        (string) ($db['username'] ?? ''),
        (string) ($db['password'] ?? ''),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
}

/**
 * @return array<string,mixed>
 */
function fetchStoreByCode(PDO $pdo, string $code): array
{
    $stmt = $pdo->prepare('SELECT store_id, code, is_active FROM store WHERE code = :code LIMIT 1');
    $stmt->execute(['code' => $code]);
    $row = $stmt->fetch();
    if (!is_array($row)) {
        throw new RuntimeException('Store não encontrada: ' . $code);
    }
    return $row;
}

/**
 * @return ?array<string,mixed>
 */
function fetchExactConfigRow(PDO $pdo, string $scope, int $scopeId, string $path): ?array
{
    $stmt = $pdo->prepare(
        'SELECT config_id, scope, scope_id, path, value
         FROM core_config_data
         WHERE scope = :scope AND scope_id = :scope_id AND path = :path
         LIMIT 1'
    );
    $stmt->execute([
        'scope' => $scope,
        'scope_id' => $scopeId,
        'path' => $path,
    ]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

function getEffectiveConfigValue(PDO $pdo, string $path, int $storeId): ?string
{
    $stmt = $pdo->prepare(
        'SELECT value
         FROM core_config_data
         WHERE path = :path
           AND ((scope = "stores" AND scope_id = :sid) OR (scope = "default" AND scope_id = 0))
         ORDER BY (scope = "stores") DESC
         LIMIT 1'
    );
    $stmt->execute(['path' => $path, 'sid' => $storeId]);
    $value = $stmt->fetchColumn();
    return is_string($value) ? $value : null;
}

function setStoreConfigValue(PDO $pdo, int $storeId, string $path, string $value): void
{
    $row = fetchExactConfigRow($pdo, 'stores', $storeId, $path);
    if ($row === null) {
        $stmt = $pdo->prepare(
            'INSERT INTO core_config_data (scope, scope_id, path, value)
             VALUES ("stores", :scope_id, :path, :value)'
        );
        $stmt->execute([
            'scope_id' => $storeId,
            'path' => $path,
            'value' => $value,
        ]);
        return;
    }

    $stmt = $pdo->prepare('UPDATE core_config_data SET value = :value WHERE config_id = :config_id');
    $stmt->execute([
        'value' => $value,
        'config_id' => (int) $row['config_id'],
    ]);
}

function restoreStoreConfigValue(PDO $pdo, int $storeId, string $path, ?string $storeScopedValue): void
{
    $row = fetchExactConfigRow($pdo, 'stores', $storeId, $path);
    if ($storeScopedValue === null) {
        if ($row !== null) {
            $stmt = $pdo->prepare('DELETE FROM core_config_data WHERE config_id = :config_id');
            $stmt->execute(['config_id' => (int) $row['config_id']]);
        }
        return;
    }

    setStoreConfigValue($pdo, $storeId, $path, $storeScopedValue);
}

function detectRenderMode(): string
{
    $file = rootDir() . '/app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Cms/layout/cms_index_index.xml';
    if (!is_file($file)) {
        return 'unknown';
    }

    $xml = file_get_contents($file);
    if (!is_string($xml) || $xml === '') {
        return 'unknown';
    }

    $cmsPageContentRemove = null;
    $contentTopHomeRemove = null;

    if (preg_match('/<referenceBlock\s+name="cms_page_content"\s+remove="(true|false)"\s*\/>/i', $xml, $m) === 1) {
        $cmsPageContentRemove = strtolower((string) $m[1]) === 'true';
    }
    if (preg_match('/<referenceContainer\s+name="content-top-home"\s+remove="(true|false)"\s*\/>/i', $xml, $m) === 1) {
        $contentTopHomeRemove = strtolower((string) $m[1]) === 'true';
    }

    if ($cmsPageContentRemove === true && $contentTopHomeRemove === false) {
        return 'template';
    }
    if ($cmsPageContentRemove === false && $contentTopHomeRemove === true) {
        return 'cms';
    }

    return 'unknown';
}

/**
 * @param array<string,mixed> $payload
 */
function writeBackupJson(string $backupDir, array $payload): string
{
    if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
        throw new RuntimeException('Falha ao criar backup dir: ' . $backupDir);
    }

    $filename = sprintf(
        '%s/cutover_%s_%s.json',
        $backupDir,
        preg_replace('/[^a-zA-Z0-9._-]+/', '_', (string) ($payload['store_code'] ?? 'store')) ?: 'store',
        gmdate('Ymd_His') . '_' . substr(bin2hex(random_bytes(2)), 0, 4)
    );

    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
        throw new RuntimeException('Falha ao serializar backup JSON');
    }

    if (file_put_contents($filename, $json . PHP_EOL) === false) {
        throw new RuntimeException('Falha ao gravar backup: ' . $filename);
    }

    return $filename;
}

function findLatestBackupFile(string $backupDir): string
{
    $files = glob(rtrim($backupDir, '/') . '/cutover_*.json');
    if (!is_array($files) || $files === []) {
        throw new RuntimeException('Nenhum backup encontrado em ' . $backupDir);
    }
    usort($files, static fn(string $a, string $b): int => strcmp($b, $a));
    return $files[0];
}

/**
 * @return array<string,mixed>
 */
function readBackupJson(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException('Backup não encontrado: ' . $path);
    }
    $json = file_get_contents($path);
    if (!is_string($json) || $json === '') {
        throw new RuntimeException('Falha ao ler backup: ' . $path);
    }
    $data = json_decode($json, true);
    if (!is_array($data)) {
        throw new RuntimeException('Backup JSON inválido: ' . $path);
    }
    return $data;
}

function runLocalCommand(string $command): void
{
    $cwd = rootDir();
    $full = 'cd ' . escapeshellarg($cwd) . ' && ' . $command;
    out('> ' . $command);
    passthru($full, $exitCode);
    if ($exitCode !== 0) {
        throw new RuntimeException('Comando falhou (exit=' . $exitCode . '): ' . $command);
    }
}

try {
    $args = parseArgs($argv);
    $pdo = pdoFromEnv(loadEnvConfig());
    $store = fetchStoreByCode($pdo, $args['store-code']);
    $storeId = (int) $store['store_id'];
    $path = 'web/default/cms_home_page';
    $exactRow = fetchExactConfigRow($pdo, 'stores', $storeId, $path);
    $effectiveValue = getEffectiveConfigValue($pdo, $path, $storeId);
    $renderMode = detectRenderMode();

    out('=== AYO HOME5 HOMEPAGE CUTOVER ===');
    out('Store: ' . (string) $store['code'] . ' (ID ' . $storeId . ')');
    out('Command: ' . $args['command']);
    out('Target homepage: ' . $args['target-homepage']);
    out('Current effective homepage: ' . ($effectiveValue ?? '[NULL]'));
    out('Current store-scoped row: ' . ($exactRow ? ((string) $exactRow['value']) : '[none]'));
    out('Current render mode: ' . $renderMode);

    if ($args['command'] === 'status') {
        exit(0);
    }

    if ($args['command'] === 'apply') {
        if ($effectiveValue === $args['target-homepage'] && $renderMode === 'cms') {
            out('Homepage já aponta para o target e render mode já está em cms.');
            if ($args['cache-clean']) {
                runLocalCommand('php bin/magento cache:clean config layout block_html full_page');
            }
            exit(0);
        }

        $backupPayload = [
            'created_at_utc' => gmdate('c'),
            'store_code' => (string) $store['code'],
            'store_id' => $storeId,
            'path' => $path,
            'previous_effective_homepage' => $effectiveValue,
            'previous_store_value' => $exactRow['value'] ?? null,
            'target_homepage' => $args['target-homepage'],
            'render_mode_before' => $renderMode,
        ];

        $pdo->beginTransaction();
        try {
            setStoreConfigValue($pdo, $storeId, $path, $args['target-homepage']);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        $backupPath = writeBackupJson($args['backup-dir'], $backupPayload);
        out('Backup JSON: ' . $backupPath);

        if ($args['sync-render-mode']) {
            runLocalCommand('php ./dev/tools/ayo_home5_render_mode_switch.php cms');
        }
        if ($args['cache-clean']) {
            runLocalCommand('php bin/magento cache:clean config layout block_html full_page');
        }

        out('Cutover APPLY concluído.');
        exit(0);
    }

    $backupFile = $args['backup-file'] ?? findLatestBackupFile($args['backup-dir']);
    $backup = readBackupJson($backupFile);
    out('Backup selecionado: ' . $backupFile);

    $backupStoreCode = (string) ($backup['store_code'] ?? '');
    if ($backupStoreCode !== '' && $backupStoreCode !== (string) $store['code']) {
        throw new RuntimeException(
            sprintf('Backup pertence ao store "%s", mas o comando está em "%s".', $backupStoreCode, (string) $store['code'])
        );
    }

    $restoreStoreValue = array_key_exists('previous_store_value', $backup) && is_string($backup['previous_store_value'])
        ? (string) $backup['previous_store_value']
        : null;
    if (array_key_exists('previous_store_value', $backup) && $backup['previous_store_value'] === null) {
        $restoreStoreValue = null;
    }

    $pdo->beginTransaction();
    try {
        restoreStoreConfigValue($pdo, $storeId, $path, $restoreStoreValue);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    if ($args['sync-render-mode']) {
        $modeBefore = (string) ($backup['render_mode_before'] ?? 'template');
        if (!in_array($modeBefore, ['template', 'cms'], true)) {
            $modeBefore = 'template';
        }
        runLocalCommand('php ./dev/tools/ayo_home5_render_mode_switch.php ' . $modeBefore);
    }
    if ($args['cache-clean']) {
        runLocalCommand('php bin/magento cache:clean config layout block_html full_page');
    }

    out('Cutover ROLLBACK concluído.');
    exit(0);
} catch (Throwable $e) {
    fail($e->getMessage());
    exit(1);
}
