<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use GrupoAwamotos\ERPIntegration\Helper\Data as Helper;
use GrupoAwamotos\ERPIntegration\Model\ResourceModel\SyncLog as SyncLogResource;
use Psr\Log\LoggerInterface;

/**
 * Observer for order placement - PULL mode
 *
 * In PULL mode, the ERP fetches orders via REST API (GET /V1/erp/orders/pending).
 * This observer only logs the order for tracking. No INSERT into ERP database.
 * The ERP SQL user (Consulta) has SELECT-only permission.
 */
class OrderPlaceAfter implements ObserverInterface
{
    private Helper $helper;
    private SyncLogResource $syncLogResource;
    private LoggerInterface $logger;

    public function __construct(
        Helper $helper,
        SyncLogResource $syncLogResource,
        LoggerInterface $logger
    ) {
        $this->helper = $helper;
        $this->syncLogResource = $syncLogResource;
        $this->logger = $logger;
    }

    public function execute(Observer $observer): void
    {
        if (!$this->helper->isOrderSyncEnabled()) {
            return;
        }

        try {
            $order = $observer->getEvent()->getOrder();
            if (!$order) {
                return;
            }

            // Log that order is available for ERP pull
            $this->syncLogResource->addLog(
                'order_pull',
                'export',
                'pending',
                sprintf(
                    'Pedido %s disponível para ERP via API Pull. Cliente: %s',
                    $order->getIncrementId(),
                    $order->getCustomerTaxvat() ?: $order->getCustomerEmail()
                ),
                null,
                (int) $order->getEntityId()
            );

            $order->addCommentToStatusHistory(
                __('Pedido disponível para sincronização com ERP via API.')
            );

            $this->logger->info('[ERP] Order available for ERP pull', [
                'order_id' => $order->getEntityId(),
                'increment_id' => $order->getIncrementId(),
            ]);
        } catch (\Exception $e) {
            // Never fail the order placement due to ERP logging issues
            $this->logger->error('[ERP] Observer OrderPlaceAfter error: ' . $e->getMessage(), [
                'order_id' => isset($order) ? $order->getEntityId() : null,
            ]);
        }
    }
}
