<?php
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Block\Product;

use GrupoAwamotos\B2B\Helper\Config;
use GrupoAwamotos\ERPIntegration\Model\CustomerPriceProvider;
use GrupoAwamotos\ERPIntegration\Model\ResourceModel\SyncLog as SyncLogResource;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Shows customer-specific price comparison on PDP.
 *
 * Displays:
 * - Customer's exclusive price from their ERP price list
 * - Base price (Magento catalog / NACIONAL list)
 * - Savings percentage
 * - Price list name
 */
class CustomerPriceInfo extends Template
{
    private CustomerSession $customerSession;
    private Config $config;
    private SyncLogResource $syncLogResource;
    private CustomerPriceProvider $customerPriceProvider;
    private CustomerRepositoryInterface $customerRepository;
    private Registry $registry;

    private ?array $priceData = null;
    private bool $resolved = false;

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        Config $config,
        SyncLogResource $syncLogResource,
        CustomerPriceProvider $customerPriceProvider,
        CustomerRepositoryInterface $customerRepository,
        Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
        $this->config = $config;
        $this->syncLogResource = $syncLogResource;
        $this->customerPriceProvider = $customerPriceProvider;
        $this->customerRepository = $customerRepository;
        $this->registry = $registry;
    }

    /**
     * Get customer price comparison data for current product
     *
     * @return array|null ['customer_price', 'base_price', 'savings_pct', 'price_list_name'] or null
     */
    public function getCustomerPriceData(): ?array
    {
        if ($this->resolved) {
            return $this->priceData;
        }
        $this->resolved = true;

        if (!$this->config->isEnabled() || !$this->customerSession->isLoggedIn()) {
            return null;
        }

        try {
            $customerId = (int) $this->customerSession->getCustomerId();

            // Check approval
            $customer = $this->customerRepository->getById($customerId);
            $attr = $customer->getCustomAttribute('b2b_approval_status');
            if (!$attr || $attr->getValue() !== 'approved') {
                return null;
            }

            // Get ERP code
            $erpCodeStr = $this->syncLogResource->getErpCodeByMagentoId('customer', $customerId);
            if ($erpCodeStr === null || !is_numeric($erpCodeStr)) {
                return null;
            }
            $erpCode = (int) $erpCodeStr;

            // Get current product
            $product = $this->registry->registry('current_product');
            if (!$product) {
                return null;
            }
            $sku = $product->getSku();

            // Get customer price from their ERP list
            $customerPrice = $this->customerPriceProvider->getCustomerPrice($erpCode, $sku);
            if ($customerPrice === null || $customerPrice <= 0) {
                return null;
            }

            // Base price is what Magento has (NACIONAL list, synced by PriceSync)
            $basePrice = (float) $product->getData('price'); // raw attribute, not plugin-modified
            if ($basePrice <= 0.01) {
                return null;
            }

            // Calculate savings (positive = customer benefits)
            $savingsPct = $basePrice > 0.01
                ? (($basePrice - $customerPrice) / $basePrice) * 100
                : 0;

            // Only show comparison box when customer actually gets a discount.
            // If ERP price >= base price, the Magento price box already shows the
            // correct ERP price (via GroupPricePlugin) — no need for redundant display.
            if ($savingsPct < 0.1) {
                return null;
            }

            $listName = $this->customerPriceProvider->getCustomerPriceListName($erpCode);

            $this->priceData = [
                'customer_price' => $customerPrice,
                'base_price' => $basePrice,
                'savings_pct' => round($savingsPct, 1),
                'price_list_name' => $listName,
            ];

            return $this->priceData;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format price in BRL
     */
    public function formatPrice(float $price): string
    {
        return 'R$ ' . number_format($price, 2, ',', '.');
    }
}
