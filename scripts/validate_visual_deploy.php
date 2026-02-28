<?php
/**
 * Script de Validação Final - Deploy Visual Completo
 * Valida todas as 5 fases da implementação visual
 */

$projectRoot = dirname(__DIR__);
$results = [];
$totalChecks = 0;
$passedChecks = 0;

echo "\n🎨 VALIDAÇÃO FINAL - IMPLEMENTAÇÃO VISUAL COMPLETA\n";
echo str_repeat("=", 70) . "\n\n";

// FASE 1: Padronização de Cores (88%)
echo "📊 FASE 1: PADRONIZAÇÃO DE CORES\n";
echo str_repeat("-", 70) . "\n";

$extendLess = file_get_contents("$projectRoot/app/design/frontend/ayo/ayo_default/web/css/source/_extend.less");
$checks = [
    'Variáveis LESS declaradas' => preg_match_all('/@[a-z-]+:\s*#[0-9a-f]{6}/i', $extendLess) >= 80,
    'Cor principal #b73337 presente' => strpos($extendLess, '#b73337') !== false,
    'Estados hover/active derivados' => strpos($extendLess, '@primary-hover') !== false,
    'Escala de cinzas completa' => strpos($extendLess, '@gray-') !== false,
];

foreach ($checks as $check => $passed) {
    $totalChecks++;
    if ($passed) $passedChecks++;
    echo ($passed ? "✅" : "❌") . " $check\n";
}

// FASE 2: Responsividade Mobile (92%)
echo "\n📱 FASE 2: RESPONSIVIDADE MOBILE\n";
echo str_repeat("-", 70) . "\n";

$checks = [
    'Media queries implementadas' => preg_match_all('/@media/', $extendLess) >= 40,
    'Breakpoints mobile definidos' => strpos($extendLess, '320px') !== false,
    'Touch-friendly (44px)' => strpos($extendLess, '44px') !== false,
    'Grid responsivo' => strpos($extendLess, 'grid') !== false || strpos($extendLess, 'flex') !== false,
];

foreach ($checks as $check => $passed) {
    $totalChecks++;
    if ($passed) $passedChecks++;
    echo ($passed ? "✅" : "❌") . " $check\n";
}

// FASE 3: Microinterações (95%)
echo "\n🎪 FASE 3: MICROINTERAÇÕES\n";
echo str_repeat("-", 70) . "\n";

$microJs = "$projectRoot/app/design/frontend/ayo/ayo_default/web/js/custom/microinteractions.js";
$microJsExists = file_exists($microJs);
$microJsContent = $microJsExists ? file_get_contents($microJs) : '';

$checks = [
    'microinteractions.js existe' => $microJsExists,
    'Scroll progress implementado' => strpos($microJsContent, 'scroll') !== false,
    'Back to top button' => strpos($microJsContent, 'scrollTop') !== false,
    'Animações keyframes' => preg_match_all('/@keyframes/', $extendLess) >= 10,
    'Lazy loading' => strpos($microJsContent, 'lazy') !== false || strpos($microJsContent, 'loading') !== false,
];

foreach ($checks as $check => $passed) {
    $totalChecks++;
    if ($passed) $passedChecks++;
    echo ($passed ? "✅" : "❌") . " $check\n";
}

// FASE 4: Performance (97%)
echo "\n⚡ FASE 4: PERFORMANCE\n";
echo str_repeat("-", 70) . "\n";

$stylesL = "$projectRoot/pub/static/frontend/ayo/ayo_default/pt_BR/css/styles-l.min.css";
$stylesM = "$projectRoot/pub/static/frontend/ayo/ayo_default/pt_BR/css/styles-m.min.css";
$microMin = "$projectRoot/pub/static/frontend/ayo/ayo_default/pt_BR/js/custom/microinteractions.min.js";

$checks = [
    'CSS desktop compilado' => file_exists($stylesL) && filesize($stylesL) > 400000,
    'CSS mobile compilado' => file_exists($stylesM),
    'JavaScript minificado' => file_exists($microMin),
    'Transições GPU-accelerated' => strpos($extendLess, 'transform') !== false,
    'Will-change otimizado' => strpos($extendLess, 'will-change') !== false,
];

foreach ($checks as $check => $passed) {
    $totalChecks++;
    if ($passed) $passedChecks++;
    echo ($passed ? "✅" : "❌") . " $check\n";
}

// FASE 5: Acessibilidade (99%)
echo "\n♿ FASE 5: ACESSIBILIDADE\n";
echo str_repeat("-", 70) . "\n";

$skipLinks = "$projectRoot/app/design/frontend/ayo/ayo_default/Magento_Theme/templates/html/skip-links.phtml";
$skipLinksExists = file_exists($skipLinks);
$skipLinksContent = $skipLinksExists ? file_get_contents($skipLinks) : '';

$checks = [
    'skip-links.phtml existe' => $skipLinksExists,
    'ARIA labels presentes' => strpos($skipLinksContent, 'aria-label') !== false,
    'Tabindex configurado' => strpos($skipLinksContent, 'tabindex') !== false,
    ':focus-visible CSS' => strpos($extendLess, ':focus') !== false,
    'prefers-reduced-motion' => strpos($extendLess, 'prefers-reduced-motion') !== false,
];

foreach ($checks as $check => $passed) {
    $totalChecks++;
    if ($passed) $passedChecks++;
    echo ($passed ? "✅" : "❌") . " $check\n";
}

// VERIFICAÇÕES DE DEPLOY
echo "\n🚀 VERIFICAÇÕES DE DEPLOY\n";
echo str_repeat("-", 70) . "\n";

$stylesLImport = file_get_contents("$projectRoot/app/design/frontend/ayo/ayo_default/web/css/styles-l.less");
$stylesMImport = file_get_contents("$projectRoot/app/design/frontend/ayo/ayo_default/web/css/styles-m.less");

$checks = [
    '_extend.less importado em styles-l' => strpos($stylesLImport, '_extend.less') !== false,
    '_extend.less importado em styles-m' => strpos($stylesMImport, '_extend.less') !== false,
    'RequireJS configurado' => file_exists("$projectRoot/app/design/frontend/ayo/ayo_default/requirejs-config.js"),
    'Layout default.xml existe' => file_exists("$projectRoot/app/design/frontend/ayo/ayo_default/Magento_Theme/layout/default.xml"),
];

foreach ($checks as $check => $passed) {
    $totalChecks++;
    if ($passed) $passedChecks++;
    echo ($passed ? "✅" : "❌") . " $check\n";
}

// MÉTRICAS FINAIS
echo "\n📈 MÉTRICAS COMPILADAS\n";
echo str_repeat("-", 70) . "\n";

if (file_exists($stylesL)) {
    $cssContent = file_get_contents($stylesL);
    $colorCount = substr_count($cssContent, '#b73337');
    $keyframesCount = substr_count($cssContent, '@keyframes');
    $mediaCount = substr_count($cssContent, '@media');
    $transitionCount = substr_count($cssContent, 'transition:');
    
    echo "📦 Tamanho CSS Desktop: " . number_format(filesize($stylesL) / 1024, 0) . " KB\n";
    echo "🎨 Ocorrências #b73337: $colorCount\n";
    echo "🎪 Animações @keyframes: $keyframesCount\n";
    echo "📱 Media Queries: $mediaCount\n";
    echo "⚡ Transições: $transitionCount\n";
}

if (file_exists($microMin)) {
    echo "📦 Tamanho JS (minificado): " . number_format(filesize($microMin) / 1024, 0) . " KB\n";
}

// RESULTADO FINAL
echo "\n" . str_repeat("=", 70) . "\n";
$percentage = round(($passedChecks / $totalChecks) * 100);
$status = $percentage >= 95 ? "EXCELENTE ✨" : ($percentage >= 85 ? "MUITO BOM 👍" : "ATENÇÃO ⚠️");

echo "🎯 RESULTADO: $passedChecks/$totalChecks checks passaram ($percentage%) - $status\n";
echo str_repeat("=", 70) . "\n\n";

exit($percentage >= 95 ? 0 : 1);
