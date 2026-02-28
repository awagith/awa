<?php
/**
 * Script para configurar produtos bestsellers e low stock
 * Para ativar Social Proof badges
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

$productCollectionFactory = $objectManager->get(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class);
$productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
$connection = $resource->getConnection();

echo "🏆 Configurando produtos bestsellers e low stock...\n\n";

// Buscar produtos para configurar
$collection = $productCollectionFactory->create()
    ->addAttributeToSelect('*')
    ->setPageSize(10);

$bestsellers = 0;
$lowstock = 0;
$count = 0;

foreach ($collection as $product) {
    try {
        $updated = false;
        
        // Marcar primeiros 3 como bestsellers
        if ($count < 3) {
            $product->setData('is_best_seller', 1);
            $updated = true;
            $bestsellers++;
            echo "✅ {$product->getSku()} - Marcado como BESTSELLER\n";
        }
        
        // Ajustar estoque dos próximos 3 para low stock (5 unidades)
        if ($count >= 3 && $count < 6) {
            $stockItem = $product->getExtensionAttributes()->getStockItem();
            if ($stockItem) {
                $stockItem->setQty(5);
                $stockItem->setIsInStock(true);
                $updated = true;
                $lowstock++;
                echo "📦 {$product->getSku()} - Estoque ajustado para LOW STOCK (5 unidades)\n";
            }
        }
        
        if ($updated) {
            $productRepository->save($product);
        }
        
        $count++;
        
    } catch (\Exception $e) {
        echo "❌ Erro em {$product->getSku()}: {$e->getMessage()}\n";
    }
}

echo "\n📊 RESUMO:\n";
echo "   ✅ Bestsellers criados: $bestsellers\n";
echo "   📦 Low stock configurados: $lowstock\n";
echo "   📋 Total processados: $count\n";
echo "\n🎯 Social Proof badges agora devem aparecer nas listagens!\n";
