<?php
/**
 * B2B Status Panel Block for Header
 * Professional B2B status indicator with dropdown panel
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Block\Header;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use GrupoAwamotos\B2B\Helper\Data as B2BHelper;

class StatusPanel extends Template
{
    private CustomerSession $customerSession;
    private B2BHelper $b2bHelper;
    private PriceCurrencyInterface $priceCurrency;

    protected $_template = 'GrupoAwamotos_B2B::header/status-panel.phtml';

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        B2BHelper $b2bHelper,
        PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->b2bHelper = $b2bHelper;
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context, $data);
    }

    /**
     * Check if B2B panel should be displayed
     */
    public function shouldDisplay(): bool
    {
        return $this->b2bHelper->isEnabled()
            && $this->customerSession->isLoggedIn()
            && $this->isB2BCustomer();
    }

    /**
     * Check if guest should see registration CTA
     */
    public function shouldShowRegistrationCTA(): bool
    {
        return $this->b2bHelper->isEnabled() && !$this->customerSession->isLoggedIn();
    }

    /**
     * Check if current customer is B2B
     */
    public function isB2BCustomer(): bool
    {
        $customerGroupId = (int) $this->customerSession->getCustomerGroupId();
        $b2bGroups = $this->b2bHelper->getB2BGroupIds();
        return in_array($customerGroupId, $b2bGroups);
    }

    /**
     * Get customer data for display
     */
    public function getCustomerData(): array
    {
        $customer = $this->customerSession->getCustomer();
        $customerGroupId = (int) $this->customerSession->getCustomerGroupId();

        return [
            'first_name' => $customer ? $customer->getFirstname() : '',
            'full_name' => $customer ? $customer->getName() : '',
            'email' => $customer ? $customer->getEmail() : '',
            'company' => $this->getCompanyName(),
            'group_id' => $customerGroupId,
            'group_name' => $this->getGroupName($customerGroupId),
            'group_badge' => $this->getGroupBadge($customerGroupId),
            'discount' => $this->getDiscountPercentage($customerGroupId),
            'credit_limit' => $this->getCreditLimit(),
            'credit_available' => $this->getAvailableCredit(),
        ];
    }

    /**
     * Get company name from customer attribute
     */
    private function getCompanyName(): string
    {
        $customer = $this->customerSession->getCustomer();
        if ($customer) {
            // Try different possible attribute names
            $company = $customer->getData('company')
                ?? $customer->getData('empresa')
                ?? $customer->getData('razao_social')
                ?? '';
            return (string) $company;
        }
        return '';
    }

    /**
     * Get customer group name
     */
    private function getGroupName(int $groupId): string
    {
        $groupNames = [
            4 => 'Atacado',
            5 => 'VIP',
            6 => 'Revendedor',
            7 => 'Distribuidor',
        ];
        return $groupNames[$groupId] ?? 'B2B';
    }

    /**
     * Get group badge color/style
     */
    private function getGroupBadge(int $groupId): array
    {
        $badges = [
            4 => ['color' => '#2563eb', 'icon' => 'building', 'label' => 'Atacado'],
            5 => ['color' => '#7c3aed', 'icon' => 'crown', 'label' => 'VIP'],
            6 => ['color' => '#059669', 'icon' => 'store', 'label' => 'Revendedor'],
            7 => ['color' => '#dc2626', 'icon' => 'truck', 'label' => 'Distribuidor'],
        ];
        return $badges[$groupId] ?? ['color' => '#6b7280', 'icon' => 'briefcase', 'label' => 'B2B'];
    }

    /**
     * Get discount percentage for customer group
     */
    private function getDiscountPercentage(int $groupId): int
    {
        $discountMap = [
            4 => 15,  // Atacado
            5 => 25,  // VIP
            6 => 10,  // Revendedor
            7 => 20,  // Distribuidor
        ];
        return $discountMap[$groupId] ?? 0;
    }

    /**
     * Get credit limit for B2B customer
     */
    private function getCreditLimit(): float
    {
        $customer = $this->customerSession->getCustomer();
        if ($customer) {
            $limit = $customer->getData('credit_limit') ?? $customer->getData('limite_credito') ?? 0;
            return (float) $limit;
        }
        return 0.0;
    }

    /**
     * Get available credit
     */
    private function getAvailableCredit(): float
    {
        $customer = $this->customerSession->getCustomer();
        if ($customer) {
            $available = $customer->getData('credit_available') ?? $customer->getData('credito_disponivel') ?? 0;
            return (float) $available;
        }
        return $this->getCreditLimit();
    }

    /**
     * Format price
     */
    public function formatPrice(float $amount): string
    {
        return $this->priceCurrency->format($amount, false);
    }

    /**
     * Get quick action links
     */
    public function getQuickActions(): array
    {
        return [
            [
                'url' => $this->getUrl('b2b/account/dashboard'),
                'label' => __('Painel B2B'),
                'icon' => 'tachometer',
            ],
            [
                'url' => $this->getUrl('sales/order/history'),
                'label' => __('Meus Pedidos'),
                'icon' => 'file-text-o',
            ],
            [
                'url' => $this->getUrl('b2b/quote'),
                'label' => __('Cotações'),
                'icon' => 'calculator',
            ],
            [
                'url' => $this->getUrl('b2b/lists'),
                'label' => __('Listas de Compras'),
                'icon' => 'list-ul',
            ],
        ];
    }

    /**
     * Get B2B registration URL
     */
    public function getRegistrationUrl(): string
    {
        return $this->getUrl('b2b/register');
    }
}
