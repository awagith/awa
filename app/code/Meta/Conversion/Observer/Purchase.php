<?php

declare(strict_types=1);

namespace Meta\Conversion\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Meta\BusinessExtension\Api\SystemConfigInterface;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Meta\Conversion\Helper\UserDataBuilder;
use Psr\Log\LoggerInterface;

/**
 * Observer to send Purchase events via Conversions API
 */
class Purchase implements ObserverInterface
{
    private const SESSION_KEY_LAST_ORDER_ID = 'meta_last_purchase_order_id';

    public function __construct(
        private readonly SystemConfigInterface $config,
        private readonly GraphAPIAdapter $graphApi,
        private readonly \Magento\Checkout\Model\Session $checkoutSession,
        private readonly LoggerInterface $logger,
        private readonly ?UserDataBuilder $userDataBuilder = null
    ) {
    }

    public function execute(Observer $observer): void
    {
        try {
            /** @var Order $order */
            $order = $observer->getEvent()->getData('order');
            if (!$order instanceof Order || !$order->getId()) {
                $orders = $observer->getEvent()->getData('orders');
                if (is_array($orders)) {
                    foreach ($orders as $candidateOrder) {
                        if ($candidateOrder instanceof Order && $candidateOrder->getId()) {
                            $order = $candidateOrder;
                            break;
                        }
                    }
                }
            }

            if (!$order instanceof Order || !$order->getId()) {
                $order = $this->checkoutSession->getLastRealOrder();
            }
            if (!$order || !$order->getId()) {
                return;
            }

            $orderReference = (string) ($order->getIncrementId() ?: $order->getId());
            $lastOrderReference = (string) ($this->checkoutSession->getData(self::SESSION_KEY_LAST_ORDER_ID) ?: '');
            if ($orderReference !== '' && hash_equals($lastOrderReference, $orderReference)) {
                return;
            }

            $storeId = $order->getStoreId() !== null ? (int) $order->getStoreId() : null;
            if (!$this->config->isActive($storeId)) {
                return;
            }

            $pixelId = $this->config->getPixelId($storeId);
            if ($pixelId === null) {
                return;
            }

            $contentIds = [];
            $contents = [];

            foreach ($order->getAllVisibleItems() as $item) {
                $contentIds[] = $item->getSku();
                $contents[] = [
                    'id' => $item->getSku(),
                    'quantity' => (int) $item->getQtyOrdered()
                ];
            }

            if ($contents === []) {
                return;
            }

            $externalId = (string) ($order->getCustomerId() ?: ($order->getIncrementId() ?: $order->getId()));
            $userData = $this->userDataBuilder
                ? $this->userDataBuilder->build(
                    (string) ($order->getCustomerEmail() ?: ''),
                    (string) ($order->getBillingAddress()?->getTelephone() ?: ''),
                    $externalId
                )
                : [];
            $eventSourceUrl = $this->userDataBuilder?->getEventSourceUrl();

            $currency = (string) ($order->getOrderCurrencyCode() ?: $order->getBaseCurrencyCode() ?: 'BRL');
            $eventTime = time();
            $eventId = sprintf('purchase-%s', (string) ($order->getIncrementId() ?: $order->getId()));

            $event = [
                'event_name' => 'Purchase',
                'event_time' => $eventTime,
                'event_id' => $eventId,
                'action_source' => 'website',
                'user_data' => $userData,
                'custom_data' => [
                    'content_ids' => $contentIds,
                    'content_type' => 'product',
                    'contents' => $contents,
                    'num_items' => count($contents),
                    'value' => (float) $order->getGrandTotal(),
                    'currency' => $currency,
                    'order_id' => $order->getIncrementId()
                ]
            ];
            if ($eventSourceUrl !== null) {
                $event['event_source_url'] = $eventSourceUrl;
            }

            $eventData = [$event];

            $result = $this->graphApi->sendEvents($pixelId, $eventData, $storeId);
            if ($orderReference !== '') {
                $this->checkoutSession->setData(self::SESSION_KEY_LAST_ORDER_ID, $orderReference);
            }
            if (isset($result['error'])) {
                $this->logger->warning('[Meta CAPI] Purchase API error', [
                    'store_id' => $storeId,
                    'order_id' => $order->getIncrementId(),
                    'http_status' => $result['http_status'] ?? null,
                    'error' => $result['error']
                ]);
            }
        } catch (\Throwable $e) {
            $this->logger->error('[Meta CAPI] Purchase event failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
