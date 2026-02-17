<?php
declare(strict_types=1);

namespace GrupoAwamotos\AbandonedCart\Cron;

use GrupoAwamotos\AbandonedCart\Model\ResourceModel\AbandonedCart\CollectionFactory;
use Psr\Log\LoggerInterface;

class CleanupOldRecords
{
    private CollectionFactory $collectionFactory;
    private LoggerInterface $logger;

    public function __construct(
        CollectionFactory $collectionFactory,
        LoggerInterface $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        $this->logger->info('[AbandonedCart] Starting cleanup cron');

        // Remover registros mais antigos que 90 dias
        $cutoffDate = date('Y-m-d H:i:s', strtotime('-90 days'));

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('created_at', ['lteq' => $cutoffDate]);

        $count = $collection->getSize();

        if ($count > 0) {
            foreach ($collection as $item) {
                $item->delete();
            }
            $this->logger->info(sprintf('[AbandonedCart] Cleaned up %d old records', $count));
        }
    }
}
