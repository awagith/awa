<?php
/**
 * B2B Helper Data
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    /**
     * Config paths
     */
    const XML_PATH_ENABLED = 'grupoawamotos_b2b/general/enabled';
    const XML_PATH_B2B_MODE = 'grupoawamotos_b2b/general/b2b_mode';
    const XML_PATH_HIDE_PRICES = 'grupoawamotos_b2b/price_visibility/hide_price_guests';
    const XML_PATH_REQUIRE_APPROVAL = 'grupoawamotos_b2b/customer_approval/require_approval';
    const XML_PATH_QUOTE_ENABLED = 'grupoawamotos_b2b/quote_request/enabled';
    const XML_PATH_QUOTE_EXPIRY = 'grupoawamotos_b2b/quote_request/expiry_days';
    
    /**
     * B2B Customer Groups
     */
    const GROUP_B2B_ATACADO = 4;
    const GROUP_B2B_VIP = 5;
    const GROUP_B2B_REVENDEDOR = 6;
    const GROUP_B2B_PENDENTE = 7;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var array
     */
    private $b2bGroups = [
        self::GROUP_B2B_ATACADO,
        self::GROUP_B2B_VIP,
        self::GROUP_B2B_REVENDEDOR,
        self::GROUP_B2B_PENDENTE
    ];

    public function __construct(
        Context $context,
        CustomerSession $customerSession
    ) {
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    /**
     * Check if B2B module is enabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get B2B mode
     *
     * @return string
     */
    public function getMode(): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_B2B_MODE,
            ScopeInterface::SCOPE_STORE
        ) ?: 'mixed';
    }

    /**
     * Check if prices should be hidden for guests
     *
     * @return bool
     */
    public function shouldHidePricesForGuests(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_HIDE_PRICES,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if approval is required for B2B customers
     *
     * @return bool
     */
    public function isApprovalRequired(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_REQUIRE_APPROVAL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if quote system is enabled
     *
     * @return bool
     */
    public function isQuoteEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_QUOTE_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get quote expiry days
     *
     * @return int
     */
    public function getQuoteExpiryDays(): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_QUOTE_EXPIRY,
            ScopeInterface::SCOPE_STORE
        ) ?: 7;
    }

    /**
     * Get config value
     *
     * @param string $path
     * @return mixed
     */
    public function getConfigValue(string $path)
    {
        return $this->scopeConfig->getValue(
            'grupoawamotos_b2b/' . $path,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if current customer is B2B
     *
     * @return bool
     */
    public function isB2BCustomer(): bool
    {
        if (!$this->customerSession->isLoggedIn()) {
            return false;
        }

        $groupId = (int)$this->customerSession->getCustomerGroupId();
        return in_array($groupId, $this->b2bGroups);
    }

    /**
     * Check if current customer is approved B2B
     *
     * @return bool
     */
    public function isApprovedB2BCustomer(): bool
    {
        if (!$this->isB2BCustomer()) {
            return false;
        }

        $groupId = (int)$this->customerSession->getCustomerGroupId();
        
        // Pendente não está aprovado
        if ($groupId === self::GROUP_B2B_PENDENTE) {
            return false;
        }

        return true;
    }

    /**
     * Get current customer B2B group
     *
     * @return int|null
     */
    public function getCustomerB2BGroup(): ?int
    {
        if (!$this->isB2BCustomer()) {
            return null;
        }

        return (int)$this->customerSession->getCustomerGroupId();
    }

    /**
     * Get B2B group name
     *
     * @param int $groupId
     * @return string
     */
    public function getB2BGroupName(int $groupId): string
    {
        $names = [
            self::GROUP_B2B_ATACADO => 'B2B Atacado',
            self::GROUP_B2B_VIP => 'B2B VIP',
            self::GROUP_B2B_REVENDEDOR => 'B2B Revendedor',
            self::GROUP_B2B_PENDENTE => 'B2B Pendente'
        ];

        return $names[$groupId] ?? 'Cliente';
    }

    /**
     * Get discount percentage for customer group
     *
     * @param int $groupId
     * @return float
     */
    public function getGroupDiscount(int $groupId): float
    {
        $discounts = [
            self::GROUP_B2B_ATACADO => 15.0,
            self::GROUP_B2B_VIP => 20.0,
            self::GROUP_B2B_REVENDEDOR => 10.0,
            self::GROUP_B2B_PENDENTE => 0.0
        ];

        return $discounts[$groupId] ?? 0.0;
    }

    /**
     * Get all B2B group IDs
     *
     * @return array
     */
    public function getB2BGroupIds(): array
    {
        return $this->b2bGroups;
    }

    /**
     * Check if a group ID is a B2B group
     *
     * @param int $groupId
     * @return bool
     */
    public function isB2BGroup(int $groupId): bool
    {
        return in_array($groupId, $this->b2bGroups);
    }

    /**
     * Get group ID by code
     *
     * @param string $code
     * @return int|null
     */
    public function getGroupIdByCode(string $code): ?int
    {
        $mapping = [
            'b2b_atacado' => self::GROUP_B2B_ATACADO,
            'b2b_vip' => self::GROUP_B2B_VIP,
            'b2b_revendedor' => self::GROUP_B2B_REVENDEDOR,
            'b2b_pendente' => self::GROUP_B2B_PENDENTE,
        ];

        return $mapping[$code] ?? null;
    }
}
