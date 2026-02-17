<?php
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Plugin;

use GrupoAwamotos\B2B\Helper\Config;
use GrupoAwamotos\ERPIntegration\Model\CustomerPriceProvider;
use GrupoAwamotos\ERPIntegration\Model\ResourceModel\SyncLog as SyncLogResource;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Http\Context as HttpContext;

/**
 * Adds the customer's ERP price list code to the HTTP context.
 *
 * This makes Full Page Cache (FPC) vary by price list, ensuring that
 * customers with different ERP price lists see their own prices.
 *
 * Since there are only ~13 active price lists, this creates a manageable
 * number of cache variants (vs N customers which would be unscalable).
 */
class CustomerPriceListContextPlugin
{
    private const CONTEXT_PRICE_LIST = 'erp_price_list';

    private CustomerSession $customerSession;
    private Config $config;
    private SyncLogResource $syncLogResource;
    private CustomerPriceProvider $customerPriceProvider;
    private CustomerRepositoryInterface $customerRepository;

    public function __construct(
        CustomerSession $customerSession,
        Config $config,
        SyncLogResource $syncLogResource,
        CustomerPriceProvider $customerPriceProvider,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->customerSession = $customerSession;
        $this->config = $config;
        $this->syncLogResource = $syncLogResource;
        $this->customerPriceProvider = $customerPriceProvider;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Add ERP price list code to HTTP context for FPC cache variation
     */
    public function beforeGetVaryString(HttpContext $subject): void
    {
        if (!$this->config->isEnabled() || !$this->customerSession->isLoggedIn()) {
            return;
        }

        try {
            $customerId = (int) $this->customerSession->getCustomerId();
            $erpCode = $this->resolveErpCode($customerId);

            if ($erpCode === null) {
                return;
            }

            $priceListCode = $this->customerPriceProvider->getCustomerPriceListCode($erpCode);

            if ($priceListCode !== null) {
                $subject->setValue(
                    self::CONTEXT_PRICE_LIST,
                    (string) $priceListCode,
                    '0' // default for non-logged-in users
                );
            }
        } catch (\Exception $e) {
            // Fail silently - don't break FPC
        }
    }

    /**
     * Resolve ERP code: erp_code attribute (primary) → entity_map (fallback)
     */
    private function resolveErpCode(int $customerId): ?int
    {
        try {
            $customer = $this->customerRepository->getById($customerId);

            // Primary: erp_code attribute (definitive, single value)
            $attr = $customer->getCustomAttribute('erp_code');
            $erpCode = ($attr && $attr->getValue()) ? $attr->getValue() : null;

            // Fallback: entity_map table
            if ($erpCode === null) {
                $erpCode = $this->syncLogResource->getErpCodeByMagentoId('customer', $customerId);
            }

            return ($erpCode !== null && is_numeric($erpCode)) ? (int) $erpCode : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
