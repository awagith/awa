#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Auditoria HTML da home para child theme Ayo Home5.
 *
 * Objetivo:
 * - confirmar bootstraps/aliases do child no HTML final
 * - detectar regressão de duplicidade (assets/script inline antigos awa-round*)
 */

function out(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function fail(string $message): void
{
    fwrite(STDERR, '[FAIL] ' . $message . PHP_EOL);
}

function extractBodyClass(string $html): string
{
    if (preg_match('/<body\b[^>]*class="([^"]*)"/i', $html, $m) === 1) {
        return trim((string) ($m[1] ?? ''));
    }
    return '';
}

function bodyClassContainsToken(string $bodyClass, string $token): bool
{
    if ($bodyClass === '' || $token === '') {
        return false;
    }
    $tokens = preg_split('/\s+/', trim($bodyClass)) ?: [];
    return in_array($token, array_values(array_filter($tokens, 'is_string')), true);
}

/**
 * @return array{url:string, timeout:int, insecure:bool}
 */
function parseArgs(array $argv): array
{
    $opts = [
        'url' => 'https://awamotos.com/',
        'timeout' => 20,
        'insecure' => false,
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
        } else {
            $opts[$key] = $value;
        }
        $i++;
    }

    $opts['url'] = rtrim((string) $opts['url'], '/') . '/';

    return [
        'url' => (string) $opts['url'],
        'timeout' => (int) $opts['timeout'],
        'insecure' => (bool) $opts['insecure'],
    ];
}

/**
 * @return array{code:int, body:string}
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
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; AYOChildHtmlAudit/1.0)',
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
    curl_close($ch);

    return ['code' => $code, 'body' => $body];
}

try {
    $args = parseArgs($argv);
    out('=== AYO CHILD HTML AUDIT ===');
    out('URL: ' . $args['url']);

    $resp = fetchHtml($args['url'], $args['timeout'], $args['insecure']);
    $code = $resp['code'];
    $html = $resp['body'];

    if ($code === 403) {
        out('[WARN] Home retornou 403 (WAF/anti-bot). Auditoria HTML pulada sem falha.');
        exit(0);
    }

    if ($code !== 200) {
        fail('Home não retornou HTTP 200. Código: ' . $code);
        exit(1);
    }

    $failures = [];
    $bodyClass = extractBodyClass($html);
    $isDemoCmsHome = bodyClassContainsToken($bodyClass, 'cms-homepage_ayo_home5_demo_stage')
        || str_contains($html, 'banner-slider2')
        || str_contains($html, 'top-home-content');

    $mustContain = [
        'frontend/AWA_Custom/ayo_home5_child/' => 'HTML não contém assets do child theme AWA_Custom/ayo_home5_child',
    ];
    if (!$isDemoCmsHome) {
        $mustContain['awaCustomCompatBootstrap'] = 'Bootstrap compat do child (x-magento-init) ausente';
    }

    foreach ($mustContain as $needle => $message) {
        if (!str_contains($html, $needle)) {
            $failures[] = $message;
        }
    }

    if (str_contains($html, 'id="choose_category"') && !str_contains($html, 'js/awa-search-category-chosen')) {
        $failures[] = 'x-magento-init do select de categoria (chosen) ausente no HTML, apesar de #choose_category estar presente';
    }

    if (!$isDemoCmsHome && substr_count($html, 'awaCustomCompatBootstrap') !== 1) {
        $failures[] = 'Bootstrap compat do child deveria aparecer exatamente 1 vez no HTML da home';
    }

    if ($isDemoCmsHome && !str_contains($html, 'banner-slider2') && !str_contains($html, 'top-home-content')) {
        $failures[] = 'Home em modo demo CMS sem markers esperados (banner-slider2/top-home-content)';
    }
    if ($isDemoCmsHome && (str_contains($html, 'newsletter_pop_up') || str_contains($html, 'newsletter-validate-popup'))) {
        $failures[] = 'Newsletter popup não deveria renderizar na home demo CMS';
    }

    $mustNotContain = [
        "require(['jquery', 'js/awa-search-autocomplete-compat']" => 'require() inline legado do search compat ainda presente no HTML',
        'js/awa-search-autocomplete-compat": {' => 'x-magento-init local duplicado do search compat ainda presente no HTML',
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

    foreach ($mustNotContain as $needle => $message) {
        if (str_contains($html, $needle)) {
            $failures[] = $message;
        }
    }

    if ($failures !== []) {
        out('');
        out('=== FALHAS ===');
        foreach ($failures as $failure) {
            fail($failure);
        }
        exit(1);
    }

    out('[OK] HTML final da home está coerente com o child theme (sem duplicidade legada detectada)');
    exit(0);
} catch (Throwable $e) {
    fail($e->getMessage());
    exit(1);
}
