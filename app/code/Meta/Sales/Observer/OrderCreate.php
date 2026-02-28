<?php

declare(strict_types=1);

namespace Meta\Sales\Observer;

use JsonException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Meta\BusinessExtension\Api\SystemConfigInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Psr\Log\LoggerInterface;

/**
 * Observer to send order creation data to Meta Commerce
 */
class OrderCreate implements ObserverInterface
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
            /** @var Order $order */
            $order = $observer->getEvent()->getData('order');
            if (!$order) {
                return;
            }

            $storeId = $order->getStoreId() !== null ? (int) $order->getStoreId() : null;
            if (!$this->config->isActive($storeId)) {
                return;
            }

            $commerceAccountId = $this->config->getCommerceAccountId($storeId);
            if ($commerceAccountId === null) {
                return;
            }

            $currency = (string) ($order->getOrderCurrencyCode() ?: $order->getBaseCurrencyCode() ?: 'BRL');
            $items = [];
            foreach ($order->getAllVisibleItems() as $item) {
                $sku = trim((string) $item->getSku());
                if ($sku === '') {
                    continue;
                }

                $items[] = [
                    'retailer_id' => $sku,
                    'quantity' => (int) $item->getQtyOrdered(),
                    'price_per_unit' => [
                        'amount' => number_format((float) $item->getPrice(), 2, '.', ''),
                        'currency' => $currency
                    ]
                ];
            }

            $estimatedPayment = [
                'subtotal' => [
                    'amount' => number_format((float) $order->getSubtotal(), 2, '.', ''),
                    'currency' => $currency
                ],
                'total' => [
                    'amount' => number_format((float) $order->getGrandTotal(), 2, '.', ''),
                    'currency' => $currency
                ],
                'shipping' => [
                    'amount' => number_format((float) $order->getShippingAmount(), 2, '.', ''),
                    'currency' => $currency
                ]
            ];

            $orderData = [
                'id' => $order->getIncrementId(),
                'buyer_details' => [
                    'email' => $order->getCustomerEmail()
                ],
                'order_status' => 'CREATED',
                'items' => json_encode($items, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'estimated_payment_details' => json_encode(
                    $estimatedPayment,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            ];

            $endpoint = $commerceAccountId . '/orders';
            $result = $this->fbeHelper->apiPost($endpoint, $orderData, $storeId);
            if (isset($result['error'])) {
                $this->logger->warning('[Meta Sales] OrderCreate API error', [
                    'store_id' => $storeId,
                    'order_id' => $order->getIncrementId(),
                    'http_status' => $result['http_status'] ?? null,
                    'error' => $result['error']
                ]);
            }

            $this->logger->info('[Meta Sales] Order created', [
                'store_id' => $storeId,
                'order_id' => $order->getIncrementId()
            ]);
        } catch (JsonException $e) {
            $this->logger->error('[Meta Sales] OrderCreate payload encode failed', [
                'error' => $e->getMessage()
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Meta Sales] OrderCreate failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
