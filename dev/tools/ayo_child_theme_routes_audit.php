#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Auditoria HTML multi-rota do child theme Ayo Home5.
 *
 * Objetivo:
 * - validar rotas críticas (home, PLP, busca, carrinho, auth, B2B)
 * - confirmar presença de assets do child e marcadores por rota
 * - detectar regressão de assets/scripts legados duplicados (awa-round*)
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
 * @param array<int, string> $argv
 * @return array{base-url:string, timeout:int, insecure:bool, pdp-path:?string}
 */
function parseArgs(array $argv): array
{
    $opts = [
        'base-url' => 'https://awamotos.com/',
        'timeout' => 20,
        'insecure' => false,
        'pdp-path' => null,
    ];

    for ($i = 1, $max = count($argv); $i < $max; $i++) {
        $arg = (string) $argv[$i];

        if ($arg === '--insecure') {
            $opts['insecure'] = true;
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

        if ($key === 'timeout') {
            $opts[$key] = max(1, (int) $value);
        } elseif ($key === 'pdp-path') {
            $opts[$key] = '/' . ltrim($value, '/');
        } else {
            $opts[$key] = $value;
        }
        $i++;
    }

    $opts['base-url'] = rtrim((string) $opts['base-url'], '/') . '/';

    return [
        'base-url' => (string) $opts['base-url'],
        'timeout' => (int) $opts['timeout'],
        'insecure' => (bool) $opts['insecure'],
        'pdp-path' => is_string($opts['pdp-path']) ? (string) $opts['pdp-path'] : null,
    ];
}

/**
 * @param string $url
 * @param int $timeout
 * @param bool $insecure
 * @return array{code:int, body:string, effective_url:string}
 */
function fetchHtml(string $url, int $timeout, bool $insecure): array
{
    $ch = curl_init();
    if ($ch === false) {
        throw new RuntimeException('Falha ao iniciar cURL');
    }

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; AYOChildRoutesAudit/1.0)',
        CURLOPT_HEADER => false,
    ]);

    if ($insecure) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    $body = curl_exec($ch);
    if (!is_string($body)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('Falha ao baixar HTML: ' . $error);
    }

    $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $effectiveUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    return [
        'code' => $code,
        'body' => $body,
        'effective_url' => $effectiveUrl,
    ];
}

/**
 * @param string $html
 * @return string
 */
function extractBodyClass(string $html): string
{
    if ($html === '') {
        return '';
    }

    $previous = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = $dom->loadHTML($html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if ($loaded !== true) {
        return '';
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    if (!$body instanceof DOMElement) {
        return '';
    }

    return trim((string) $body->getAttribute('class'));
}

/**
 * @param string $bodyClass
 * @param string $token
 * @return bool
 */
function bodyClassContainsToken(string $bodyClass, string $token): bool
{
    if ($bodyClass === '' || $token === '') {
        return false;
    }

    $tokens = preg_split('/\s+/', trim($bodyClass)) ?: [];
    return in_array($token, $tokens, true);
}

/**
 * @param string $xml
 * @return list<string>
 */
function extractLocUrlsFromXml(string $xml): array
{
    if ($xml === '') {
        return [];
    }

    if (preg_match_all('~<loc>(.*?)</loc>~is', $xml, $matches) < 1) {
        return [];
    }

    $locs = [];
    foreach (($matches[1] ?? []) as $rawLoc) {
        $loc = trim(html_entity_decode((string) $rawLoc, ENT_QUOTES | ENT_XML1, 'UTF-8'));
        if ($loc === '') {
            continue;
        }
        $locs[] = $loc;
    }

    return array_values(array_unique($locs));
}

/**
 * @param string $baseUrl
 * @param string $candidateUrl
 * @return ?string
 */
function normalizeSameOriginPath(string $baseUrl, string $candidateUrl): ?string
{
    $baseParts = parse_url($baseUrl);
    $candidateParts = parse_url($candidateUrl);

    if (!is_array($baseParts) || !is_array($candidateParts)) {
        return null;
    }

    $baseScheme = strtolower((string) ($baseParts['scheme'] ?? ''));
    $baseHost = strtolower((string) ($baseParts['host'] ?? ''));
    $candidateScheme = strtolower((string) ($candidateParts['scheme'] ?? ''));
    $candidateHost = strtolower((string) ($candidateParts['host'] ?? ''));

    if ($baseHost === '' || $candidateHost === '' || $baseHost !== $candidateHost) {
        return null;
    }

    if ($baseScheme !== '' && $candidateScheme !== '' && $baseScheme !== $candidateScheme) {
        return null;
    }

    $path = (string) ($candidateParts['path'] ?? '');
    if ($path === '') {
        return null;
    }

    $normalizedPath = '/' . ltrim($path, '/');
    $query = (string) ($candidateParts['query'] ?? '');
    if ($query !== '') {
        $normalizedPath .= '?' . $query;
    }

    return $normalizedPath;
}

/**
 * @param string $path
 * @return bool
 */
function looksLikeNestedHtmlPath(string $path): bool
{
    $pathOnly = (string) parse_url($path, PHP_URL_PATH);
    $trimmed = trim($pathOnly, '/');

    if ($trimmed === '' || !str_ends_with(strtolower($trimmed), '.html')) {
        return false;
    }

    if (substr_count($trimmed, '/') < 1) {
        return false;
    }

    foreach (['/checkout/', '/customer/', '/catalogsearch/', '/search/', '/b2b/'] as $blockedPrefix) {
        if (str_starts_with($pathOnly, $blockedPrefix)) {
            return false;
        }
    }

    return true;
}

/**
 * @param string $html
 * @param string $baseUrl
 * @return list<string>
 */
function extractProductItemLinksFromHtml(string $html, string $baseUrl): array
{
    if ($html === '') {
        return [];
    }

    if (preg_match_all('~<a\b(?=[^>]*\bclass\s*=\s*"[^"]*\bproduct-item-link\b[^"]*")[^>]*\bhref\s*=\s*"([^"]+)"~i', $html, $matches) < 1) {
        return [];
    }

    $paths = [];
    foreach (($matches[1] ?? []) as $href) {
        $path = normalizeSameOriginPath($baseUrl, (string) $href);
        if ($path === null) {
            continue;
        }
        $paths[] = $path;
    }

    return array_values(array_unique($paths));
}

/**
 * @param string $baseUrl
 * @param list<string> $candidatePaths
 * @param int $timeout
 * @param bool $insecure
 * @param int $maxAttempts
 * @param bool $useHeuristic
 * @return ?string
 */
function discoverPdpFromCandidatePaths(
    string $baseUrl,
    array $candidatePaths,
    int $timeout,
    bool $insecure,
    int $maxAttempts = 20,
    bool $useHeuristic = true
): ?string {
    $attempts = 0;

    foreach ($candidatePaths as $path) {
        if ($useHeuristic && !looksLikeNestedHtmlPath($path)) {
            continue;
        }

        if ($attempts >= $maxAttempts) {
            break;
        }

        $attempts++;
        $url = rtrim($baseUrl, '/') . $path;
        $resp = fetchHtml($url, $timeout, $insecure);

        if ($resp['code'] !== 200) {
            continue;
        }

        $bodyClass = extractBodyClass($resp['body']);
        if (bodyClassContainsToken($bodyClass, 'catalog-product-view')) {
            out('[INFO] PDP auto-descoberta após ' . $attempts . ' tentativa(s): ' . $path);
            return $path;
        }

        if (
            str_contains($resp['effective_url'], '/b2b/account/login/') &&
            bodyClassContainsToken($bodyClass, 'b2b-auth-shell')
        ) {
            out('[INFO] PDP auto-descoberta (gated via B2B) após ' . $attempts . ' tentativa(s): ' . $path);
            return $path;
        }
    }

    return null;
}

/**
 * @param string $baseUrl
 * @param int $timeout
 * @param bool $insecure
 * @return ?string
 */
function discoverPdpPath(string $baseUrl, int $timeout, bool $insecure): ?string
{
    $sitemapUrl = rtrim($baseUrl, '/') . '/sitemap.xml';
    out('[INFO] Tentando auto-descobrir PDP via sitemap: ' . $sitemapUrl);

    $sitemapResp = fetchHtml($sitemapUrl, $timeout, $insecure);
    if ($sitemapResp['code'] === 200) {
        $locUrls = extractLocUrlsFromXml($sitemapResp['body']);
        $candidatePaths = [];

        foreach ($locUrls as $locUrl) {
            $path = normalizeSameOriginPath($baseUrl, $locUrl);
            if ($path === null) {
                continue;
            }
            $candidatePaths[] = $path;
        }

        $candidatePaths = array_values(array_unique($candidatePaths));
        $found = discoverPdpFromCandidatePaths($baseUrl, $candidatePaths, $timeout, $insecure, 30, true);
        if ($found !== null) {
            return $found;
        }
    } elseif ($sitemapResp['code'] !== 403) {
        warn('Sitemap retornou HTTP ' . $sitemapResp['code'] . '; fallback para PLP será tentado.');
    } else {
        warn('Sitemap retornou 403 (WAF). Fallback para PLP será tentado.');
    }

    $fallbackPlpPath = '/retrovisores.html';
    out('[INFO] Tentando auto-descobrir PDP via links da PLP: ' . $fallbackPlpPath);
    $plpResp = fetchHtml(rtrim($baseUrl, '/') . $fallbackPlpPath, $timeout, $insecure);
    if ($plpResp['code'] !== 200) {
        warn('PLP fallback retornou HTTP ' . $plpResp['code'] . '; PDP auto não será validada.');
        return null;
    }

    $productItemPaths = extractProductItemLinksFromHtml($plpResp['body'], $baseUrl);
    if ($productItemPaths === []) {
        warn('Nenhum link de produto (product-item-link) encontrado na PLP fallback.');
        return null;
    }

    return discoverPdpFromCandidatePaths($baseUrl, $productItemPaths, $timeout, $insecure, 15, false);
}

/**
 * @return array<int, array{
 *   key:string,
 *   label:string,
 *   path:string,
 *   body_class_contains?:list<string>,
 *   must_contain:array<string,string>
 * }>
 */
function buildRoutes(?string $pdpPath): array
{
    $routes = [
        [
            'key' => 'home',
            'label' => 'Home',
            'path' => '/',
            'body_class_contains' => ['cms-index-index'],
            'must_contain' => [
                'aw-footer-highlights' => 'Rodapé melhorado esperado (aw-footer-highlights) ausente na home',
            ],
        ],
        [
            'key' => 'plp_retrovisores',
            'label' => 'Categoria (retrovisores)',
            'path' => '/retrovisores.html',
            'body_class_contains' => ['catalog-category-view'],
            'must_contain' => [],
        ],
        [
            'key' => 'search_result',
            'label' => 'Busca (catalogsearch)',
            'path' => '/catalogsearch/result/?q=capacete',
            'body_class_contains' => ['catalogsearch-result-index'],
            'must_contain' => [],
        ],
        [
            'key' => 'cart',
            'label' => 'Carrinho',
            'path' => '/checkout/cart/',
            'body_class_contains' => ['checkout-cart-index'],
            'must_contain' => [],
        ],
        [
            'key' => 'customer_login_alias_b2b',
            'label' => 'Login customer/account (B2B shell)',
            'path' => '/customer/account/login/',
            'body_class_contains' => ['b2b-auth-shell'],
            'must_contain' => [
                'b2b-login-page' => 'Layout B2B custom (b2b-login-page) ausente em customer/account/login',
            ],
        ],
        [
            'key' => 'b2b_login',
            'label' => 'Login B2B',
            'path' => '/b2b/account/login/',
            'body_class_contains' => ['b2b-auth-shell'],
            'must_contain' => [
                'b2b-login-page' => 'Layout B2B custom (b2b-login-page) ausente em /b2b/account/login/',
            ],
        ],
    ];

    if ($pdpPath !== null) {
        $routes[] = [
            'key' => 'pdp',
            'label' => 'PDP',
            'path' => $pdpPath,
            'must_contain' => [],
        ];
    }

    return $routes;
}

/**
 * @return array<string,string>
 */
function globalMustContainChecks(): array
{
    return [
        'frontend/AWA_Custom/ayo_home5_child/' => 'HTML não contém assets do child theme AWA_Custom/ayo_home5_child',
    ];
}

/**
 * @return array<string,string>
 */
function globalMustNotContainChecks(): array
{
    return [
        "require(['jquery', 'js/awa-search-autocomplete-compat']" => 'require() inline legado do search compat ainda presente',
        'js/awa-search-autocomplete-compat": {' => 'x-magento-init local duplicado do search compat ainda presente',
        'awa-round2-header-minicart-ui.js' => 'Asset legado duplicado awa-round2-header-minicart-ui.js ainda presente',
        'awa-round2-home-owl-tabs-ui.js' => 'Asset legado duplicado awa-round2-home-owl-tabs-ui.js ainda presente',
        'awa-round3-footer-ux.js' => 'Asset legado duplicado awa-round3-footer-ux.js ainda presente',
        'awa-round3-pdp-sticky-cta.js' => 'Asset legado duplicado awa-round3-pdp-sticky-cta.js ainda presente',
        'css/awa-round2-brand-bridge.css' => 'CSS legado duplicado awa-round2-brand-bridge.css ainda presente',
        'css/awa-round2-header-minicart.css' => 'CSS legado duplicado awa-round2-header-minicart.css ainda presente',
        'css/awa-round3-footer-trust.css' => 'CSS legado duplicado awa-round3-footer-trust.css ainda presente',
        'css/awa-round4-global-brand.css' => 'CSS legado duplicado awa-round4-global-brand.css ainda presente',
        'css/awa-round5-ux-refine.css' => 'CSS legado duplicado awa-round5-ux-refine.css ainda presente',
        'css/awa-round6-b2b-gate-refine.css' => 'CSS legado duplicado awa-round6-b2b-gate-refine.css ainda presente',
        'css/awa-round7-auth-refine.css' => 'CSS legado duplicado awa-round7-auth-refine.css ainda presente',
        'css/awa-round8-plp-search-cart-refine.css' => 'CSS legado duplicado awa-round8-plp-search-cart-refine.css ainda presente',
        'js/awa-b2b-cart-checkout-compat' => 'Referência ao JS legado awa-b2b-cart-checkout-compat ainda presente',
        'js/awa-home-category-compat' => 'Referência ao JS legado awa-home-category-compat ainda presente',
    ];
}

/**
 * @param string $html
 * @param array<string,string> $checks
 * @return list<string>
 */
function applyContainsChecks(string $html, array $checks): array
{
    $failures = [];

    foreach ($checks as $needle => $message) {
        if (!str_contains($html, $needle)) {
            $failures[] = $message;
        }
    }

    return $failures;
}

/**
 * @param string $html
 * @param array<string,string> $checks
 * @return list<string>
 */
function applyNotContainsChecks(string $html, array $checks): array
{
    $failures = [];

    foreach ($checks as $needle => $message) {
        if (str_contains($html, $needle)) {
            $failures[] = $message;
        }
    }

    return $failures;
}

/**
 * @param string $bodyClass
 * @param list<string> $tokens
 * @param string $routeLabel
 * @return list<string>
 */
function applyBodyClassChecks(string $bodyClass, array $tokens, string $routeLabel): array
{
    $failures = [];

    foreach ($tokens as $token) {
        if (!bodyClassContainsToken($bodyClass, $token)) {
            $failures[] = 'Body class da rota (' . $routeLabel . ') não contém "' . $token . '"';
        }
    }

    return $failures;
}

/**
 * @param string $html
 * @param string $routeLabel
 * @return list<string>
 */
function conditionalChecks(
    string $html,
    string $routeKey,
    string $routeLabel,
    string $bodyClass,
    string $effectiveUrl
): array
{
    $failures = [];
    $isHomeDemoCms = $routeKey === 'home'
        && (bodyClassContainsToken($bodyClass, 'cms-homepage_ayo_home5_demo_stage')
            || str_contains($html, 'banner-slider2')
            || str_contains($html, 'top-home-content'));

    if (
        !$isHomeDemoCms &&
        (str_contains($html, 'id="search_mini_form"') || str_contains($html, 'form minisearch')) &&
        !str_contains($html, 'awaCustomCompatBootstrap')
    ) {
        $failures[] = 'Bootstrap compat do child ausente apesar de mini search estar presente';
    }

    if (str_contains($html, 'id="choose_category"') && !str_contains($html, 'js/awa-search-category-chosen')) {
        $failures[] = 'x-magento-init do select de categoria (chosen) ausente apesar de #choose_category estar presente';
    }

    if (str_contains($html, 'id="b2b-login-form"') && !str_contains($html, 'GrupoAwamotos_B2B/js/auth-form')) {
        $failures[] = 'x-magento-init do formulário B2B (GrupoAwamotos_B2B/js/auth-form) ausente';
    }

    if ($routeKey === 'pdp') {
        $isDirectPdp = bodyClassContainsToken($bodyClass, 'catalog-product-view');
        $isB2bGate = bodyClassContainsToken($bodyClass, 'b2b-auth-shell')
            && str_contains($effectiveUrl, '/b2b/account/login/')
            && str_contains($html, 'b2b-login-page');

        if (!$isDirectPdp && !$isB2bGate) {
            $failures[] = 'PDP não renderizou como catalog-product-view nem redirecionou para shell B2B esperado';
        }
    }

    if ($routeLabel === 'Home' && !$isHomeDemoCms && substr_count($html, 'awaCustomCompatBootstrap') !== 1) {
        $failures[] = 'Bootstrap compat do child deveria aparecer exatamente 1 vez na home';
    }

    if ($routeLabel === 'Home' && $isHomeDemoCms) {
        if (!str_contains($html, 'banner-slider2') && !str_contains($html, 'top-home-content')) {
            $failures[] = 'Home em modo demo CMS não exibiu markers esperados (banner-slider2/top-home-content)';
        }
        if (str_contains($html, 'newsletter_pop_up') || str_contains($html, 'newsletter-validate-popup')) {
            $failures[] = 'Newsletter popup não deveria renderizar na home demo CMS';
        }
    }

    return $failures;
}

try {
    $args = parseArgs($argv);
    $baseUrl = $args['base-url'];
    $timeout = $args['timeout'];
    $insecure = $args['insecure'];
    $pdpPath = $args['pdp-path'];

    if ($pdpPath === null) {
        $pdpPath = discoverPdpPath($baseUrl, $timeout, $insecure);
        if ($pdpPath === null) {
            warn('PDP não foi auto-descoberta; auditoria seguirá sem rota de PDP.');
        }
    } else {
        out('[INFO] PDP definida manualmente via --pdp-path: ' . $pdpPath);
    }

    $routes = buildRoutes($pdpPath);

    out('=== AYO CHILD THEME ROUTES AUDIT ===');
    out('Base URL: ' . $baseUrl);
    out('Rotas: ' . (string) count($routes));

    $globalMustContain = globalMustContainChecks();
    $globalMustNotContain = globalMustNotContainChecks();

    $allFailures = [];
    $totalWarnings = 0;
    $executedRoutes = 0;
    $skippedRoutes = 0;

    foreach ($routes as $route) {
        $routeUrl = rtrim($baseUrl, '/') . $route['path'];
        out('');
        out('-> ' . $route['label'] . ': ' . $route['path']);

        $resp = fetchHtml($routeUrl, $timeout, $insecure);
        $code = $resp['code'];
        $html = $resp['body'];
        $effectiveUrl = $resp['effective_url'];
        $bodyClass = '';

        out('   HTTP: ' . $code . ($effectiveUrl !== '' && $effectiveUrl !== $routeUrl ? ' (final: ' . $effectiveUrl . ')' : ''));

        if ($code === 403) {
            warn($route['label'] . ' retornou 403 (WAF/anti-bot). Rota ignorada sem falha.');
            $totalWarnings++;
            $skippedRoutes++;
            continue;
        }

        if ($code !== 200) {
            $allFailures[] = $route['label'] . ': HTTP inesperado ' . $code . ' (esperado 200 ou 403/WAF)';
            continue;
        }

        $executedRoutes++;
        $bodyClass = extractBodyClass($html);

        $routeFailures = [];
        $routeFailures = array_merge($routeFailures, applyContainsChecks($html, $globalMustContain));
        $routeFailures = array_merge($routeFailures, applyNotContainsChecks($html, $globalMustNotContain));
        if (isset($route['body_class_contains']) && is_array($route['body_class_contains'])) {
            /** @var list<string> $bodyClassTokens */
            $bodyClassTokens = array_values(array_filter($route['body_class_contains'], 'is_string'));
            $routeFailures = array_merge($routeFailures, applyBodyClassChecks($bodyClass, $bodyClassTokens, $route['label']));
        }
        $routeFailures = array_merge($routeFailures, applyContainsChecks($html, $route['must_contain']));
        $routeFailures = array_merge(
            $routeFailures,
            conditionalChecks($html, $route['key'], $route['label'], $bodyClass, $effectiveUrl)
        );

        if ($routeFailures === []) {
            out('   [OK] Checks da rota passaram');
            continue;
        }

        foreach ($routeFailures as $message) {
            $allFailures[] = $route['label'] . ': ' . $message;
        }
    }

    out('');
    out('=== RESUMO ===');
    out('Rotas validadas: ' . $executedRoutes);
    out('Rotas ignoradas (403/WAF): ' . $skippedRoutes);
    out('Warnings: ' . $totalWarnings);

    if ($allFailures !== []) {
        out('Falhas: ' . count($allFailures));
        out('');
        out('=== FALHAS ===');
        foreach ($allFailures as $failureMessage) {
            fail($failureMessage);
        }
        exit(1);
    }

    if ($executedRoutes === 0) {
        warn('Nenhuma rota foi validada (todas retornaram 403/WAF).');
        exit(0);
    }

    out('[OK] Auditoria multi-rota do child theme concluída sem falhas');
    exit(0);
} catch (Throwable $e) {
    fail($e->getMessage());
    exit(1);
}
