<?php

declare(strict_types=1);

namespace Meta\Catalog\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Meta\BusinessExtension\Api\SystemConfigInterface;
use Meta\Catalog\Model\Feed\ProductFeedManager;
use Psr\Log\LoggerInterface;

/**
 * Observer to delete product from Meta catalog on Magento delete
 */
class ProductDelete implements ObserverInterface
{
    public function __construct(
        private readonly SystemConfigInterface $config,
        private readonly ProductFeedManager $feedManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        try {
            $product = $observer->getEvent()->getData('product');
            if ($product) {
                $this->feedManager->deleteProduct($product->getSku());
            }
        } catch (\Throwable $e) {
            $this->logger->error('[Meta Catalog] ProductDelete observer failed', [
                'product_id' => $observer->getEvent()->getData('product')?->getId(),
                'error' => $e->getMessage()
            ]);
        }
    }
}
