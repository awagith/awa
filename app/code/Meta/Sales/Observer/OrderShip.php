<?php

declare(strict_types=1);

namespace Meta\Sales\Observer;

use JsonException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Shipment;
use Meta\BusinessExtension\Api\SystemConfigInterface;
use Meta\BusinessExtension\Helper\FBEHelper;
use Psr\Log\LoggerInterface;

/**
 * Observer to send shipment data to Meta Commerce
 */
class OrderShip implements ObserverInterface
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
            /** @var Shipment $shipment */
            $shipment = $observer->getEvent()->getData('shipment');
            if (!$shipment) {
                return;
            }

            // sales_order_shipment_save_after may fire again on edits; sync only on creation.
            if ($shipment->getId() && $shipment->getOrigData('entity_id') !== null) {
                return;
            }

            $order = $shipment->getOrder();
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

            $tracks = $shipment->getTracks();

            $trackingData = [];
            foreach ($tracks as $track) {
                $trackingNumber = trim((string) $track->getTrackNumber());
                $carrier = trim((string) $track->getTitle());
                if ($trackingNumber === '' && $carrier === '') {
                    continue;
                }

                $trackingData[] = [
                    'tracking_number' => $trackingNumber,
                    'carrier' => $carrier
                ];
            }

            $shipmentData = [
                'order_id' => $order->getIncrementId(),
                'order_status' => 'SHIPPED',
                'tracking_info' => json_encode(
                    $trackingData,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            ];

            $endpoint = $commerceAccountId . '/orders';
            $result = $this->fbeHelper->apiPost($endpoint, $shipmentData, $storeId);
            if (isset($result['error'])) {
                $this->logger->warning('[Meta Sales] OrderShip API error', [
                    'store_id' => $storeId,
                    'order_id' => $order->getIncrementId(),
                    'http_status' => $result['http_status'] ?? null,
                    'error' => $result['error']
                ]);
            }

            $this->logger->info('[Meta Sales] Order shipped', [
                'store_id' => $storeId,
                'order_id' => $order->getIncrementId()
            ]);
        } catch (JsonException $e) {
            $this->logger->error('[Meta Sales] OrderShip payload encode failed', [
                'error' => $e->getMessage()
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[Meta Sales] OrderShip failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
