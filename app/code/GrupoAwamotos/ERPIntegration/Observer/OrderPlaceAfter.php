<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use GrupoAwamotos\ERPIntegration\Helper\Data as Helper;
use GrupoAwamotos\ERPIntegration\Model\ResourceModel\SyncLog as SyncLogResource;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Observer for order placement - PULL mode
 *
 * In PULL mode, the ERP fetches orders via REST API (GET /V1/erp/orders/pending).
 * This observer logs the order and stamps the customer's ERP code directly on
 * the sales_order record, so ERP SECTRA can read it regardless of import method.
 */
class OrderPlaceAfter implements ObserverInterface
{
    private Helper $helper;
    private SyncLogResource $syncLogResource;
    private CustomerRepositoryInterface $customerRepository;
    private LoggerInterface $logger;

    public function __construct(
        Helper $helper,
        SyncLogResource $syncLogResource,
        CustomerRepositoryInterface $customerRepository,
        LoggerInterface $logger
    ) {
        $this->helper = $helper;
        $this->syncLogResource = $syncLogResource;
        $this->customerRepository = $customerRepository;
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

            // Stamp customer ERP code directly on the order
            $erpCode = $this->resolveCustomerErpCode($order);
            if ($erpCode) {
                $order->setData('customer_erp_code', (string) $erpCode);
            }

            // Log that order is available for ERP pull
            $this->syncLogResource->addLog(
                'order_pull',
                'export',
                'pending',
                sprintf(
                    'Pedido %s disponível para ERP via API Pull. Cliente: %s (ERP: %s)',
                    $order->getIncrementId(),
                    $order->getCustomerTaxvat() ?: $order->getCustomerEmail(),
                    $erpCode ?: 'N/A'
                ),
                null,
                (int) $order->getEntityId()
            );

            $order->addCommentToStatusHistory(
                __('Pedido disponível para sincronização com ERP via API. Código ERP cliente: %1', $erpCode ?: 'N/A')
            );

            $this->logger->info('[ERP] Order available for ERP pull', [
                'order_id' => $order->getEntityId(),
                'increment_id' => $order->getIncrementId(),
                'customer_erp_code' => $erpCode,
            ]);
        } catch (\Exception $e) {
            // Never fail the order placement due to ERP logging issues
            $this->logger->error('[ERP] Observer OrderPlaceAfter error: ' . $e->getMessage(), [
                'order_id' => isset($order) ? $order->getEntityId() : null,
            ]);
        }
    }

    /**
     * Resolve ERP code: customer attribute (primary) -> entity_map (fallback)
     */
    private function resolveCustomerErpCode($order): ?int
    {
        $customerId = $order->getCustomerId();
        if (!$customerId) {
            return null;
        }

        try {
            // Primary: erp_code customer attribute
            $customer = $this->customerRepository->getById((int) $customerId);
            $attr = $customer->getCustomAttribute('erp_code');
            if ($attr && $attr->getValue() && is_numeric($attr->getValue())) {
                return (int) $attr->getValue();
            }
        } catch (\Exception $e) {
            $this->logger->debug('[ERP] Could not load customer attribute: ' . $e->getMessage());
        }

        // Fallback: entity_map
        $erpCode = $this->syncLogResource->getErpCodeByMagentoId('customer', (int) $customerId);
        if ($erpCode && is_numeric($erpCode)) {
            return (int) $erpCode;
        }

        return null;
    }
}
