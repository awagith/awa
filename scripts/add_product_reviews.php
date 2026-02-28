<?php
/**
 * Adicionar reviews fake para produtos terem aggregate rating
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode(\Magento\Framework\App\Area::AREA_FRONTEND);

$reviewFactory = $objectManager->get(\Magento\Review\Model\ReviewFactory::class);
$ratingFactory = $objectManager->get(\Magento\Review\Model\RatingFactory::class);
$storeManager = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
$productCollection = $objectManager->get(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class);

echo "⭐ Adicionando reviews aos produtos...\n\n";

$collection = $productCollection->create()
    ->addAttributeToSelect('*')
    ->setPageSize(5);

$reviewsAdded = 0;

foreach ($collection as $product) {
    try {
        // Adicionar 2 reviews por produto
        for ($i = 1; $i <= 2; $i++) {
            $review = $reviewFactory->create();
            $review->setEntityId(1) // product
                   ->setEntityPkValue($product->getId())
                   ->setStatusId(\Magento\Review\Model\Review::STATUS_APPROVED)
                   ->setTitle("Excelente produto!")
                   ->setDetail("Produto de ótima qualidade, recomendo!")
                   ->setNickname("Cliente " . $i)
                   ->setStoreId($storeManager->getStore()->getId())
                   ->setStores([$storeManager->getStore()->getId()])
                   ->save();
            
            // Adicionar rating (4-5 estrelas)
            $ratingOptions = [1 => 4, 2 => 5, 3 => 5]; // Rating, Quality, Value
            foreach ($ratingOptions as $ratingId => $optionId) {
                $rating = $ratingFactory->create();
                $rating->setRatingId($ratingId)
                       ->setReviewId($review->getId())
                       ->addOptionVote($optionId, $product->getId());
            }
            
            $reviewsAdded++;
        }
        
        $review->aggregate();
        echo "✅ {$product->getSku()} - 2 reviews adicionadas\n";
        
    } catch (\Exception $e) {
        echo "⚠️  Erro em {$product->getSku()}: {$e->getMessage()}\n";
    }
}

echo "\n📊 RESUMO:\n";
echo "   ⭐ Reviews adicionadas: $reviewsAdded\n";
echo "   📦 Produtos com reviews: " . ($reviewsAdded / 2) . "\n";
echo "\n🎯 Product Schema agora terá aggregate rating!\n";
