<?php
/**
 * Script para Aplicar Paleta de Cores #b73337
 * Configura tema com cores conforme branch feat/paleta-b73337
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

echo "🎨 Aplicando Paleta de Cores #b73337 no Tema Ayo\n";
echo "===============================================\n\n";

$configWriter = $objectManager->get('Magento\Framework\App\Config\Storage\WriterInterface');

// Configurações de cores do tema
$colorConfigs = [
    // Habilitar Custom Color
    'rokanthemes_themeoption/customcolor/custom_color' => '1',
    
    // Basic Colors
    'rokanthemes_themeoption/customcolor/text_color' => '#333333',
    'rokanthemes_themeoption/customcolor/link_color' => '#b73337',
    'rokanthemes_themeoption/customcolor/link_hover_color' => '#8d2729',
    
    // Button Colors
    'rokanthemes_themeoption/customcolor/button_text_color' => '#ffffff',
    'rokanthemes_themeoption/customcolor/button_background_color' => '#b73337',
    'rokanthemes_themeoption/customcolor/button_hover_text_color' => '#ffffff',
    'rokanthemes_themeoption/customcolor/button_hover_background_color' => '#8d2729',
    
    // Header Colors
    'rokanthemes_themeoption/customcolor/header_background_color' => '#ffffff',
    'rokanthemes_themeoption/customcolor/header_text_color' => '#333333',
    
    // Footer Colors
    'rokanthemes_themeoption/customcolor/footer_background_color' => '#f5f5f5',
    'rokanthemes_themeoption/customcolor/footer_text_color' => '#666666',
    'rokanthemes_themeoption/customcolor/footer_link_color' => '#b73337',
    
    // Primary/Accent Color
    'rokanthemes_themeoption/customcolor/primary_color' => '#b73337',
    'rokanthemes_themeoption/customcolor/accent_color' => '#b73337',
    
    // General Settings
    'rokanthemes_themeoption/general/copyright' => '© ' . date('Y') . ' Grupo Awamotos. Todos os direitos reservados.',
    'rokanthemes_themeoption/general/page_width' => '1200px',
    
    // Font Settings
    'rokanthemes_themeoption/font/custom_font' => '1',
    'rokanthemes_themeoption/font/basic_font_size' => '14px',
    'rokanthemes_themeoption/font/basic_font_family' => 'Roboto, Arial, sans-serif',
    'rokanthemes_themeoption/font/google_font' => 'Roboto:300,400,500,700',
    
    // Newsletter Popup
    'rokanthemes_themeoption/newsletter_popup/enable' => '1',
    'rokanthemes_themeoption/newsletter_popup/width' => '600',
    'rokanthemes_themeoption/newsletter_popup/height' => '400',
    'rokanthemes_themeoption/newsletter_popup/delay' => '5000',
    'rokanthemes_themeoption/newsletter_popup/cookie_lifetime' => '7',
];

$configured = 0;
$errors = 0;

echo "📝 Configurando cores e opções do tema...\n\n";

foreach ($colorConfigs as $path => $value) {
    try {
        $configWriter->save($path, $value, 'default', 0);
        echo "✅ $path = $value\n";
        $configured++;
    } catch (\Exception $e) {
        echo "❌ Erro em $path: {$e->getMessage()}\n";
        $errors++;
    }
}

echo "\n===============================================\n";
echo "📊 Resumo da Configuração:\n";
echo "   ✅ Configurações aplicadas: $configured\n";
echo "   ❌ Erros: $errors\n";
echo "===============================================\n\n";

// Criar CSS customizado com a paleta
echo "📄 Criando arquivo CSS customizado...\n";

$customCss = "/* 
 * Paleta de Cores #b73337 - Grupo Awamotos
 * Aplicada automaticamente via script
 * Data: " . date('d/m/Y H:i:s') . "
 */

:root {
    --primary-color: #b73337;
    --primary-hover: #8d2729;
    --primary-light: #d94448;
    --primary-dark: #6d1f21;
    
    --text-color: #333333;
    --text-light: #666666;
    --text-lighter: #999999;
    
    --link-color: #b73337;
    --link-hover: #8d2729;
    
    --button-bg: #b73337;
    --button-hover-bg: #8d2729;
    --button-text: #ffffff;
    
    --border-color: #e0e0e0;
    --bg-light: #f5f5f5;
    --bg-white: #ffffff;
}

/* Botões Primários */
.btn-primary,
.action.primary,
.button.primary,
.btn.btn-primary {
    background-color: var(--button-bg) !important;
    border-color: var(--button-bg) !important;
    color: var(--button-text) !important;
}

.btn-primary:hover,
.action.primary:hover,
.button.primary:hover,
.btn.btn-primary:hover {
    background-color: var(--button-hover-bg) !important;
    border-color: var(--button-hover-bg) !important;
}

/* Links */
a {
    color: var(--link-color);
}

a:hover {
    color: var(--link-hover);
}

/* Preços */
.price,
.special-price .price {
    color: var(--primary-color) !important;
}

/* Badges e Labels */
.product-label-hot,
.product-label-sale,
.badge-sale {
    background-color: var(--primary-color) !important;
}

/* Header */
.top-header {
    background-color: var(--bg-light);
}

.header-hotline a {
    color: var(--primary-color);
}

/* Footer */
.velaFooterTitle {
    color: var(--primary-color);
    border-bottom: 2px solid var(--primary-color);
    padding-bottom: 10px;
    margin-bottom: 15px;
}

.velaFooterLinks a:hover {
    color: var(--primary-hover);
}

/* Menu */
.navigation .level-top:hover,
.navigation .level-top.ui-state-active {
    color: var(--primary-color);
}

/* Wishlist e Compare */
.action.towishlist:hover i,
.action.tocompare:hover i {
    color: var(--primary-color);
}

/* Paginação */
.pagination .page-link:hover {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

/* Forms */
.form-control:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(183, 51, 55, 0.25);
}

/* Product View */
.product-info-main .price-box .price {
    color: var(--primary-color);
    font-weight: bold;
}

/* Cart */
.cart-summary .action.primary.checkout {
    background-color: var(--primary-color);
}

/* Newsletter */
.newsletter .action.subscribe {
    background-color: var(--primary-color);
}

.newsletter .action.subscribe:hover {
    background-color: var(--primary-hover);
}
";

try {
    $cssPath = BP . '/pub/media/custom-colors-b73337.css';
    file_put_contents($cssPath, $customCss);
    echo "✅ CSS customizado criado: pub/media/custom-colors-b73337.css\n";
} catch (\Exception $e) {
    echo "❌ Erro ao criar CSS: {$e->getMessage()}\n";
}

echo "\n🎉 Paleta de cores aplicada com sucesso!\n";
echo "📋 Cores principais configuradas:\n";
echo "   - Primary: #b73337\n";
echo "   - Hover: #8d2729\n";
echo "   - Text: #333333\n";
echo "   - Links: #b73337\n";
echo "   - Botões: #b73337 (background)\n\n";

echo "⚠️  IMPORTANTE:\n";
echo "   1. Execute: php bin/magento cache:flush\n";
echo "   2. Recompile: php bin/magento setup:di:compile\n";
echo "   3. Deploy estático: php bin/magento setup:static-content:deploy pt_BR -f\n";
echo "   4. Verifique no Admin: Rokanthemes > Theme Settings > Custom Color\n\n";

echo "✅ Script concluído!\n";
