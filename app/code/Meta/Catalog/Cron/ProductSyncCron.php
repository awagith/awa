<?php

declare(strict_types=1);

namespace Meta\Catalog\Cron;

use Meta\Catalog\Model\Feed\ProductFeedManager;
use Psr\Log\LoggerInterface;

/**
 * Cron job for periodic product sync to Meta catalog (every 4 hours)
 */
class ProductSyncCron
{
    public function __construct(
        private readonly ProductFeedManager $feedManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(): void
    {
        $this->logger->info('[Meta Cron] Starting product sync');
        try {
            $this->feedManager->syncAllProducts();
        } catch (\Throwable $e) {
            $this->logger->error('[Meta Cron] Product sync failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
