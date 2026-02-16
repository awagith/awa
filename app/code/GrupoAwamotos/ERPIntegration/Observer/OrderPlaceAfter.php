<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use GrupoAwamotos\ERPIntegration\Api\OrderSyncInterface;
use GrupoAwamotos\ERPIntegration\Model\Queue\OrderSyncPublisher;
use GrupoAwamotos\ERPIntegration\Helper\Data as Helper;
use Psr\Log\LoggerInterface;

/**
 * Observer for order placement
 *
 * Sends orders to ERP either synchronously or via message queue.
 * Queue mode (default): More resilient, non-blocking for checkout
 * Sync mode: Immediate feedback but can slow checkout if ERP is slow
 */
class OrderPlaceAfter implements ObserverInterface
{
    private OrderSyncInterface $orderSync;
    private OrderSyncPublisher $queuePublisher;
    private Helper $helper;
    private LoggerInterface $logger;

    public function __construct(
        OrderSyncInterface $orderSync,
        OrderSyncPublisher $queuePublisher,
        Helper $helper,
        LoggerInterface $logger
    ) {
        $this->orderSync = $orderSync;
        $this->queuePublisher = $queuePublisher;
        $this->helper = $helper;
        $this->logger = $logger;
    }

    public function execute(Observer $observer): void
    {
        if (!$this->helper->isOrderSyncEnabled() || !$this->helper->sendOrderOnPlace()) {
            return;
        }

        try {
            $order = $observer->getEvent()->getOrder();
            if (!$order) {
                return;
            }

            // Check if async mode is enabled (default: true for resilience)
            if ($this->helper->isOrderQueueEnabled()) {
                // Async mode: publish to queue
                $this->publishToQueue($order);
            } else {
                // Sync mode: send directly (legacy behavior)
                $this->sendDirectly($order);
            }
        } catch (\Exception $e) {
            // Never fail the order placement due to ERP sync issues
            $this->logger->error('[ERP] Observer OrderPlaceAfter error: ' . $e->getMessage(), [
                'order_id' => $order ? $order->getEntityId() : null,
                'increment_id' => $order ? $order->getIncrementId() : null,
            ]);
        }
    }

    /**
     * Publish order to message queue for async processing
     */
    private function publishToQueue($order): void
    {
        $published = $this->queuePublisher->publish($order);

        if ($published) {
            $this->logger->info('[ERP] Order queued for ERP sync', [
                'order_id' => $order->getEntityId(),
                'increment_id' => $order->getIncrementId(),
            ]);

            // Add comment to order
            $order->addCommentToStatusHistory(
                __('Pedido enviado para fila de sincronização com ERP.')
            );
        } else {
            // Queue failed, try direct sync as fallback
            $this->logger->warning('[ERP] Queue failed, trying direct sync as fallback', [
                'order_id' => $order->getEntityId(),
            ]);
            $this->sendDirectly($order);
        }
    }

    /**
     * Send order directly to ERP (synchronous)
     */
    private function sendDirectly($order): void
    {
        $result = $this->orderSync->sendOrder($order);

        if ($result['success']) {
            $order->addCommentToStatusHistory(
                __('Pedido enviado ao ERP. ID ERP: %1', $result['erp_order_id'])
            );
            $this->logger->info('[ERP] Order sent to ERP synchronously', [
                'order_id' => $order->getEntityId(),
                'erp_order_id' => $result['erp_order_id'],
            ]);
        } else {
            $this->logger->warning('[ERP] Order not sent to ERP: ' . $result['message'], [
                'order_id' => $order->getEntityId(),
            ]);

            // If direct sync fails, try queue as fallback
            if ($this->helper->isOrderQueueEnabled()) {
                $this->logger->info('[ERP] Direct sync failed, queueing for retry', [
                    'order_id' => $order->getEntityId(),
                ]);
                $this->queuePublisher->publish($order);
            }
        }
    }
}
