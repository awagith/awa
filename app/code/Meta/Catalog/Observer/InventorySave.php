<?php

declare(strict_types=1);

namespace Meta\Catalog\Observer;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Meta\BusinessExtension\Api\SystemConfigInterface;
use Meta\Catalog\Model\Feed\ProductFeedManager;
use Psr\Log\LoggerInterface;

/**
 * Observer to re-sync product when inventory changes
 */
class InventorySave implements ObserverInterface
{
    public function __construct(
        private readonly SystemConfigInterface $config,
        private readonly ProductFeedManager $feedManager,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        try {
            $stockItem = $observer->getEvent()->getData('item');
            if ($stockItem) {
                $productId = $stockItem->getProductId();
                $product = $this->productRepository->getById((int) $productId);
                $this->feedManager->syncProduct($product);
            }
        } catch (\Throwable $e) {
            $this->logger->error('[Meta Catalog] InventorySave observer failed', [
                'product_id' => $observer->getEvent()->getData('item')?->getProductId(),
                'error' => $e->getMessage()
            ]);
        }
    }
}
