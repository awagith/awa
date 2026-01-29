<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use GrupoAwamotos\ERPIntegration\Api\OrderSyncInterface;
use GrupoAwamotos\ERPIntegration\Helper\Data as Helper;
use Psr\Log\LoggerInterface;

class OrderPlaceAfter implements ObserverInterface
{
    private OrderSyncInterface $orderSync;
    private Helper $helper;
    private LoggerInterface $logger;

    public function __construct(
        OrderSyncInterface $orderSync,
        Helper $helper,
        LoggerInterface $logger
    ) {
        $this->orderSync = $orderSync;
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
            if ($order) {
                $result = $this->orderSync->sendOrder($order);
                if ($result['success']) {
                    $order->addCommentToStatusHistory(
                        __('Pedido enviado ao ERP. ID ERP: %1', $result['erp_order_id'])
                    );
                } else {
                    $this->logger->warning('[ERP] Order not sent to ERP: ' . $result['message']);
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Observer OrderPlaceAfter error: ' . $e->getMessage());
        }
    }
}
