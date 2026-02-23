<?php
/**
 * Observer para copiar PO Number do Quote para Order
 * P0-1: Purchase Order Number
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class CopyPoNumberToOrder implements ObserverInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Event: sales_model_service_quote_submit_before
     * Copia b2b_po_number do quote para o order antes de salvar
     */
    public function execute(Observer $observer): void
    {
        try {
            /** @var \Magento\Quote\Model\Quote $quote */
            $quote = $observer->getEvent()->getQuote();

            /** @var \Magento\Sales\Model\Order $order */
            $order = $observer->getEvent()->getOrder();

            $poNumber = $quote->getData('b2b_po_number');

            if (!empty($poNumber)) {
                $order->setData('b2b_po_number', $poNumber);

                $this->logger->info('[B2B] PO Number copiado para order', [
                    'order_increment_id' => $order->getIncrementId(),
                    'po_number' => $poNumber
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->error('[B2B] Erro ao copiar PO Number para order: ' . $e->getMessage());
        }
    }
}
