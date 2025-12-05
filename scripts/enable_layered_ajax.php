<?php
/**
 * Habilitar filtros Ajax (LayeredAjax) e configurar categorias
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

$configWriter = $objectManager->get(\Magento\Framework\App\Config\Storage\WriterInterface::class);
$cacheManager = $objectManager->get(\Magento\Framework\App\CacheInterface::class);

echo "🔧 Configurando módulo LayeredAjax...\n\n";

// Configurações do módulo Rokanthemes LayeredAjax
$configs = [
    'rokanthemes_layeredajax/general/enable' => 1,
    'rokanthemes_layeredajax/general/ajax_enable' => 1,
    'rokanthemes_layeredajax/general/show_product_count' => 1,
    'rokanthemes_layeredajax/general/price_slider' => 1,
];

foreach ($configs as $path => $value) {
    try {
        $configWriter->save($path, $value);
        echo "✅ Configurado: $path = $value\n";
    } catch (\Exception $e) {
        echo "⚠️  Erro em $path: {$e->getMessage()}\n";
    }
}

// Limpar cache de configuração
$cacheManager->clean([\Magento\Framework\App\Config::CACHE_TAG]);

echo "\n📊 RESUMO:\n";
echo "   ✅ LayeredAjax habilitado\n";
echo "   ✅ AJAX filters ativo\n";
echo "   ✅ Product count visível\n";
echo "   ✅ Price slider ativo\n";
echo "\n🎯 Filtros Ajax agora estão disponíveis nas categorias!\n";
