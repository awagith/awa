<?php
/**
 * Script para Marcar Produtos como Featured e Configurar Price Countdown
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

echo "⭐ Configurando Produtos Featured e Price Countdown\n";
echo "=================================================\n\n";

$productRepository = $objectManager->get('Magento\Catalog\Api\ProductRepositoryInterface');
$searchCriteriaBuilder = $objectManager->get('Magento\Framework\Api\SearchCriteriaBuilder');
$productAction = $objectManager->get('Magento\Catalog\Model\Product\Action');

// 1. Marcar todos os produtos como Featured
echo "📦 Buscando produtos para marcar como Featured...\n";

$searchCriteria = $searchCriteriaBuilder
    ->addFilter('type_id', 'simple', 'eq')
    ->addFilter('status', 1, 'eq')
    ->setPageSize(50)
    ->create();

$products = $productRepository->getList($searchCriteria)->getItems();

$featuredCount = 0;
$countdownCount = 0;

foreach ($products as $product) {
    try {
        $productId = $product->getId();
        $sku = $product->getSku();
        
        // Marcar como Featured
        $productAction->updateAttributes(
            [$productId],
            ['featured' => 1],
            0
        );
        
        echo "✅ Produto '{$sku}' marcado como Featured\n";
        $featuredCount++;
        
        // Se tem Special Price, habilitar countdown
        $specialPrice = $product->getSpecialPrice();
        $specialPriceFrom = $product->getSpecialFromDate();
        $specialPriceTo = $product->getSpecialToDate();
        
        if ($specialPrice && $specialPrice > 0) {
            // Se não tem data de término, adicionar 30 dias
            if (!$specialPriceTo) {
                $specialPriceTo = date('Y-m-d H:i:s', strtotime('+30 days'));
            }
            
            // Se não tem data de início, usar hoje
            if (!$specialPriceFrom) {
                $specialPriceFrom = date('Y-m-d H:i:s');
            }
            
            $productAction->updateAttributes(
                [$productId],
                [
                    'special_from_date' => $specialPriceFrom,
                    'special_to_date' => $specialPriceTo
                ],
                0
            );
            
            echo "   ⏱️  Special Price configurado (até " . date('d/m/Y', strtotime($specialPriceTo)) . ")\n";
            $countdownCount++;
        }
        
    } catch (\Exception $e) {
        echo "❌ Erro no produto {$sku}: {$e->getMessage()}\n";
    }
}

// 2. Marcar produtos como New (últimos 30 dias)
echo "\n🆕 Marcando produtos como New (Set as New)...\n";

$newCount = 0;
$dateFrom = date('Y-m-d H:i:s', strtotime('-30 days'));
$dateTo = date('Y-m-d H:i:s', strtotime('+30 days'));

foreach ($products as $product) {
    try {
        $productId = $product->getId();
        $sku = $product->getSku();
        
        $productAction->updateAttributes(
            [$productId],
            [
                'news_from_date' => $dateFrom,
                'news_to_date' => $dateTo
            ],
            0
        );
        
        echo "✅ Produto '{$sku}' marcado como New\n";
        $newCount++;
        
    } catch (\Exception $e) {
        echo "❌ Erro: {$e->getMessage()}\n";
    }
}

echo "\n=================================================\n";
echo "📊 Resumo da Execução:\n";
echo "   ⭐ Produtos Featured: $featuredCount\n";
echo "   ⏱️  Price Countdown: $countdownCount\n";
echo "   🆕 Produtos New: $newCount\n";
echo "=================================================\n\n";

echo "🎉 Produtos configurados com sucesso!\n";
echo "📋 Módulos que usam estas configurações:\n";
echo "   - Rokanthemes_Featuredpro (Featured Products)\n";
echo "   - Rokanthemes_PriceCountdown (Countdown Timer)\n";
echo "   - Rokanthemes_Newproduct (New Products)\n\n";

echo "⚠️  IMPORTANTE:\n";
echo "   1. Execute: php bin/magento indexer:reindex\n";
echo "   2. Execute: php bin/magento cache:flush\n";
echo "   3. Verifique os produtos na homepage\n\n";

echo "✅ Script concluído!\n";
