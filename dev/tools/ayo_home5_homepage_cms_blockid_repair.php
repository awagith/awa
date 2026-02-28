#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Reparo idempotente de block_id directives na CMS page da Home5.
 *
 * Cenário alvo:
 * - homepage_ayo_home5 referencia block_ids com hífen que não existem
 * - existem equivalentes válidos com underscore
 *
 * Padrão:
 * - dry-run por padrão
 * - --apply grava em cms_page.content (com backup local do conteúdo anterior)
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
 * @return array{store-code:string,homepage:?string,apply:bool,backup-dir:string,strict:bool}
 */
function parseArgs(array $argv): array
{
    $opts = [
        'store-code' => 'default',
        'homepage' => null,
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
        'homepage' => is_string($opts['homepage']) ? $opts['homepage'] : null,
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
        throw new RuntimeException('Config DB default não encontrada');
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        (string) ($db['host'] ?? '127.0.0.1'),
        (string) ($db['port'] ?? '3306'),
        (string) ($db['dbname'] ?? '')
    );

    $pdo = new PDO(
        $dsn,
        (string) ($db['username'] ?? ''),
        (string) ($db['password'] ?? ''),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    return $pdo;
}

/**
 * @return array<string,mixed>
 */
function fetchStoreByCode(PDO $pdo, string $code): array
{
    $stmt = $pdo->prepare('SELECT store_id, code FROM store WHERE code = :code LIMIT 1');
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
 * @return array<string,mixed>
 */
function fetchCmsPage(PDO $pdo, string $identifier): array
{
    $stmt = $pdo->prepare(
        'SELECT page_id, identifier, title, is_active, content
         FROM cms_page WHERE identifier = :identifier
         ORDER BY page_id DESC LIMIT 1'
    );
    $stmt->execute(['identifier' => $identifier]);
    $row = $stmt->fetch();
    if (!is_array($row)) {
        throw new RuntimeException('CMS page não encontrada: ' . $identifier);
    }
    return $row;
}

/**
 * @return array<string,bool>
 */
function fetchExistingBlockIdentifiers(PDO $pdo): array
{
    $rows = $pdo->query('SELECT identifier FROM cms_block')->fetchAll();
    $ids = [];

    foreach ($rows as $row) {
        if (!is_array($row) || !isset($row['identifier'])) {
            continue;
        }
        $ids[(string) $row['identifier']] = true;
    }

    return $ids;
}

/**
 * @return list<string>
 */
function extractBlockIdsFromCmsContent(string $content): array
{
    if ($content === '') {
        return [];
    }

    $ids = [];
    if (preg_match_all('/block_id\s*=\s*"([^"]+)"/i', $content, $matches) >= 1) {
        foreach (($matches[1] ?? []) as $id) {
            $ids[] = trim((string) $id);
        }
    }

    if (preg_match_all("/block_id\s*=\s*'([^']+)'/i", $content, $matchesSingle) >= 1) {
        foreach (($matchesSingle[1] ?? []) as $id) {
            $ids[] = trim((string) $id);
        }
    }

    return array_values(array_unique(array_filter($ids, static fn(string $v): bool => $v !== '')));
}

/**
 * @param array<string,bool> $existingBlockIds
 * @return array<string,string>
 */
function buildReplacementMap(array $existingBlockIds, array $blockIdsInPage): array
{
    $manual = [
        'home-benefits-bar' => 'home_benefits_bar',
        'home-security-seals' => 'home_security_seals',
        'trust-badges' => 'trust_badges_homepage',
        'home-faq-structured' => 'home_faq_quick',
    ];

    $map = [];
    foreach ($blockIdsInPage as $id) {
        if (isset($existingBlockIds[$id])) {
            continue;
        }

        $candidate = $manual[$id] ?? str_replace('-', '_', $id);
        if ($candidate !== $id && isset($existingBlockIds[$candidate])) {
            $map[$id] = $candidate;
        }
    }

    return $map;
}

/**
 * @param string $content
 * @param array<string,string> $replacementMap
 * @return array{content:string,replacements:array<string,int>}
 */
function replaceBlockIdsInContent(string $content, array $replacementMap): array
{
    $counts = [];
    foreach ($replacementMap as $from => $to) {
        $counts[$from] = 0;
    }

    $result = preg_replace_callback(
        '/block_id\s*=\s*(["\'])([^"\']+)\1/i',
        static function (array $match) use ($replacementMap, &$counts): string {
            $quote = (string) ($match[1] ?? '"');
            $value = (string) ($match[2] ?? '');
            if (!isset($replacementMap[$value])) {
                return (string) ($match[0] ?? '');
            }

            $counts[$value] = ($counts[$value] ?? 0) + 1;
            return 'block_id=' . $quote . $replacementMap[$value] . $quote;
        },
        $content
    );

    return [
        'content' => is_string($result) ? $result : $content,
        'replacements' => $counts,
    ];
}

/**
 * @param string $backupDir
 * @param string $pageIdentifier
 * @param string $originalContent
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
        preg_replace('/[^a-zA-Z0-9._-]+/', '_', $pageIdentifier) ?: 'homepage',
        gmdate('Ymd_His')
    );

    $written = file_put_contents($filename, $originalContent);
    if ($written === false) {
        throw new RuntimeException('Falha ao salvar backup em ' . $filename);
    }

    return $filename;
}

/**
 * @return int
 */
function updateCmsPageContent(PDO $pdo, int $pageId, string $newContent): int
{
    $stmt = $pdo->prepare(
        'UPDATE cms_page
         SET content = :content, update_time = UTC_TIMESTAMP()
         WHERE page_id = :page_id'
    );
    $stmt->execute([
        'content' => $newContent,
        'page_id' => $pageId,
    ]);

    return $stmt->rowCount();
}

try {
    $args = parseArgs($argv);
    $strict = $args['strict'];

    $pdo = pdoFromEnv(loadEnvConfig());
    $store = fetchStoreByCode($pdo, $args['store-code']);
    $storeId = (int) $store['store_id'];

    $homepageIdentifier = $args['homepage'] ?? getScopedConfigValue($pdo, 'web/default/cms_home_page', $storeId) ?? 'home';
    $page = fetchCmsPage($pdo, $homepageIdentifier);
    $pageId = (int) $page['page_id'];
    $content = (string) ($page['content'] ?? '');

    $existingBlockIds = fetchExistingBlockIdentifiers($pdo);
    $pageBlockIds = extractBlockIdsFromCmsContent($content);
    $replacementMap = buildReplacementMap($existingBlockIds, $pageBlockIds);

    $unresolved = [];
    foreach ($pageBlockIds as $id) {
        if (!isset($existingBlockIds[$id]) && !isset($replacementMap[$id])) {
            $unresolved[] = $id;
        }
    }

    $patch = replaceBlockIdsInContent($content, $replacementMap);
    $newContent = $patch['content'];
    $replacementCounts = $patch['replacements'];
    $changed = $newContent !== $content;

    out('=== AYO HOME5 HOMEPAGE CMS BLOCKID REPAIR ===');
    out('Store: ' . (string) $store['code'] . ' (ID ' . $storeId . ')');
    out('Homepage: ' . $homepageIdentifier . ' (page_id=' . $pageId . ')');
    out('Mode: ' . ($args['apply'] ? 'APPLY' : 'DRY-RUN'));
    out('CMS directives block_id refs: ' . count($pageBlockIds));

    out('');
    out('=== REPLACEMENTS DETECTADOS ===');
    if ($replacementMap === []) {
        out('Nenhuma substituição necessária.');
    } else {
        foreach ($replacementMap as $from => $to) {
            out(sprintf('%s -> %s', $from, $to));
        }
    }

    out('');
    out('=== CONTAGEM DE OCORRÊNCIAS ===');
    if ($replacementCounts === []) {
        out('Nenhuma ocorrência substituível detectada.');
    } else {
        foreach ($replacementCounts as $from => $count) {
            out(sprintf('%s: %d', $from, $count));
        }
    }

    if ($unresolved !== []) {
        out('');
        out('=== REFERÊNCIAS NÃO RESOLVIDAS ===');
        foreach ($unresolved as $id) {
            $msg = 'Sem bloco de destino para: ' . $id;
            if ($strict) {
                fail($msg);
            } else {
                warn($msg);
            }
        }
    }

    out('');
    out('=== RESUMO ===');
    out('Conteúdo original bytes: ' . strlen($content));
    out('Conteúdo novo bytes: ' . strlen($newContent));
    out('Mudança necessária: ' . ($changed ? 'SIM' : 'NÃO'));

    if (!$args['apply']) {
        if ($changed) {
            out('[OK] Dry-run concluído. Use --apply para gravar as substituições.');
        } else {
            out('[OK] Dry-run concluído. Nenhuma alteração para aplicar.');
        }
        exit(($strict && $unresolved !== []) ? 1 : 0);
    }

    if ($unresolved !== []) {
        throw new RuntimeException('Existem referências não resolvidas. Corrija ou execute sem --strict apenas em dry-run.');
    }

    if (!$changed) {
        out('[OK] Nada para aplicar; conteúdo já está consistente com o mapa de aliases.');
        exit(0);
    }

    $backupFile = writeBackup($args['backup-dir'], $homepageIdentifier, $content);
    $rowCount = updateCmsPageContent($pdo, $pageId, $newContent);

    out('[OK] CMS page atualizada com sucesso.');
    out('Backup: ' . $backupFile);
    out('Rows afetadas: ' . $rowCount);
    warn('Execute cache clean: php bin/magento cache:clean layout block_html full_page');

    exit(0);
} catch (Throwable $e) {
    fail($e->getMessage());
    exit(1);
}

