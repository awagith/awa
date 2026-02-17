<?php
use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get(\Magento\Framework\App\State::class);
$state->setAreaCode('frontend');

$collectionFactory = $objectManager->get(\Rokanthemes\Testimonials\Model\ResourceModel\Testimonials\CollectionFactory::class);
$storeManager = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);

echo "=== DEBUG TESTIMONIALS COLLECTION ===\n\n";

$collection = $collectionFactory->create();
echo "1. Collection SEM filtros: " . $collection->getSize() . " registros\n";

$collection2 = $collectionFactory->create();
$collection2->addActiveFilter();
echo "2. Com addActiveFilter(): " . $collection2->getSize() . " registros\n";

$collection3 = $collectionFactory->create();
$collection3->addActiveFilter();
$collection3->addStoreFilter($storeManager->getStore()->getId());
echo "3. Com addActiveFilter() + addStoreFilter(): " . $collection3->getSize() . " registros\n";

echo "\n=== Analisando registros ===\n";
foreach ($collection as $item) {
    echo "  - ID={$item->getId()}, name={$item->getName()}, is_active={$item->getIsActive()}\n";
}

echo "\n=== SQL da collection com filtros ===\n";
$collection4 = $collectionFactory->create();
$collection4->addActiveFilter();
$collection4->addStoreFilter($storeManager->getStore()->getId());
echo $collection4->getSelect()->__toString() . "\n";
