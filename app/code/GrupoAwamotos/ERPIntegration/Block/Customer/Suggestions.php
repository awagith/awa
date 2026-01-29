<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Block\Customer;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session as CustomerSession;
use GrupoAwamotos\ERPIntegration\Model\PurchaseHistory;
use GrupoAwamotos\ERPIntegration\Model\ProductSuggestion;
use GrupoAwamotos\ERPIntegration\Helper\Data as Helper;

/**
 * Customer Product Suggestions Block
 */
class Suggestions extends Template
{
    protected $_template = 'GrupoAwamotos_ERPIntegration::customer/suggestions.phtml';

    private CustomerSession $customerSession;
    private PurchaseHistory $purchaseHistory;
    private ProductSuggestion $productSuggestion;
    private Helper $helper;
    private ?int $erpCustomerCode = null;

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        PurchaseHistory $purchaseHistory,
        ProductSuggestion $productSuggestion,
        Helper $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
        $this->purchaseHistory = $purchaseHistory;
        $this->productSuggestion = $productSuggestion;
        $this->helper = $helper;
    }

    /**
     * Check if suggestions are enabled
     */
    public function isEnabled(): bool
    {
        return $this->helper->isEnabled() && $this->helper->isSuggestionsEnabled();
    }

    /**
     * Check if customer is logged in
     */
    public function isLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * Get ERP customer code for logged in customer
     */
    public function getErpCustomerCode(): ?int
    {
        if ($this->erpCustomerCode !== null) {
            return $this->erpCustomerCode;
        }

        if (!$this->isLoggedIn()) {
            return null;
        }

        $customer = $this->customerSession->getCustomer();

        // Try to get CNPJ from B2B attribute
        $cnpj = $customer->getData('b2b_cnpj');

        // Fallback to taxvat
        if (empty($cnpj)) {
            $cnpj = $customer->getTaxvat();
        }

        if (!empty($cnpj)) {
            $this->erpCustomerCode = $this->purchaseHistory->getCustomerCodeByCnpj($cnpj);
        }

        return $this->erpCustomerCode;
    }

    /**
     * Get customer info from ERP
     */
    public function getErpCustomerInfo(): ?array
    {
        $customerCode = $this->getErpCustomerCode();

        if (!$customerCode) {
            return null;
        }

        return $this->purchaseHistory->getCustomerInfo($customerCode);
    }

    /**
     * Get purchase summary
     */
    public function getPurchaseSummary(): array
    {
        $customerCode = $this->getErpCustomerCode();

        if (!$customerCode) {
            return [];
        }

        return $this->purchaseHistory->getCustomerSummary($customerCode);
    }

    /**
     * Get most purchased products
     */
    public function getMostPurchasedProducts(int $limit = 10): array
    {
        $customerCode = $this->getErpCustomerCode();

        if (!$customerCode) {
            return [];
        }

        return $this->purchaseHistory->getMostPurchasedProducts($customerCode, $limit);
    }

    /**
     * Get product suggestions
     */
    public function getSuggestions(int $limit = 10): array
    {
        $customerCode = $this->getErpCustomerCode();

        if (!$customerCode) {
            return [];
        }

        return $this->productSuggestion->getSuggestions($customerCode, $limit);
    }

    /**
     * Get reorder suggestions
     */
    public function getReorderSuggestions(int $limit = 10): array
    {
        $customerCode = $this->getErpCustomerCode();

        if (!$customerCode) {
            return [];
        }

        return $this->productSuggestion->getReorderSuggestions($customerCode, $limit);
    }

    /**
     * Get trending products
     */
    public function getTrendingProducts(int $limit = 10): array
    {
        return $this->productSuggestion->getTrendingProducts($limit);
    }

    /**
     * Get last orders
     */
    public function getLastOrders(int $limit = 5): array
    {
        $customerCode = $this->getErpCustomerCode();

        if (!$customerCode) {
            return [];
        }

        return $this->purchaseHistory->getLastOrders($customerCode, $limit);
    }

    /**
     * Format price
     */
    public function formatPrice(float $price): string
    {
        return 'R$ ' . number_format($price, 2, ',', '.');
    }

    /**
     * Format date
     */
    public function formatDate(?string $date): string
    {
        if (empty($date)) {
            return '-';
        }

        try {
            $datetime = new \DateTime($date);
            return $datetime->format('d/m/Y');
        } catch (\Exception $e) {
            return substr($date, 0, 10);
        }
    }

    /**
     * Get AJAX URL for suggestions
     */
    public function getAjaxUrl(): string
    {
        return $this->getUrl('erpintegration/customer/suggestions');
    }

    /**
     * Get product URL
     */
    public function getProductUrl(array $product): string
    {
        if (!empty($product['magento']['url_key'])) {
            return $this->getUrl($product['magento']['url_key'] . '.html');
        }

        // Search by SKU
        return $this->getUrl('catalogsearch/result', ['q' => $product['codigo_material']]);
    }

    /**
     * Check if product is available in store
     */
    public function isProductAvailable(array $product): bool
    {
        return !empty($product['available_in_store']) &&
               !empty($product['magento']['in_stock']);
    }

    /**
     * Get status label
     */
    public function getStatusLabel(string $status): string
    {
        return match($status) {
            'F' => 'Faturado',
            'A' => 'Aberto',
            'P' => 'Pendente',
            'C' => 'Cancelado',
            'X' => 'Excluído',
            default => $status,
        };
    }

    /**
     * Get status CSS class
     */
    public function getStatusClass(string $status): string
    {
        return match($status) {
            'F' => 'status-success',
            'A' => 'status-info',
            'P' => 'status-warning',
            'C', 'X' => 'status-error',
            default => '',
        };
    }
}
