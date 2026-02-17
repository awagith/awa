<?php
/**
 * Script para configurar módulos avançados (LayeredAjax, Custom Menu)
 * Conforme auditoria AUDITORIA_TEMA_AYO.md
 * 
 * Execução:
 * php scripts/configurar_modulos_avancados.php
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode('adminhtml');

// Config Writer
$configWriter = $objectManager->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);

echo "=== CONFIGURAÇÃO DE MÓDULOS AVANÇADOS ===\n\n";

// ===== LAYERED AJAX =====
echo "--- Layered Ajax ---\n";

$layeredConfigs = [
    [
        'path' => 'rokanthemes_layeredajax/general/enable',
        'value' => '1',
        'label' => 'Layered Ajax: Habilitado',
    ],
    [
        'path' => 'rokanthemes_layeredajax/general/open_all_tab',
        'value' => '0',
        'label' => 'Open All Tab: Desabilitado (accordion fechado)',
    ],
    [
        'path' => 'rokanthemes_layeredajax/general/use_price_slider',
        'value' => '1',
        'label' => 'Price Range Slider: Habilitado',
    ],
    [
        'path' => 'rokanthemes_layeredajax/general/show_product_count',
        'value' => '1',
        'label' => 'Show Product Count: Habilitado',
    ],
];

$savedCount = 0;

foreach ($layeredConfigs as $config) {
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

// ===== CUSTOM MENU =====
echo "\n--- Custom Menu ---\n";

$customMenuConfigs = [
    [
        'path' => 'rokanthemes_custommenu/general/default_menu_type',
        'value' => 'fullwidth',
        'label' => 'Default Menu Type: Full Width',
    ],
    [
        'path' => 'rokanthemes_custommenu/general/visible_menu_depth',
        'value' => '3',
        'label' => 'Visible Menu Depth: 3 níveis',
    ],
    [
        'path' => 'rokanthemes_custommenu/general/show_icons',
        'value' => '1',
        'label' => 'Show Category Icons: Habilitado',
    ],
    [
        'path' => 'rokanthemes_custommenu/general/animation',
        'value' => 'fade',
        'label' => 'Animation: Fade',
    ],
];

foreach ($customMenuConfigs as $config) {
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

// ===== VERTICAL MENU =====
echo "\n--- Vertical Menu ---\n";

$verticalMenuConfigs = [
    [
        'path' => 'rokanthemes_verticalmenu/general/enable',
        'value' => '1',
        'label' => 'Vertical Menu: Habilitado',
    ],
    [
        'path' => 'rokanthemes_verticalmenu/general/limit_show_more',
        'value' => '10',
        'label' => 'Limit Show More Categories: 10',
    ],
    [
        'path' => 'rokanthemes_verticalmenu/general/show_more_text',
        'value' => 'Ver Mais',
        'label' => 'Show More Text: "Ver Mais"',
    ],
    [
        'path' => 'rokanthemes_verticalmenu/general/show_less_text',
        'value' => 'Ver Menos',
        'label' => 'Show Less Text: "Ver Menos"',
    ],
];

foreach ($verticalMenuConfigs as $config) {
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

// ===== GENERAL THEME OPTIONS =====
echo "\n--- General Theme Options ---\n";

$generalConfigs = [
    [
        'path' => 'rokanthemes_themeoption/general/page_width',
        'value' => '1200px',
        'label' => 'Page Width: 1200px',
    ],
    [
        'path' => 'rokanthemes_themeoption/general/auto_render_less',
        'value' => '0',
        'label' => 'Auto Render LESS: Desabilitado (produção)',
    ],
    [
        'path' => 'rokanthemes_themeoption/general/back_to_top',
        'value' => '1',
        'label' => 'Back to Top Button: Habilitado',
    ],
    [
        'path' => 'rokanthemes_themeoption/general/show_loader',
        'value' => '1',
        'label' => 'Show Page Loader: Habilitado',
    ],
];

foreach ($generalConfigs as $config) {
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

echo "\n=== RESUMO ===\n";
echo "✅ Configurações salvas: $savedCount/" . 
    (count($layeredConfigs) + count($customMenuConfigs) + count($verticalMenuConfigs) + count($generalConfigs)) . "\n";
echo "✅ Layered Ajax: Price sliders, product count\n";
echo "✅ Custom Menu: Full width, 3 níveis, ícones\n";
echo "✅ Vertical Menu: Habilitado, limite 10 categorias\n";
echo "✅ General: Page width 1200px, LESS desabilitado\n\n";

echo "⚠️  PRÓXIMOS PASSOS (MANUAIS):\n";
echo "1. Adicionar ícones às categorias:\n";
echo "   Catalog > Categories > [Categoria]\n";
echo "   Custom Menu Options > Icon Image ou Font Icon Class\n\n";
echo "2. Configurar submenus com conteúdo:\n";
echo "   Catalog > Categories > [Categoria]\n";
echo "   Custom Menu Options > Content Blocks (Top/Left/Right/Bottom)\n\n";
echo "3. Testar filtros Ajax em páginas de categoria\n\n";

echo "✅ Script concluído!\n";
