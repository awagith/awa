<?php

declare(strict_types=1);

namespace Meta\Sales\Observer;

use JsonException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Meta\BusinessExtension\Api\SystemConfigInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Psr\Log\LoggerInterface;

/**
 * Observer to send refund data to Meta Commerce
 */
class OrderRefund implements ObserverInterface
{
    public function __construct(
        private readonly SystemConfigInterface $config,
        private readonly FBEHelper $fbeHelper,
        private readonly LoggerInterface $logger
    ) {
    }

    public function execute(Observer $observer): void
    {
        try {
            /** @var Creditmemo $creditmemo */
            $creditmemo = $observer->getEvent()->getData('creditmemo');
            if (!$creditmemo) {
                return;
            }

            // sales_order_creditmemo_save_after may run on subsequent updates; sync only on first persistence.
            if ($creditmemo->getId() && $creditmemo->getOrigData('entity_id') !== null) {
                return;
            }

            $order = $creditmemo->getOrder();
            if (!$order || !$order->getIncrementId()) {
                return;
            }
            $storeId = $order && $order->getStoreId() !== null ? (int) $order->getStoreId() : null;
            if (!$this->config->isActive($storeId)) {
                return;
            }

            $commerceAccountId = $this->config->getCommerceAccountId($storeId);
            if ($commerceAccountId === null) {
                return;
            }

            $currency = (string) ($order->getOrderCurrencyCode() ?: $order->getBaseCurrencyCode() ?: 'BRL');

            $refundItems = [];
            foreach ($creditmemo->getAllItems() as $item) {
                if ($item->getQty() > 0) {
                    $sku = trim((string) $item->getSku());
                    if ($sku === '') {
                        continue;
                    }

                    $refundItems[] = [
                        'retailer_id' => $sku,
                        'quantity' => (int) $item->getQty()
                    ];
                }
            }

            $refundData = [
                'order_id' => $order->getIncrementId(),
                'order_status' => 'REFUNDED',
                'items' => json_encode($refundItems, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'refund_amount' => json_encode(
                    [
                        'amount' => number_format((float) $creditmemo->getGrandTotal(), 2, '.', ''),
                        'currency' => $currency
                    ],
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            ];

            $endpoint = $commerceAccountId . '/orders';
            $result = $this->fbeHelper->apiPost($endpoint, $refundData, $storeId);
            if (isset($result['error'])) {
                $this->logger->warning('[Meta Sales] OrderRefund API error', [
                    'store_id' => $storeId,
                    'order_id' => $order->getIncrementId(),
                    'http_status' => $result['http_status'] ?? null,
                    'error' => $result['error']
                ]);
            }

            $this->logger->info('[Meta Sales] Order refunded', [
                'store_id' => $storeId,
                'order_id' => $order->getIncrementId(),
                'amount' => $creditmemo->getGrandTotal()
            ]);
        } catch (JsonException $e) {
            $this->logger->error('[Meta Sales] OrderRefund payload encode failed', [
                'error' => $e->getMessage()
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Meta Sales] OrderRefund failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
