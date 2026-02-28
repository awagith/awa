#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Sincroniza (cria/atualiza) uma CMS page stage para comparação visual da Home5.
 *
 * Objetivo:
 * - criar `homepage_ayo_home5_stage` (ou outro identifier informado)
 * - clonar metadata relevante da homepage atual (source)
 * - usar baseline CMS-driven (scripts/home_content.html) como conteúdo
 * - operação idempotente com dry-run por padrão e --apply para gravar
 */

/**
 * @param string $message
 */
function out(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

/**
 * @param string $message
 */
function warn(string $message): void
{
    fwrite(STDOUT, '[WARN] ' . $message . PHP_EOL);
}

/**
 * @param string $message
 */
function fail(string $message): void
{
    fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL);
}

/**
 * @return string
 */
function rootDir(): string
{
    return dirname(__DIR__, 2);
}

/**
 * @param array<int,string> $argv
 * @return array{
 *   store-code:string,
 *   source-homepage:?string,
 *   stage-identifier:string,
 *   content-file:string,
 *   title:?string,
 *   apply:bool,
 *   backup-dir:string,
 *   strict:bool
 * }
 */
function parseArgs(array $argv): array
{
    $opts = [
        'store-code' => 'default',
        'source-homepage' => null,
        'stage-identifier' => 'homepage_ayo_home5_stage',
        'content-file' => rootDir() . '/scripts/home_content.html',
        'title' => null,
        'apply' => false,
        'backup-dir' => rootDir() . '/var/tmp/ayo-homepage-cms-backups',
        'strict' => false,
    ];

    for ($i = 1, $max = count($argv); $i < $max; $i++) {
        $arg = (string) $argv[$i];

        if ($arg === '--apply') {
            $opts['apply'] = true;
            continue;
        }
        if ($arg === '--strict') {
            $opts['strict'] = true;
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

        if ($key === 'backup-dir') {
            $opts[$key] = rtrim($value, '/');
        } else {
            $opts[$key] = $value;
        }
        $i++;
    }

    return [
        'store-code' => (string) $opts['store-code'],
        'source-homepage' => is_string($opts['source-homepage']) ? $opts['source-homepage'] : null,
        'stage-identifier' => (string) $opts['stage-identifier'],
        'content-file' => (string) $opts['content-file'],
        'title' => is_string($opts['title']) ? $opts['title'] : null,
        'apply' => (bool) $opts['apply'],
        'backup-dir' => (string) $opts['backup-dir'],
        'strict' => (bool) $opts['strict'],
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
 * @return PDO
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
 * @return ?string
 */
function getScopedConfigValue(PDO $pdo, string $path, int $storeId): ?string
{
    $stmt = $pdo->prepare(
        'SELECT value FROM core_config_data
         WHERE path = :path AND ((scope = "stores" AND scope_id = :sid) OR (scope = "default" AND scope_id = 0))
         ORDER BY (scope = "stores") DESC
         LIMIT 1'
    );
    $stmt->execute(['path' => $path, 'sid' => $storeId]);
    $value = $stmt->fetchColumn();
    return is_string($value) ? $value : null;
}

/**
 * @return list<array<string,mixed>>
 */
function fetchCmsPagesByIdentifier(PDO $pdo, string $identifier): array
{
    $stmt = $pdo->prepare(
        'SELECT *
         FROM cms_page
         WHERE identifier = :identifier
         ORDER BY page_id DESC'
    );
    $stmt->execute(['identifier' => $identifier]);

    $rows = $stmt->fetchAll();
    $result = [];
    foreach ($rows as $row) {
        if (is_array($row)) {
            $result[] = $row;
        }
    }

    return $result;
}

/**
 * @return array<string,mixed>
 */
function fetchCmsPageByIdentifier(PDO $pdo, string $identifier): array
{
    $pages = fetchCmsPagesByIdentifier($pdo, $identifier);
    if ($pages === []) {
        throw new RuntimeException('CMS page não encontrada: ' . $identifier);
    }
    return $pages[0];
}

/**
 * @return list<int>
 */
function fetchCmsPageStoreIds(PDO $pdo, int $pageId): array
{
    $stmt = $pdo->prepare('SELECT store_id FROM cms_page_store WHERE page_id = :page_id ORDER BY store_id ASC');
    $stmt->execute(['page_id' => $pageId]);

    $ids = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $value) {
        $ids[] = (int) $value;
    }

    return array_values(array_unique($ids));
}

/**
 * @return string
 */
function readContentFile(string $path): string
{
    if (!is_file($path)) {
        throw new RuntimeException('Arquivo de conteúdo não encontrado: ' . $path);
    }
    $content = file_get_contents($path);
    if (!is_string($content)) {
        throw new RuntimeException('Falha ao ler arquivo de conteúdo: ' . $path);
    }
    return $content;
}

/**
 * @param mixed $value
 * @return string
 */
function truncateForLog($value, int $max = 90): string
{
    $str = (is_scalar($value) || $value === null) ? (string) ($value ?? 'NULL') : '[complex]';
    if (strlen($str) <= $max) {
        return $str;
    }

    return substr($str, 0, max(0, $max - 3)) . '...';
}

/**
 * @return list<string>
 */
function editableCmsPageFields(): array
{
    return [
        'title',
        'page_layout',
        'meta_keywords',
        'meta_description',
        'identifier',
        'content_heading',
        'content',
        'is_active',
        'sort_order',
        'layout_update_xml',
        'custom_theme',
        'custom_root_template',
        'custom_layout_update_xml',
        'layout_update_selected',
        'custom_theme_from',
        'custom_theme_to',
        'meta_title',
    ];
}

/**
 * @return array<string,mixed>
 */
function buildStagePayload(array $sourcePage, string $stageIdentifier, string $title, string $content): array
{
    $payload = [];
    foreach (editableCmsPageFields() as $field) {
        $payload[$field] = $sourcePage[$field] ?? null;
    }

    $payload['identifier'] = $stageIdentifier;
    $payload['title'] = $title;
    $payload['content'] = $content;
    $payload['is_active'] = 1;

    return $payload;
}

/**
 * @return array<string,array{from:mixed,to:mixed}>
 */
function diffCmsPagePayload(array $payload, ?array $existingPage): array
{
    if ($existingPage === null) {
        $diff = [];
        foreach ($payload as $field => $value) {
            $diff[(string) $field] = ['from' => null, 'to' => $value];
        }
        return $diff;
    }

    $diff = [];
    foreach ($payload as $field => $value) {
        $current = $existingPage[$field] ?? null;
        if ((string) $field === 'is_active' || (string) $field === 'sort_order') {
            $same = (int) $current === (int) $value;
        } else {
            $same = (string) ($current ?? '') === (string) ($value ?? '');
        }

        if (!$same) {
            $diff[(string) $field] = ['from' => $current, 'to' => $value];
        }
    }

    return $diff;
}

/**
 * @param list<int> $sourceStores
 * @param list<int> $stageStores
 * @return bool
 */
function storeAssignmentsDiffer(array $sourceStores, array $stageStores): bool
{
    sort($sourceStores);
    sort($stageStores);
    return $sourceStores !== $stageStores;
}

/**
 * @return string
 */
function writeBackup(string $backupDir, string $pageIdentifier, string $originalContent): string
{
    if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
        throw new RuntimeException('Não foi possível criar backup dir: ' . $backupDir);
    }

    $filename = sprintf(
        '%s/%s_%s.html',
        $backupDir,
        preg_replace('/[^a-zA-Z0-9._-]+/', '_', $pageIdentifier) ?: 'cms-page',
        gmdate('Ymd_His')
    );

    $written = file_put_contents($filename, (string) $originalContent);
    if ($written === false) {
        throw new RuntimeException('Falha ao salvar backup em ' . $filename);
    }

    return $filename;
}

/**
 * @param array<string,mixed> $payload
 * @return int
 */
function insertCmsPage(PDO $pdo, array $payload): int
{
    $fields = editableCmsPageFields();
    $columns = implode(', ', $fields);
    $placeholders = implode(', ', array_map(static fn(string $f): string => ':' . $f, $fields));

    $sql = sprintf(
        'INSERT INTO cms_page (%s, creation_time, update_time)
         VALUES (%s, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
        $columns,
        $placeholders
    );

    $stmt = $pdo->prepare($sql);
    $params = [];
    foreach ($fields as $field) {
        $params[$field] = $payload[$field] ?? null;
    }
    $stmt->execute($params);

    return (int) $pdo->lastInsertId();
}

/**
 * @param array<string,mixed> $payload
 * @return int
 */
function updateCmsPage(PDO $pdo, int $pageId, array $payload): int
{
    $fields = editableCmsPageFields();
    $assignments = implode(
        ', ',
        array_map(static fn(string $f): string => $f . ' = :' . $f, $fields)
    );

    $sql = sprintf(
        'UPDATE cms_page
         SET %s, update_time = UTC_TIMESTAMP()
         WHERE page_id = :page_id',
        $assignments
    );

    $stmt = $pdo->prepare($sql);
    $params = [];
    foreach ($fields as $field) {
        $params[$field] = $payload[$field] ?? null;
    }
    $params['page_id'] = $pageId;
    $stmt->execute($params);

    return $stmt->rowCount();
}

/**
 * @param list<int> $storeIds
 */
function replaceCmsPageStores(PDO $pdo, int $pageId, array $storeIds): void
{
    $delete = $pdo->prepare('DELETE FROM cms_page_store WHERE page_id = :page_id');
    $delete->execute(['page_id' => $pageId]);

    $insert = $pdo->prepare('INSERT INTO cms_page_store (page_id, store_id) VALUES (:page_id, :store_id)');
    foreach ($storeIds as $storeId) {
        $insert->execute([
            'page_id' => $pageId,
            'store_id' => (int) $storeId,
        ]);
    }
}

try {
    $args = parseArgs($argv);
    $strict = $args['strict'];

    $pdo = pdoFromEnv(loadEnvConfig());
    $store = fetchStoreByCode($pdo, $args['store-code']);
    $storeId = (int) $store['store_id'];

    $sourceIdentifier = $args['source-homepage']
        ?? getScopedConfigValue($pdo, 'web/default/cms_home_page', $storeId)
        ?? 'home';
    $cmsPageUrlSuffix = getScopedConfigValue($pdo, 'web/default/cms_page_url_suffix', $storeId) ?? '';
    $stageIdentifier = $args['stage-identifier'];
    $baselineContent = readContentFile($args['content-file']);

    $sourcePages = fetchCmsPagesByIdentifier($pdo, $sourceIdentifier);
    if ($sourcePages === []) {
        throw new RuntimeException('Homepage source não encontrada: ' . $sourceIdentifier);
    }
    $sourcePage = $sourcePages[0];
    if (count($sourcePages) > 1) {
        $msg = sprintf(
            'Existem %d CMS pages com identifier "%s"; usando page_id=%d (mais recente).',
            count($sourcePages),
            $sourceIdentifier,
            (int) $sourcePage['page_id']
        );
        if ($strict) {
            throw new RuntimeException($msg);
        }
        warn($msg);
    }

    $stagePages = fetchCmsPagesByIdentifier($pdo, $stageIdentifier);
    $stagePage = $stagePages[0] ?? null;
    if (count($stagePages) > 1) {
        $msg = sprintf(
            'Existem %d CMS pages com identifier "%s"; a mais recente será atualizada.',
            count($stagePages),
            $stageIdentifier
        );
        if ($strict) {
            throw new RuntimeException($msg);
        }
        warn($msg);
    }

    $sourceStores = fetchCmsPageStoreIds($pdo, (int) $sourcePage['page_id']);
    if ($sourceStores === []) {
        $sourceStores = [0];
        warn('Homepage source sem vínculos em cms_page_store; usando fallback store_id [0].');
    }

    $stageStores = $stagePage !== null ? fetchCmsPageStoreIds($pdo, (int) $stagePage['page_id']) : [];

    $stageTitle = $args['title'];
    if ($stageTitle === null || $stageTitle === '') {
        $sourceTitle = trim((string) ($sourcePage['title'] ?? 'Homepage Ayo Home 5'));
        $stageTitle = $sourceTitle . ' (Stage CMS)';
    }

    $payload = buildStagePayload($sourcePage, $stageIdentifier, $stageTitle, $baselineContent);
    $fieldDiff = diffCmsPagePayload($payload, $stagePage);
    $storeDiff = storeAssignmentsDiffer($sourceStores, $stageStores);

    out('=== AYO HOME5 STAGE PAGE SYNC ===');
    out('Store: ' . (string) $store['code'] . ' (ID ' . $storeId . ')');
    out('Mode: ' . ($args['apply'] ? 'APPLY' : 'DRY-RUN'));
    out('Source homepage: ' . $sourceIdentifier . ' (page_id=' . (int) $sourcePage['page_id'] . ')');
    out('Stage identifier: ' . $stageIdentifier . ($stagePage ? ' (page_id=' . (int) $stagePage['page_id'] . ')' : ' (novo)'));
    out('Baseline content file: ' . $args['content-file'] . ' (' . strlen($baselineContent) . ' bytes)');

    out('');
    out('=== STORES (cms_page_store) ===');
    out('Source stores: [' . implode(', ', array_map('strval', $sourceStores)) . ']');
    out('Stage stores : [' . implode(', ', array_map('strval', $stageStores)) . ']');
    out('Store assignments changed: ' . ($storeDiff ? 'SIM' : 'NÃO'));

    out('');
    out('=== FIELDS CHANGED ===');
    if ($fieldDiff === []) {
        out('Nenhum campo difere entre payload desejado e page stage atual.');
    } else {
        foreach ($fieldDiff as $field => $diff) {
            $from = $diff['from'];
            $to = $diff['to'];
            if ($field === 'content') {
                out(sprintf('%s: %d bytes -> %d bytes', $field, strlen((string) $from), strlen((string) $to)));
                continue;
            }

            $fromStr = truncateForLog($from, 90);
            $toStr = truncateForLog($to, 90);
            out(sprintf('%s: "%s" -> "%s"', $field, $fromStr, $toStr));
        }
    }

    $needsWrite = ($fieldDiff !== []) || $storeDiff || ($stagePage === null);

    out('');
    out('=== PREVIEW URLS (estimado) ===');
    out('Config web/default/cms_page_url_suffix: "' . $cmsPageUrlSuffix . '"');
    out('Provável: /' . $stageIdentifier . $cmsPageUrlSuffix);
    if ($cmsPageUrlSuffix !== '') {
        out('Alternativa sem sufixo (se rewrite/customização divergir): /' . $stageIdentifier);
    }

    out('');
    out('=== RESUMO ===');
    out('Mudança necessária: ' . ($needsWrite ? 'SIM' : 'NÃO'));

    if (!$args['apply']) {
        out('Dry-run: nenhuma alteração foi gravada.');
        exit(0);
    }

    if (!$needsWrite) {
        out('Stage page já está sincronizada. Nada a aplicar.');
        exit(0);
    }

    $pdo->beginTransaction();
    $backupPath = null;

    try {
        if ($stagePage !== null) {
            $backupPath = writeBackup($args['backup-dir'], $stageIdentifier, (string) ($stagePage['content'] ?? ''));
            updateCmsPage($pdo, (int) $stagePage['page_id'], $payload);
            $stagePageId = (int) $stagePage['page_id'];
        } else {
            $stagePageId = insertCmsPage($pdo, $payload);
        }

        replaceCmsPageStores($pdo, $stagePageId, $sourceStores);
        $pdo->commit();

        if ($backupPath !== null) {
            out('Backup content anterior: ' . $backupPath);
        }
        out('Stage page sincronizada com sucesso. page_id=' . $stagePageId);
        out('Lembrete: limpe cache block_html/full_page se quiser refletir imediatamente no storefront.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
} catch (Throwable $e) {
    fail($e->getMessage());
    exit(1);
}
