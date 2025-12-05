<?php
/**
 * Script para configurar fontes Google (Roboto) no tema Ayo
 * Conforme auditoria AUDITORIA_TEMA_AYO.md
 * 
 * Execução:
 * php scripts/configurar_fontes_google.php
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode('adminhtml');

// Config Writer
$configWriter = $objectManager->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);

echo "=== CONFIGURAÇÃO DE FONTES GOOGLE ===\n\n";

// Configurações de fontes
$fontConfigs = [
    // Habilitar custom font
    [
        'path' => 'rokanthemes_themeoption/font/custom_font',
        'value' => '1',
        'label' => 'Custom Font: Habilitado',
    ],
    
    // Fonte principal (Roboto)
    [
        'path' => 'rokanthemes_themeoption/font/basic_font_family',
        'value' => 'Roboto',
        'label' => 'Basic Font Family: Roboto',
    ],
    
    // Google Font URL
    [
        'path' => 'rokanthemes_themeoption/font/google_font',
        'value' => 'Roboto:300,400,500,700',
        'label' => 'Google Font: Roboto (300,400,500,700)',
    ],
    
    // Tamanho básico da fonte
    [
        'path' => 'rokanthemes_themeoption/font/basic_font_size',
        'value' => '14px',
        'label' => 'Basic Font Size: 14px',
    ],
    
    // Fonte de cabeçalhos
    [
        'path' => 'rokanthemes_themeoption/font/heading_font_family',
        'value' => 'Roboto',
        'label' => 'Heading Font Family: Roboto',
    ],
    
    // Tamanho de cabeçalhos
    [
        'path' => 'rokanthemes_themeoption/font/heading_font_weight',
        'value' => '700',
        'label' => 'Heading Font Weight: 700 (Bold)',
    ],
];

$savedCount = 0;

foreach ($fontConfigs as $config) {
    try {
        $configWriter->save(
            $config['path'],
            $config['value'],
            \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            0
        );
        echo "✅ {$config['label']}\n";
        $savedCount++;
    } catch (\Exception $e) {
        echo "❌ Erro em {$config['path']}: " . $e->getMessage() . "\n";
    }
}

echo "\n=== APLICANDO FONTES NO TEMA ===\n\n";

// Criar CSS customizado com @import do Google Fonts
$customCssContent = <<<CSS
/**
 * Fontes Google - Roboto
 * Aplicado automaticamente via configurar_fontes_google.php
 */

/* Import Google Fonts */
@import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap');

/* Aplicar Roboto como fonte padrão */
body {
    font-family: 'Roboto', Arial, sans-serif;
    font-size: 14px;
    font-weight: 400;
    line-height: 1.6;
}

/* Cabeçalhos */
h1, h2, h3, h4, h5, h6,
.h1, .h2, .h3, .h4, .h5, .h6 {
    font-family: 'Roboto', Arial, sans-serif;
    font-weight: 700;
}

h1, .h1 {
    font-size: 2.5rem;
}

h2, .h2 {
    font-size: 2rem;
}

h3, .h3 {
    font-size: 1.75rem;
}

h4, .h4 {
    font-size: 1.5rem;
}

h5, .h5 {
    font-size: 1.25rem;
}

h6, .h6 {
    font-size: 1rem;
}

/* Botões */
.btn,
button,
.action.primary,
.action-primary {
    font-family: 'Roboto', Arial, sans-serif;
    font-weight: 500;
}

/* Inputs */
input,
textarea,
select,
.input-text {
    font-family: 'Roboto', Arial, sans-serif;
}

/* Menu */
.navigation,
.nav-sections,
.menu {
    font-family: 'Roboto', Arial, sans-serif;
    font-weight: 500;
}

/* Produtos */
.product-item-name,
.product-name {
    font-family: 'Roboto', Arial, sans-serif;
    font-weight: 500;
}

.price {
    font-family: 'Roboto', Arial, sans-serif;
    font-weight: 700;
}

/* Footer */
.footer {
    font-family: 'Roboto', Arial, sans-serif;
}

.footer-title,
.velaFooterTitle {
    font-weight: 700;
}

/* Breadcrumbs */
.breadcrumbs {
    font-family: 'Roboto', Arial, sans-serif;
    font-size: 13px;
}

/* Checkout */
.checkout-index-index {
    font-family: 'Roboto', Arial, sans-serif;
}

/* Cart */
.cart-summary,
.cart-totals {
    font-family: 'Roboto', Arial, sans-serif;
    font-weight: 500;
}
CSS;

$cssFilePath = BP . '/pub/media/custom-fonts-roboto.css';

try {
    file_put_contents($cssFilePath, $customCssContent);
    echo "✅ Arquivo CSS criado: pub/media/custom-fonts-roboto.css\n";
    echo "   Tamanho: " . strlen($customCssContent) . " bytes\n\n";
} catch (\Exception $e) {
    echo "❌ Erro ao criar CSS: " . $e->getMessage() . "\n\n";
}

echo "=== RESUMO ===\n";
echo "✅ Configurações salvas: $savedCount/6\n";
echo "✅ Fonte principal: Roboto (300, 400, 500, 700)\n";
echo "✅ Tamanho base: 14px\n";
echo "✅ Peso cabeçalhos: 700 (Bold)\n";
echo "✅ Custom CSS: pub/media/custom-fonts-roboto.css\n\n";

echo "⚠️  PRÓXIMOS PASSOS:\n";
echo "1. Incluir CSS no layout:\n";
echo "   Content > Design > Configuration > ayo_default\n";
echo "   HTML Head > Scripts and Style Sheets:\n";
echo "   <link rel=\"stylesheet\" type=\"text/css\" media=\"all\" href=\"{{media url='custom-fonts-roboto.css'}}\" />\n\n";
echo "2. Ou adicionar via layout XML em:\n";
echo "   app/design/frontend/ayo/ayo_default/Magento_Theme/layout/default_head_blocks.xml\n\n";

echo "✅ Script concluído!\n";
