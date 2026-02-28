<?php
/**
 * Script para configurar Sticky Header do tema Ayo
 * Conforme auditoria AUDITORIA_TEMA_AYO.md
 * 
 * Execução:
 * php scripts/configurar_sticky_header.php
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode('adminhtml');

// Config Writer
$configWriter = $objectManager->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);

echo "=== CONFIGURAÇÃO DO STICKY HEADER ===\n\n";

// Configurações do Sticky Header
$stickyConfigs = [
    // Habilitar Sticky Header
    [
        'path' => 'rokanthemes_themeoption/sticky_header/enable',
        'value' => '1',
        'label' => 'Sticky Header: Habilitado',
    ],
    
    // Background color (branco)
    [
        'path' => 'rokanthemes_themeoption/sticky_header/background_color',
        'value' => '#ffffff',
        'label' => 'Background Color: #ffffff (Branco)',
    ],
    
    // Text color
    [
        'path' => 'rokanthemes_themeoption/sticky_header/text_color',
        'value' => '#333333',
        'label' => 'Text Color: #333333',
    ],
    
    // Link color (usar cor primária do tema)
    [
        'path' => 'rokanthemes_themeoption/sticky_header/link_color',
        'value' => '#b73337',
        'label' => 'Link Color: #b73337 (Cor Primária)',
    ],
    
    // Link hover color
    [
        'path' => 'rokanthemes_themeoption/sticky_header/link_hover_color',
        'value' => '#8d2729',
        'label' => 'Link Hover Color: #8d2729',
    ],
    
    // Scroll offset (quando o sticky aparece)
    [
        'path' => 'rokanthemes_themeoption/sticky_header/scroll_offset',
        'value' => '100',
        'label' => 'Scroll Offset: 100px',
    ],
    
    // Animation
    [
        'path' => 'rokanthemes_themeoption/sticky_header/animation',
        'value' => 'slideDown',
        'label' => 'Animation: slideDown',
    ],
    
    // Mostrar logo no sticky
    [
        'path' => 'rokanthemes_themeoption/sticky_header/show_logo',
        'value' => '1',
        'label' => 'Show Logo: Sim',
    ],
    
    // Mostrar minicart no sticky
    [
        'path' => 'rokanthemes_themeoption/sticky_header/show_minicart',
        'value' => '1',
        'label' => 'Show Minicart: Sim',
    ],
    
    // Mostrar search no sticky
    [
        'path' => 'rokanthemes_themeoption/sticky_header/show_search',
        'value' => '1',
        'label' => 'Show Search: Sim',
    ],
];

$savedCount = 0;

foreach ($stickyConfigs as $config) {
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

echo "\n=== CONFIGURAÇÕES GERAIS DO HEADER ===\n\n";

// Configurações gerais do header
$headerConfigs = [
    // Header layout
    [
        'path' => 'rokanthemes_themeoption/header/header_layout',
        'value' => 'layout1',
        'label' => 'Header Layout: Layout 1',
    ],
    
    // Mostrar hotline
    [
        'path' => 'rokanthemes_themeoption/header/show_hotline',
        'value' => '1',
        'label' => 'Show Hotline: Sim',
    ],
    
    // Texto hotline
    [
        'path' => 'rokanthemes_themeoption/header/hotline_text',
        'value' => 'Atendimento:',
        'label' => 'Hotline Text: "Atendimento:"',
    ],
    
    // Número hotline
    [
        'path' => 'rokanthemes_themeoption/header/hotline_number',
        'value' => '(11) 1234-5678',
        'label' => 'Hotline Number: (11) 1234-5678',
    ],
    
    // Mostrar wishlist
    [
        'path' => 'rokanthemes_themeoption/header/show_wishlist',
        'value' => '1',
        'label' => 'Show Wishlist: Sim',
    ],
    
    // Mostrar compare
    [
        'path' => 'rokanthemes_themeoption/header/show_compare',
        'value' => '1',
        'label' => 'Show Compare: Sim',
    ],
];

foreach ($headerConfigs as $config) {
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

echo "\n=== CRIANDO CSS PARA STICKY HEADER ===\n\n";

// CSS customizado para sticky header
$stickyCssContent = <<<CSS
/**
 * Sticky Header Customizations
 * Aplicado automaticamente via configurar_sticky_header.php
 */

/* Sticky Header Container */
.sticky-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    width: 100%;
    z-index: 1000;
    background-color: #ffffff;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease-in-out;
}

.sticky-header.active {
    transform: translateY(0);
}

.sticky-header.inactive {
    transform: translateY(-100%);
}

/* Sticky Header Logo */
.sticky-header .logo {
    height: 50px;
}

.sticky-header .logo img {
    max-height: 40px;
    width: auto;
}

/* Sticky Header Navigation */
.sticky-header .navigation {
    background-color: transparent;
}

.sticky-header .nav-item > a {
    color: #333333;
    font-weight: 500;
}

.sticky-header .nav-item > a:hover {
    color: #b73337;
}

/* Sticky Header Icons */
.sticky-header .header-icons {
    display: flex;
    align-items: center;
    gap: 15px;
}

.sticky-header .header-icon {
    color: #333333;
    font-size: 18px;
    transition: color 0.3s;
}

.sticky-header .header-icon:hover {
    color: #b73337;
}

/* Sticky Header Search */
.sticky-header .search-wrapper {
    max-width: 400px;
}

/* Sticky Header Minicart */
.sticky-header .minicart-wrapper {
    position: relative;
}

.sticky-header .minicart-wrapper .counter {
    background-color: #b73337;
    color: #ffffff;
}

/* Mobile Sticky Header */
@media (max-width: 767px) {
    .sticky-header {
        padding: 10px 0;
    }
    
    .sticky-header .logo img {
        max-height: 30px;
    }
    
    .sticky-header .header-icons {
        gap: 10px;
    }
    
    .sticky-header .header-icon {
        font-size: 16px;
    }
}

/* Animation slideDown */
@keyframes slideDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.sticky-header.slide-down {
    animation: slideDown 0.3s ease-in-out;
}

/* Compensação para conteúdo quando sticky está ativo */
body.sticky-header-active {
    padding-top: 80px;
}

@media (max-width: 767px) {
    body.sticky-header-active {
        padding-top: 60px;
    }
}
CSS;

$cssFilePath = BP . '/pub/media/custom-sticky-header.css';

try {
    file_put_contents($cssFilePath, $stickyCssContent);
    echo "✅ Arquivo CSS criado: pub/media/custom-sticky-header.css\n";
    echo "   Tamanho: " . strlen($stickyCssContent) . " bytes\n\n";
} catch (\Exception $e) {
    echo "❌ Erro ao criar CSS: " . $e->getMessage() . "\n\n";
}

echo "=== RESUMO ===\n";
echo "✅ Configurações salvas: $savedCount/" . (count($stickyConfigs) + count($headerConfigs)) . "\n";
echo "✅ Sticky Header: Habilitado\n";
echo "✅ Background: #ffffff (Branco)\n";
echo "✅ Link Color: #b73337 (Paleta do tema)\n";
echo "✅ Scroll Offset: 100px\n";
echo "✅ Animation: slideDown\n";
echo "✅ Hotline: (11) 1234-5678\n";
echo "✅ Custom CSS: pub/media/custom-sticky-header.css\n\n";

echo "⚠️  AÇÃO NECESSÁRIA:\n";
echo "1. Upload do Sticky Logo:\n";
echo "   Admin > Content > Design > Configuration\n";
echo "   ayo_default > Header > Sticky Logo\n";
echo "   Recomendação: Logo transparente, 200x40px\n\n";
echo "2. Verificar número de telefone correto:\n";
echo "   Stores > Configuration > Rokanthemes > Theme Option > Header\n";
echo "   Hotline Number: Alterar para o telefone real\n\n";

echo "✅ Script concluído!\n";
