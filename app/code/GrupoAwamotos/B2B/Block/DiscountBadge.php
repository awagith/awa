<?php
/**
 * B2B Discount Badge Block
 * Shows discount indicator for logged-in B2B customers
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session as CustomerSession;
use GrupoAwamotos\B2B\Helper\Data as B2BHelper;

class DiscountBadge extends Template
{
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var B2BHelper
     */
    private $b2bHelper;

    /**
     * @var string
     */
    protected $_template = 'GrupoAwamotos_B2B::widget/discount-badge.phtml';

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        B2BHelper $b2bHelper,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->b2bHelper = $b2bHelper;
        parent::__construct($context, $data);
    }

    /**
     * Should badge be displayed?
     *
     * @return bool
     */
    public function shouldDisplay(): bool
    {
        if (!$this->b2bHelper->isEnabled()) {
            return false;
        }
        
        if (!$this->customerSession->isLoggedIn()) {
            return false;
        }
        
        // Only show for B2B customers
        $customerGroupId = (int) $this->customerSession->getCustomerGroupId();
        $b2bGroups = $this->b2bHelper->getB2BGroupIds();
        
        return in_array($customerGroupId, $b2bGroups);
    }

    /**
     * Get discount percentage for current customer group
     *
     * @return int
     */
    public function getDiscountPercentage(): int
    {
        $customerGroupId = (int) $this->customerSession->getCustomerGroupId();
        
        // Discount map based on customer group
        $discountMap = [
            4 => 15,  // B2B Atacado
            5 => 20,  // B2B VIP
            6 => 10,  // B2B Revendedor
        ];
        
        return $discountMap[$customerGroupId] ?? 0;
    }

    /**
     * Get customer group name
     *
     * @return string
     */
    public function getGroupName(): string
    {
        $customerGroupId = (int) $this->customerSession->getCustomerGroupId();
        
        $groupNames = [
            4 => 'Atacado',
            5 => 'VIP',
            6 => 'Revendedor',
        ];
        
        return $groupNames[$customerGroupId] ?? 'B2B';
    }

    /**
     * Get customer first name
     *
     * @return string
     */
    public function getCustomerFirstName(): string
    {
        $customer = $this->customerSession->getCustomer();
        return $customer ? $customer->getFirstname() : '';
    }
}
