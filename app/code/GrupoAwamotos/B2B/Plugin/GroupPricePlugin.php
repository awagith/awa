<?php
/**
 * Plugin para aplicar desconto automático por grupo de cliente B2B
 * 
 * Aplica descontos configurados para grupos:
 * - B2B Atacado: desconto configurável (padrão 15%)
 * - B2B VIP: desconto configurável (padrão 20%)
 * - B2B Revendedor: desconto configurável (padrão 10%)
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Plugin;

use GrupoAwamotos\B2B\Helper\Config;
use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Pricing\SaleableInterface;

class GroupPricePlugin
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var array
     */
    private $processedProducts = [];

    public function __construct(
        Config $config,
        CustomerSession $customerSession
    ) {
        $this->config = $config;
        $this->customerSession = $customerSession;
    }

    /**
     * Apply B2B group discount to product price
     *
     * @param Product $subject
     * @param float $result
     * @return float
     */
    public function afterGetPrice(Product $subject, $result)
    {
        if (!$this->config->isEnabled()) {
            return $result;
        }

        if (!$this->customerSession->isLoggedIn()) {
            return $result;
        }

        // Evitar processamento duplicado
        $productId = $subject->getId();
        if (isset($this->processedProducts[$productId])) {
            return $this->processedProducts[$productId];
        }

        $discount = $this->getGroupDiscount();
        
        if ($discount > 0 && $result > 0) {
            $discountedPrice = $result * (1 - ($discount / 100));
            $this->processedProducts[$productId] = $discountedPrice;
            return $discountedPrice;
        }

        return $result;
    }

    /**
     * Apply B2B group discount to final price
     *
     * @param Product $subject
     * @param float $result
     * @return float
     */
    public function afterGetFinalPrice(Product $subject, $result)
    {
        if (!$this->config->isEnabled()) {
            return $result;
        }

        if (!$this->customerSession->isLoggedIn()) {
            return $result;
        }

        $discount = $this->getGroupDiscount();
        
        if ($discount > 0 && $result > 0) {
            return $result * (1 - ($discount / 100));
        }

        return $result;
    }

    /**
     * Get discount percentage for current customer group
     *
     * @return float
     */
    private function getGroupDiscount(): float
    {
        $customerGroupId = (int) $this->customerSession->getCustomerGroupId();
        
        // Verificar se cliente está aprovado
        $customer = $this->customerSession->getCustomer();
        $approvalStatus = $customer->getData('b2b_approval_status');
        
        if ($approvalStatus !== 'approved') {
            return 0.0;
        }

        // Verificar grupo e retornar desconto correspondente
        $wholesaleGroupId = $this->config->getWholesaleGroupId();
        $vipGroupId = $this->config->getVipGroupId();
        
        if ($customerGroupId === $wholesaleGroupId) {
            return $this->config->getWholesaleDiscount();
        }
        
        if ($customerGroupId === $vipGroupId) {
            return $this->config->getVipDiscount();
        }
        
        // Verificar grupos B2B criados pelo módulo (IDs 4, 5, 6)
        $b2bGroups = [
            4 => 15.0, // B2B Atacado
            5 => 20.0, // B2B VIP
            6 => 10.0, // B2B Revendedor
        ];
        
        if (isset($b2bGroups[$customerGroupId])) {
            return $b2bGroups[$customerGroupId];
        }

        return 0.0;
    }
}
