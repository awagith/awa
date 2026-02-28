#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Auditoria de alinhamento da homepage Ayo Home5 (CMS + layout + blocos).
 *
 * Objetivo:
 * - explicar por que a home pode divergir do demo/documentação
 * - detectar se a home é dirigida por CMS page ou pelo template top-home do tema
 * - validar blocos esperados e referências quebradas na CMS page
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
 * @return array{store-code:string, strict:bool}
 */
function parseArgs(array $argv): array
{
    $opts = [
        'store-code' => 'default',
        'strict' => false,
    ];

    for ($i = 1, $max = count($argv); $i < $max; $i++) {
        $arg = (string) $argv[$i];

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

        $opts[$key] = $value;
        $i++;
    }

    return [
        'store-code' => (string) $opts['store-code'],
        'strict' => (bool) $opts['strict'],
    ];
}

/**
 * @return array<string,mixed>
 */
function loadEnvConfig(): array
{
    $env = rootDir() . '/app/etc/env.php';
    if (!is_file($env)) {
        throw new RuntimeException('env.php não encontrado: ' . $env);
    }

    /** @var array<string,mixed> $cfg */
    $cfg = require $env;
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

    $host = (string) ($db['host'] ?? '127.0.0.1');
    $dbname = (string) ($db['dbname'] ?? '');
    $user = (string) ($db['username'] ?? '');
    $pass = (string) ($db['password'] ?? '');
    $port = isset($db['port']) ? (string) $db['port'] : '3306';

    if ($dbname === '' || $user === '') {
        throw new RuntimeException('Credenciais DB inválidas em env.php');
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbname);
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

/**
 * @return array<string,mixed>
 */
function fetchStoreByCode(PDO $pdo, string $code): array
{
    $row = $pdo->prepare('SELECT store_id, code, website_id, group_id, is_active FROM store WHERE code = :code LIMIT 1');
    $row->execute(['code' => $code]);
    $data = $row->fetch();
    if (!is_array($data)) {
        throw new RuntimeException('Store não encontrada: ' . $code);
    }
    return $data;
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
 * @return array<string,mixed>|null
 */
function fetchThemeById(PDO $pdo, int $themeId): ?array
{
    $stmt = $pdo->prepare('SELECT theme_id, theme_path, parent_id, theme_title, area FROM theme WHERE theme_id = :id LIMIT 1');
    $stmt->execute(['id' => $themeId]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

/**
 * @return array<string,mixed>|null
 */
function fetchCmsPage(PDO $pdo, string $identifier): ?array
{
    $stmt = $pdo->prepare(
        'SELECT page_id, identifier, title, is_active, page_layout, custom_theme, custom_root_template, content
         FROM cms_page
         WHERE identifier = :identifier
         ORDER BY page_id DESC
         LIMIT 1'
    );
    $stmt->execute(['identifier' => $identifier]);
    $row = $stmt->fetch();
    return is_array($row) ? $row : null;
}

/**
 * @return array<string,array{block_id:int|string,identifier:string,title:string,is_active:int|string,content_len:int|string}>
 */
function fetchCmsBlocksByIdentifiers(PDO $pdo, array $identifiers): array
{
    if ($identifiers === []) {
        return [];
    }

    $identifiers = array_values(array_unique(array_filter(array_map('strval', $identifiers), static fn(string $v): bool => $v !== '')));
    if ($identifiers === []) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($identifiers), '?'));
    $sql = sprintf(
        'SELECT block_id, identifier, title, is_active, LENGTH(content) AS content_len
         FROM cms_block WHERE identifier IN (%s)',
        $placeholders
    );
    $stmt = $pdo->prepare($sql);
    $stmt->execute($identifiers);

    $rows = [];
    foreach ($stmt->fetchAll() as $row) {
        if (!is_array($row) || !isset($row['identifier'])) {
            continue;
        }
        $rows[(string) $row['identifier']] = $row;
    }

    return $rows;
}

/**
 * @return list<string>
 */
function extractCmsBlockIdsFromDirectiveContent(string $content): array
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

    return array_values(array_unique(array_filter($ids, static fn(string $v): bool => $v !== '')));
}

/**
 * @return list<string>
 */
function extractSetBlockIdsFromTemplate(string $phpTemplate): array
{
    if ($phpTemplate === '') {
        return [];
    }

    $ids = [];
    if (preg_match_all('/setBlockId\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $phpTemplate, $matches) >= 1) {
        foreach (($matches[1] ?? []) as $id) {
            $ids[] = trim((string) $id);
        }
    }

    return array_values(array_unique(array_filter($ids, static fn(string $v): bool => $v !== '')));
}

/**
 * @return list<string>
 */
function extractRenderCmsBlockCallsFromTemplate(string $phpTemplate): array
{
    if ($phpTemplate === '') {
        return [];
    }

    $ids = [];
    if (preg_match_all('/renderCmsBlock\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $phpTemplate, $matches) >= 1) {
        foreach (($matches[1] ?? []) as $id) {
            $ids[] = trim((string) $id);
        }
    }

    return array_values(array_unique(array_filter($ids, static fn(string $v): bool => $v !== '')));
}

/**
 * @return bool
 */
function fileContains(string $path, string $needle): bool
{
    if (!is_file($path)) {
        return false;
    }

    $content = file_get_contents($path);
    return is_string($content) && str_contains($content, $needle);
}

/**
 * @return string
 */
function readFileOrEmpty(string $path): string
{
    if (!is_file($path)) {
        return '';
    }
    $content = file_get_contents($path);
    return is_string($content) ? $content : '';
}

/**
 * @return ?array{exists:bool,identifier:string,suggestion:string}
 */
function suggestBlockIdAlias(string $missingId, array $existingBlocksById): ?array
{
    if ($missingId === '') {
        return null;
    }

    $candidates = [];

    if (str_contains($missingId, '-')) {
        $candidates[] = str_replace('-', '_', $missingId);
    }
    if (str_contains($missingId, '_')) {
        $candidates[] = str_replace('_', '-', $missingId);
    }

    // Heurísticas locais conhecidas (Ayo/AWA)
    $map = [
        'trust-badges' => 'trust_badges_homepage',
        'home-benefits-bar' => 'home_benefits_bar',
        'home-security-seals' => 'home_security_seals',
        'home-faq-structured' => 'home_faq_quick',
    ];
    if (isset($map[$missingId])) {
        array_unshift($candidates, $map[$missingId]);
    }

    foreach (array_values(array_unique($candidates)) as $candidate) {
        if (isset($existingBlocksById[$candidate])) {
            return [
                'exists' => true,
                'identifier' => $candidate,
                'suggestion' => sprintf('Substituir "%s" por "%s"', $missingId, $candidate),
            ];
        }
    }

    return null;
}

/**
 * @return bool
 */
function fetchHomeHtmlHasMarker(string $baseUrl, string $marker): bool
{
    $ch = curl_init();
    if ($ch === false) {
        return false;
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => rtrim($baseUrl, '/') . '/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; AYOHomeAlignmentAudit/1.0)',
        CURLOPT_HEADER => false,
    ]);

    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if (!is_string($body) || ($code !== 200 && $code !== 403)) {
        return false;
    }

    return str_contains($body, $marker);
}

try {
    $args = parseArgs($argv);
    $strictMode = $args['strict'];
    $root = rootDir();
    $pdo = pdoFromEnv(loadEnvConfig());

    $store = fetchStoreByCode($pdo, $args['store-code']);
    $storeId = (int) $store['store_id'];

    $homeIdentifier = getScopedConfigValue($pdo, 'web/default/cms_home_page', $storeId) ?? 'home';
    $themeIdValue = getScopedConfigValue($pdo, 'design/theme/theme_id', $storeId);
    $themeId = is_string($themeIdValue) && ctype_digit($themeIdValue) ? (int) $themeIdValue : 0;
    $theme = $themeId > 0 ? fetchThemeById($pdo, $themeId) : null;

    $page = fetchCmsPage($pdo, $homeIdentifier);
    if ($page === null) {
        throw new RuntimeException('Página CMS da home não encontrada: ' . $homeIdentifier);
    }

    $homepageContent = (string) ($page['content'] ?? '');
    $homepageBlockRefs = extractCmsBlockIdsFromDirectiveContent($homepageContent);

    $allBlockIdsToCheck = $homepageBlockRefs;

    $childTopHomeTemplatePath = $root . '/app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Cms/templates/top-home.phtml';
    $baseTopHomeTemplatePath = $root . '/app/design/frontend/ayo/ayo_home5/Magento_Cms/templates/top-home.phtml';
    $topHomeTemplatePath = is_file($childTopHomeTemplatePath) ? $childTopHomeTemplatePath : $baseTopHomeTemplatePath;
    $topHomeTemplateContent = readFileOrEmpty($topHomeTemplatePath);
    $topHomeExpectedBlockIds = array_values(array_unique(array_merge(
        extractSetBlockIdsFromTemplate($topHomeTemplateContent),
        extractRenderCmsBlockCallsFromTemplate($topHomeTemplateContent)
    )));
    $allBlockIdsToCheck = array_values(array_unique(array_merge($allBlockIdsToCheck, $topHomeExpectedBlockIds)));

    $awaHomeBaselinePath = $root . '/scripts/home_content.html';
    $awaHomeBaselineContent = readFileOrEmpty($awaHomeBaselinePath);
    $awaBaselineBlockIds = extractCmsBlockIdsFromDirectiveContent($awaHomeBaselineContent);
    $allBlockIdsToCheck = array_values(array_unique(array_merge($allBlockIdsToCheck, $awaBaselineBlockIds)));

    $cmsBlocks = fetchCmsBlocksByIdentifiers($pdo, $allBlockIdsToCheck);

    $childCmsIndexLayout = $root . '/app/design/frontend/AWA_Custom/ayo_home5_child/Magento_Cms/layout/cms_index_index.xml';
    $baseCmsIndexLayout = $root . '/app/design/frontend/ayo/ayo_home5/Magento_Cms/layout/cms_index_index.xml';
    $parentAyoDefaultCmsIndexLayout = $root . '/app/design/frontend/ayo/ayo_default/Magento_Cms/layout/cms_index_index.xml';

    $childRemovesCmsPageContent = fileContains($childCmsIndexLayout, 'name="cms_page_content" remove="true"');
    $childRemovesContentTopHome = fileContains($childCmsIndexLayout, 'name="content-top-home" remove="true"');
    $baseRemovesCmsPageContent = fileContains($baseCmsIndexLayout, 'name="cms_page_content" remove="true"');
    $parentInjectsTopHome = fileContains($parentAyoDefaultCmsIndexLayout, 'template="Magento_Cms::top-home.phtml"');
    $isCmsDrivenHome = !$childRemovesCmsPageContent && $childRemovesContentTopHome;
    $isTemplateDrivenHome = $childRemovesCmsPageContent && $parentInjectsTopHome;

    $renderedHomeHasTopHome = fetchHomeHtmlHasMarker('https://awamotos.com/', 'top-home-content--above-fold');
    $renderedHomeHasAwaCmsWrapper = fetchHomeHtmlHasMarker('https://awamotos.com/', 'ayo-home5-wrapper');
    $renderedHomeHasTemplateWrapper = fetchHomeHtmlHasMarker('https://awamotos.com/', 'ayo-home5-wrapper--template-driven');
    $renderedHomeHasDemoCmsMarkers = fetchHomeHtmlHasMarker('https://awamotos.com/', 'banner-slider2')
        || fetchHomeHtmlHasMarker('https://awamotos.com/', 'top-home-content');

    out('=== AYO HOME5 HOMEPAGE ALIGNMENT AUDIT ===');
    out('Store: ' . (string) $store['code'] . ' (ID ' . $storeId . ')');
    out('Homepage (config): ' . $homeIdentifier);
    out('Theme (store effective): ' . ($theme !== null ? ((string) ($theme['theme_path'] ?? 'unknown') . ' [ID ' . (string) ($theme['theme_id'] ?? '?') . ']') : 'N/D'));

    out('');
    out('=== MODO DE RENDERIZAÇÃO (HOME) ===');
    out('Child remove cms_page_content: ' . ($childRemovesCmsPageContent ? 'SIM' : 'NÃO'));
    out('Child remove content-top-home: ' . ($childRemovesContentTopHome ? 'SIM' : 'NÃO'));
    out('Base remove cms_page_content: ' . ($baseRemovesCmsPageContent ? 'SIM' : 'NÃO'));
    out('Parent ayo_default injeta top-home.phtml: ' . ($parentInjectsTopHome ? 'SIM' : 'NÃO'));
    out('Home HTML contém marker top-home-content--above-fold: ' . ($renderedHomeHasTopHome ? 'SIM' : 'NÃO'));
    out('Home HTML contém marker ayo-home5-wrapper (wrapper visual): ' . ($renderedHomeHasAwaCmsWrapper ? 'SIM' : 'NÃO'));
    out('Home HTML contém marker ayo-home5-wrapper--template-driven: ' . ($renderedHomeHasTemplateWrapper ? 'SIM' : 'NÃO'));
    out('Home HTML contém markers demo CMS (banner-slider2/top-home-content): ' . ($renderedHomeHasDemoCmsMarkers ? 'SIM' : 'NÃO'));

    if ($isTemplateDrivenHome) {
        warn('A homepage está em modo "template-driven" (top-home.phtml). O conteúdo da CMS page pode não ser renderizado diretamente.');
        if ($renderedHomeHasTemplateWrapper) {
            out('[OK] top-home.phtml já renderiza wrapper compatível (ayo-home5-wrapper--template-driven).');
        }
    } elseif ($isCmsDrivenHome) {
        out('[OK] Homepage em modo "CMS-driven" (cms_page_content ativo, content-top-home removido).');
    }

    out('');
    out('=== PÁGINA CMS ATIVA ===');
    out('ID: ' . (string) $page['page_id']);
    out('Título: ' . (string) $page['title']);
    out('Ativa: ' . ((string) $page['is_active'] === '1' ? 'SIM' : 'NÃO'));
    out('Page layout: ' . ((string) ($page['page_layout'] ?? '') !== '' ? (string) $page['page_layout'] : '(vazio)'));
    out('Content length: ' . (string) strlen($homepageContent) . ' bytes');
    out('CMS directives block_id refs: ' . (string) count($homepageBlockRefs));
    out('Contém ayo-home5-wrapper: ' . (str_contains($homepageContent, 'ayo-home5-wrapper') ? 'SIM' : 'NÃO'));

    out('');
    out('=== BLOCOS REFERENCIADOS PELA CMS PAGE ===');
    if ($homepageBlockRefs === []) {
        warn('Nenhum block_id encontrado no conteúdo da CMS page');
    } else {
        foreach ($homepageBlockRefs as $blockId) {
            $row = $cmsBlocks[$blockId] ?? null;
            if ($row === null) {
                $msg = sprintf('CMS page referencia bloco inexistente: %s', $blockId);
                if ($strictMode) {
                    fail($msg);
                } else {
                    warn($msg);
                }
                $suggest = suggestBlockIdAlias($blockId, $cmsBlocks);
                if ($suggest !== null) {
                    warn($suggest['suggestion']);
                }
                continue;
            }

            $isActive = (string) ($row['is_active'] ?? '0') === '1';
            out(sprintf(
                'OK %-30s | %s | ativo=%s | len=%s',
                $blockId,
                (string) ($row['title'] ?? '(sem título)'),
                $isActive ? '1' : '0',
                (string) ($row['content_len'] ?? '0')
            ));
        }
    }

    out('');
    out('=== BLOCOS ESPERADOS PELO TEMPLATE AYO (top-home.phtml) ===');
    out('Template auditado: ' . str_replace($root . '/', '', $topHomeTemplatePath));
    if ($topHomeExpectedBlockIds === []) {
        warn('Não foi possível extrair setBlockId() do template top-home.phtml');
    } else {
        foreach ($topHomeExpectedBlockIds as $blockId) {
            $row = $cmsBlocks[$blockId] ?? null;
            if ($row === null) {
                $msg = sprintf('Template top-home.phtml espera bloco ausente: %s', $blockId);
                if ($strictMode) {
                    fail($msg);
                } else {
                    warn($msg);
                }
                continue;
            }

            $isActive = (string) ($row['is_active'] ?? '0') === '1';
            $len = (int) ($row['content_len'] ?? 0);
            if (!$isActive) {
                $msg = sprintf('Bloco esperado pelo top-home está inativo: %s', $blockId);
                if ($strictMode) {
                    fail($msg);
                } else {
                    warn($msg);
                }
                continue;
            }

            if ($len === 0) {
                warn(sprintf('Bloco esperado pelo top-home está vazio: %s', $blockId));
                continue;
            }

            out(sprintf('OK %-30s | len=%d', $blockId, $len));
        }
    }

    out('');
    out('=== BASELINE AWA CMS (scripts/home_content.html) ===');
    if ($awaHomeBaselineContent === '') {
        warn('Baseline local scripts/home_content.html não encontrado');
    } else {
        out('Arquivo baseline encontrado: scripts/home_content.html');
        out('Baseline contém ayo-home5-wrapper: ' . (str_contains($awaHomeBaselineContent, 'ayo-home5-wrapper') ? 'SIM' : 'NÃO'));
        out('Blocos referenciados no baseline: ' . (string) count($awaBaselineBlockIds));
        foreach ($awaBaselineBlockIds as $blockId) {
            $row = $cmsBlocks[$blockId] ?? null;
            if ($row === null) {
                $msg = sprintf('Baseline AWA referencia bloco ausente: %s', $blockId);
                if ($strictMode) {
                    fail($msg);
                } else {
                    warn($msg);
                }
                continue;
            }
            out(sprintf('OK %-30s | ativo=%s', $blockId, ((string) ($row['is_active'] ?? '0') === '1' ? '1' : '0')));
        }
    }

    $issues = [];

    // Problemas que explicam desalinhamento com o template.
    foreach ($homepageBlockRefs as $blockId) {
        if (!isset($cmsBlocks[$blockId])) {
            $issues[] = 'CMS page referencia bloco inexistente: ' . $blockId;
        }
    }
    if (!$isCmsDrivenHome && !str_contains($homepageContent, 'ayo-home5-wrapper')) {
        $issues[] = 'CMS page ativa não contém wrapper "ayo-home5-wrapper" (baseline AWA Home5)';
    }
    if ($isTemplateDrivenHome) {
        $issues[] = 'Layout da home remove cms_page_content e usa top-home.phtml; alterar somente a CMS page pode não alinhar visual';
    }
    if ($isCmsDrivenHome && !$renderedHomeHasDemoCmsMarkers) {
        $issues[] = 'Homepage em CMS-driven, mas HTML final não exibiu markers esperados da demo CMS (banner-slider2/top-home-content)';
    }

    out('');
    out('=== CONCLUSÃO ===');
    if ($issues === []) {
        out('[OK] Nenhuma divergência crítica de alinhamento detectada pelo auditor.');
        exit(0);
    }

    foreach ($issues as $issue) {
        warn($issue);
    }

    out('');
    out('Recomendação de alinhamento (sem aplicar mudanças):');
    if ($isCmsDrivenHome) {
        out('1. Validar visualmente a homepage demo CMS em desktop/mobile (ordem de seções, slider, tabs, blogs, testimonials).');
        out('2. Ajustar apenas o conteúdo CMS/blocos/widgets do demo (evitar reintroduzir top-home.phtml custom).');
        out('3. Manter compat scripts da home desativados enquanto monitora estabilidade/performance.');
        out('4. Corrigir/normalizar block_ids inválidos se surgirem novos aliases.');
    } else {
        out('1. Definir se a home seguirá modo "template-driven" (top-home.phtml) ou "CMS-driven" (ayo-home5-wrapper).');
        out('2. Se mantiver template-driven: alinhar blocos esperados pelo top-home.phtml e ignorar CMS page como fonte principal de layout.');
        out('3. Se migrar para CMS-driven: parar de remover cms_page_content em cms_index_index.xml e restaurar baseline com ayo-home5-wrapper + blocos válidos.');
        out('4. Corrigir block_ids inexistentes na CMS page (ex.: hífen vs underscore).');
    }

    if ($args['strict']) {
        exit(1);
    }

    exit(0);
} catch (Throwable $e) {
    fail($e->getMessage());
    exit(1);
}
