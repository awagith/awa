<?php
/**
 * Cron job para expirar cotações vencidas automaticamente
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Cron;

use GrupoAwamotos\B2B\Model\ResourceModel\QuoteRequest\CollectionFactory;
use GrupoAwamotos\B2B\Helper\Config;
use Psr\Log\LoggerInterface;

class ExpireQuotes
{
    private CollectionFactory $collectionFactory;
    private Config $config;
    private LoggerInterface $logger;

    public function __construct(
        CollectionFactory $collectionFactory,
        Config $config,
        LoggerInterface $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        if (!$this->config->isEnabled() || !$this->config->isQuoteEnabled()) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        // Expirar cotações com expires_at no passado
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', ['in' => ['pending', 'processing', 'quoted']])
            ->addFieldToFilter('expires_at', ['notnull' => true])
            ->addFieldToFilter('expires_at', ['lt' => $now]);

        $count = 0;
        foreach ($collection as $quote) {
            try {
                $quote->setStatus('expired');
                $quote->save();
                $count++;
            } catch (\Exception $e) {
                $this->logger->error(
                    'B2B Quote Expiry Error: ' . $e->getMessage(),
                    ['quote_id' => $quote->getId()]
                );
            }
        }

        // Expirar cotações sem expires_at mas com dias configurados
        $expiryDays = $this->config->getQuoteExpiryDays();
        if ($expiryDays > 0) {
            $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$expiryDays} days"));

            $collection = $this->collectionFactory->create();
            $collection->addFieldToFilter('status', ['in' => ['pending', 'processing', 'quoted']])
                ->addFieldToFilter(
                    ['expires_at'],
                    [['null' => true]]
                )
                ->addFieldToFilter('created_at', ['lt' => $cutoffDate]);

            foreach ($collection as $quote) {
                try {
                    $quote->setStatus('expired');
                    $quote->save();
                    $count++;
                } catch (\Exception $e) {
                    $this->logger->error(
                        'B2B Quote Expiry Error: ' . $e->getMessage(),
                        ['quote_id' => $quote->getId()]
                    );
                }
            }
        }

        if ($count > 0) {
            $this->logger->info("B2B: {$count} cotação(ões) expirada(s) automaticamente.");
        }
    }
}
