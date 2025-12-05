<?php
/**
 * Script para gerar imagens placeholder para o slider e logos
 * Como não podemos criar imagens reais via PHP puro, geramos SVG placeholders
 * 
 * Execução:
 * php scripts/gerar_placeholders_imagens.php
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode('adminhtml');

echo "=== GERAÇÃO DE PLACEHOLDERS ===\n\n";

// Diretórios
$mediaPath = BP . '/pub/media/';
$sliderPath = $mediaPath . 'slidebanner/';
$logoPath = $mediaPath . 'logo/';
$paymentPath = $mediaPath . 'payment/';
$securityPath = $mediaPath . 'security/';

// Criar diretórios se não existirem
foreach ([$sliderPath, $logoPath, $paymentPath, $securityPath] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "✅ Diretório criado: " . basename($dir) . "/\n";
    }
}

echo "\n--- SLIDES HOMEPAGE ---\n";

// Slides do slider (1920x600px)
$slides = [
    [
        'file' => 'slide-1-bem-vindo.svg',
        'width' => 1920,
        'height' => 600,
        'bg' => '#b73337',
        'title' => 'Bem-vindo ao Grupo Awamotos',
        'subtitle' => 'As melhores peças e acessórios para seu veículo',
    ],
    [
        'file' => 'slide-2-ofertas.svg',
        'width' => 1920,
        'height' => 600,
        'bg' => '#8d2729',
        'title' => 'Ofertas Imperdíveis',
        'subtitle' => 'Até 50% de desconto em produtos selecionados',
    ],
    [
        'file' => 'slide-3-frete-gratis.svg',
        'width' => 1920,
        'height' => 600,
        'bg' => '#333333',
        'title' => 'Frete Grátis',
        'subtitle' => 'Em compras acima de R$ 299,00',
    ],
];

foreach ($slides as $slide) {
    $svg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="{$slide['width']}" height="{$slide['height']}" viewBox="0 0 {$slide['width']} {$slide['height']}">
    <defs>
        <linearGradient id="grad{$slide['file']}" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:{$slide['bg']};stop-opacity:1" />
            <stop offset="100%" style="stop-color:#000000;stop-opacity:0.3" />
        </linearGradient>
    </defs>
    <rect width="{$slide['width']}" height="{$slide['height']}" fill="url(#grad{$slide['file']})"/>
    <text x="960" y="250" font-family="Arial, sans-serif" font-size="72" font-weight="bold" fill="#ffffff" text-anchor="middle">
        {$slide['title']}
    </text>
    <text x="960" y="330" font-family="Arial, sans-serif" font-size="36" fill="#ffffff" text-anchor="middle" opacity="0.9">
        {$slide['subtitle']}
    </text>
    <rect x="810" y="380" width="300" height="60" rx="30" fill="#ffffff" opacity="0.9"/>
    <text x="960" y="420" font-family="Arial, sans-serif" font-size="24" font-weight="bold" fill="{$slide['bg']}" text-anchor="middle">
        SAIBA MAIS
    </text>
</svg>
SVG;

    $filepath = $sliderPath . $slide['file'];
    file_put_contents($filepath, $svg);
    echo "✅ {$slide['file']} ({$slide['width']}x{$slide['height']}px)\n";
}

echo "\n--- LOGOS ---\n";

// Logo principal (200x60px)
$logoSvg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="200" height="60" viewBox="0 0 200 60">
    <rect width="200" height="60" fill="#b73337"/>
    <text x="100" y="25" font-family="Arial, sans-serif" font-size="18" font-weight="bold" fill="#ffffff" text-anchor="middle">
        GRUPO
    </text>
    <text x="100" y="45" font-family="Arial, sans-serif" font-size="16" font-weight="bold" fill="#ffffff" text-anchor="middle">
        AWAMOTOS
    </text>
</svg>
SVG;

file_put_contents($logoPath . 'logo.svg', $logoSvg);
echo "✅ logo.svg (200x60px)\n";

// Sticky logo (200x40px - mais compacto)
$stickyLogoSvg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="200" height="40" viewBox="0 0 200 40">
    <rect width="200" height="40" fill="#b73337"/>
    <text x="100" y="28" font-family="Arial, sans-serif" font-size="14" font-weight="bold" fill="#ffffff" text-anchor="middle">
        GRUPO AWAMOTOS
    </text>
</svg>
SVG;

file_put_contents($logoPath . 'sticky-logo.svg', $stickyLogoSvg);
echo "✅ sticky-logo.svg (200x40px)\n";

// Favicon (32x32px)
$faviconSvg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32">
    <rect width="32" height="32" fill="#b73337"/>
    <text x="16" y="22" font-family="Arial, sans-serif" font-size="18" font-weight="bold" fill="#ffffff" text-anchor="middle">
        GA
    </text>
</svg>
SVG;

file_put_contents($logoPath . 'favicon.svg', $faviconSvg);
echo "✅ favicon.svg (32x32px)\n";

echo "\n--- ÍCONES DE PAGAMENTO ---\n";

// Ícones de pagamento (80x50px cada)
$paymentMethods = [
    ['file' => 'pix.svg', 'name' => 'PIX', 'color' => '#00BBF9'],
    ['file' => 'boleto.svg', 'name' => 'Boleto', 'color' => '#FF6B35'],
    ['file' => 'visa.svg', 'name' => 'VISA', 'color' => '#1A1F71'],
    ['file' => 'mastercard.svg', 'name' => 'Master', 'color' => '#EB001B'],
    ['file' => 'amex.svg', 'name' => 'AMEX', 'color' => '#006FCF'],
    ['file' => 'elo.svg', 'name' => 'ELO', 'color' => '#FFCD00'],
    ['file' => 'hipercard.svg', 'name' => 'Hiper', 'color' => '#C8102E'],
];

foreach ($paymentMethods as $method) {
    $paymentSvg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="80" height="50" viewBox="0 0 80 50">
    <rect width="80" height="50" rx="4" fill="#ffffff" stroke="#cccccc" stroke-width="1"/>
    <text x="40" y="32" font-family="Arial, sans-serif" font-size="14" font-weight="bold" fill="{$method['color']}" text-anchor="middle">
        {$method['name']}
    </text>
</svg>
SVG;

    file_put_contents($paymentPath . $method['file'], $paymentSvg);
    echo "✅ {$method['file']} (80x50px)\n";
}

echo "\n--- SELOS DE SEGURANÇA ---\n";

// Selo SSL (80x80px)
$sslSvg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">
    <circle cx="40" cy="40" r="38" fill="#4CAF50" stroke="#388E3C" stroke-width="2"/>
    <text x="40" y="35" font-family="Arial, sans-serif" font-size="14" font-weight="bold" fill="#ffffff" text-anchor="middle">
        SITE
    </text>
    <text x="40" y="50" font-family="Arial, sans-serif" font-size="14" font-weight="bold" fill="#ffffff" text-anchor="middle">
        SEGURO
    </text>
    <path d="M 25 42 L 35 52 L 55 28" stroke="#ffffff" stroke-width="4" fill="none" stroke-linecap="round"/>
</svg>
SVG;

file_put_contents($securityPath . 'ssl-secure.svg', $sslSvg);
echo "✅ ssl-secure.svg (80x80px)\n";

// Selo Google Safe
$googleSafeSvg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">
    <circle cx="40" cy="40" r="38" fill="#1A73E8" stroke="#1557B0" stroke-width="2"/>
    <text x="40" y="35" font-family="Arial, sans-serif" font-size="12" font-weight="bold" fill="#ffffff" text-anchor="middle">
        GOOGLE
    </text>
    <text x="40" y="50" font-family="Arial, sans-serif" font-size="12" font-weight="bold" fill="#ffffff" text-anchor="middle">
        SAFE
    </text>
</svg>
SVG;

file_put_contents($securityPath . 'google-safe.svg', $googleSafeSvg);
echo "✅ google-safe.svg (80x80px)\n";

echo "\n=== RESUMO ===\n";
echo "✅ Slides: 3 arquivos SVG (1920x600px)\n";
echo "✅ Logos: 3 arquivos SVG (logo, sticky, favicon)\n";
echo "✅ Pagamento: 7 ícones SVG (80x50px)\n";
echo "✅ Segurança: 2 selos SVG (80x80px)\n";
echo "\n📁 Total: 15 arquivos SVG criados\n\n";

echo "📂 LOCALIZAÇÃO DOS ARQUIVOS:\n";
echo "   Slides: pub/media/slidebanner/\n";
echo "   Logos: pub/media/logo/\n";
echo "   Pagamento: pub/media/payment/\n";
echo "   Segurança: pub/media/security/\n\n";

echo "⚠️  PRÓXIMOS PASSOS:\n";
echo "1. Atualizar slides com as imagens geradas:\n";
echo "   Admin > Rokanthemes > Manage Slider Items\n";
echo "   Fazer upload dos SVG de pub/media/slidebanner/\n\n";
echo "2. Configurar logos:\n";
echo "   Admin > Content > Design > Configuration > ayo_default\n";
echo "   Header > Logo Image: pub/media/logo/logo.svg\n";
echo "   Header > Sticky Logo: pub/media/logo/sticky-logo.svg\n";
echo "   HTML Head > Favicon: pub/media/logo/favicon.svg\n\n";
echo "3. Os ícones de pagamento já estão em pub/media/payment/\n";
echo "   O bloco footer_payment já referencia esses arquivos\n\n";

echo "✅ Script concluído!\n";
