<?php
/**
 * Block para Dashboard B2B do cliente
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Block\Account;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use GrupoAwamotos\B2B\Model\ResourceModel\QuoteRequest\CollectionFactory as QuoteCollectionFactory;
use GrupoAwamotos\B2B\Model\ResourceModel\CreditLimit\CollectionFactory as CreditCollectionFactory;
use GrupoAwamotos\B2B\Helper\Data as B2BHelper;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Customer\Api\CustomerRepositoryInterface;

class Dashboard extends Template
{
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var OrderCollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * @var QuoteCollectionFactory
     */
    private $quoteCollectionFactory;

    /**
     * @var CreditCollectionFactory
     */
    private $creditCollectionFactory;

    /**
     * @var B2BHelper
     */
    private $b2bHelper;

    /**
     * @var PricingHelper
     */
    private $pricingHelper;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        OrderCollectionFactory $orderCollectionFactory,
        QuoteCollectionFactory $quoteCollectionFactory,
        CreditCollectionFactory $creditCollectionFactory,
        B2BHelper $b2bHelper,
        PricingHelper $pricingHelper,
        CustomerRepositoryInterface $customerRepository,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->quoteCollectionFactory = $quoteCollectionFactory;
        $this->creditCollectionFactory = $creditCollectionFactory;
        $this->b2bHelper = $b2bHelper;
        $this->pricingHelper = $pricingHelper;
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $data);
    }

    /**
     * Get current customer
     *
     * @return \Magento\Customer\Api\Data\CustomerInterface|null
     */
    public function getCustomer()
    {
        $customerId = $this->customerSession->getCustomerId();
        if ($customerId) {
            try {
                return $this->customerRepository->getById($customerId);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Check if customer is B2B
     *
     * @return bool
     */
    public function isB2BCustomer(): bool
    {
        return $this->b2bHelper->isB2BCustomer();
    }

    /**
     * Check if customer is approved
     *
     * @return bool
     */
    public function isApproved(): bool
    {
        $customer = $this->getCustomer();
        if ($customer) {
            $attr = $customer->getCustomAttribute('b2b_approved');
            return $attr && $attr->getValue();
        }
        return false;
    }

    /**
     * Get customer group name
     *
     * @return string
     */
    public function getCustomerGroupName(): string
    {
        $customer = $this->getCustomer();
        if ($customer) {
            $groupId = $customer->getGroupId();
            $groupNames = [
                4 => 'B2B Atacado',
                5 => 'B2B VIP',
                6 => 'B2B Revendedor',
                7 => 'B2B Pendente'
            ];
            return $groupNames[$groupId] ?? 'Cliente';
        }
        return 'Cliente';
    }

    /**
     * Get discount percentage for customer group
     *
     * @return float
     */
    public function getDiscountPercentage(): float
    {
        $customer = $this->getCustomer();
        if ($customer && $this->isApproved()) {
            $groupId = $customer->getGroupId();
            $discounts = [
                4 => 15, // Atacado
                5 => 20, // VIP
                6 => 10, // Revendedor
            ];
            return $discounts[$groupId] ?? 0;
        }
        return 0;
    }

    /**
     * Get CNPJ
     *
     * @return string
     */
    public function getCnpj(): string
    {
        $customer = $this->getCustomer();
        if ($customer) {
            $attr = $customer->getCustomAttribute('b2b_cnpj');
            return $attr ? (string)$attr->getValue() : '';
        }
        return '';
    }

    /**
     * Get Razão Social
     *
     * @return string
     */
    public function getRazaoSocial(): string
    {
        $customer = $this->getCustomer();
        if ($customer) {
            $attr = $customer->getCustomAttribute('b2b_razao_social');
            return $attr ? (string)$attr->getValue() : '';
        }
        return '';
    }

    /**
     * Get credit limit
     *
     * @return array|null
     */
    public function getCreditLimit(): ?array
    {
        $customerId = $this->customerSession->getCustomerId();
        if (!$customerId) {
            return null;
        }

        $collection = $this->creditCollectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId)
            ->setOrder('created_at', 'DESC')
            ->setPageSize(1);

        $credit = $collection->getFirstItem();
        if ($credit && $credit->getId()) {
            return [
                'limit' => (float)$credit->getCreditLimit(),
                'used' => (float)$credit->getUsedCredit(),
                'available' => (float)$credit->getCreditLimit() - (float)$credit->getUsedCredit()
            ];
        }

        return null;
    }

    /**
     * Get recent orders
     *
     * @param int $limit
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    public function getRecentOrders(int $limit = 5)
    {
        $customerId = $this->customerSession->getCustomerId();
        $collection = $this->orderCollectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId)
            ->setOrder('created_at', 'DESC')
            ->setPageSize($limit);

        return $collection;
    }

    /**
     * Get total orders amount (last 30 days)
     *
     * @return float
     */
    public function getTotalOrdersAmount(): float
    {
        $customerId = $this->customerSession->getCustomerId();
        $collection = $this->orderCollectionFactory->create();
        $thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
        
        $collection->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('created_at', ['gteq' => $thirtyDaysAgo])
            ->addFieldToFilter('state', ['neq' => 'canceled']);

        $total = 0;
        foreach ($collection as $order) {
            $total += (float)$order->getGrandTotal();
        }

        return $total;
    }

    /**
     * Get quote requests
     *
     * @param int $limit
     * @return \GrupoAwamotos\B2B\Model\ResourceModel\QuoteRequest\Collection
     */
    public function getQuoteRequests(int $limit = 5)
    {
        $customerId = $this->customerSession->getCustomerId();
        $collection = $this->quoteCollectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId)
            ->setOrder('created_at', 'DESC')
            ->setPageSize($limit);

        return $collection;
    }

    /**
     * Get pending quotes count
     *
     * @return int
     */
    public function getPendingQuotesCount(): int
    {
        $customerId = $this->customerSession->getCustomerId();
        $collection = $this->quoteCollectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('status', 'pending');

        return $collection->getSize();
    }

    /**
     * Get approved quotes count
     *
     * @return int
     */
    public function getApprovedQuotesCount(): int
    {
        $customerId = $this->customerSession->getCustomerId();
        $collection = $this->quoteCollectionFactory->create();
        $collection->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('status', 'approved');

        return $collection->getSize();
    }

    /**
     * Format price
     *
     * @param float $price
     * @return string
     */
    public function formatPrice($price): string
    {
        return $this->pricingHelper->currency($price, true, false);
    }

    /**
     * Get quote status label
     *
     * @param string $status
     * @return string
     */
    public function getStatusLabel(string $status): string
    {
        $labels = [
            'pending' => 'Pendente',
            'processing' => 'Em Análise',
            'approved' => 'Aprovado',
            'rejected' => 'Rejeitado',
            'expired' => 'Expirado',
            'converted' => 'Convertido em Pedido'
        ];
        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Get status CSS class
     *
     * @param string $status
     * @return string
     */
    public function getStatusClass(string $status): string
    {
        $classes = [
            'pending' => 'warning',
            'processing' => 'info',
            'approved' => 'success',
            'rejected' => 'danger',
            'expired' => 'secondary',
            'converted' => 'primary'
        ];
        return $classes[$status] ?? 'secondary';
    }

    /**
     * Get quote request URL
     *
     * @return string
     */
    public function getQuoteRequestUrl(): string
    {
        return $this->getUrl('b2b/quote/request');
    }

    /**
     * Get quotes list URL
     *
     * @return string
     */
    public function getQuotesListUrl(): string
    {
        return $this->getUrl('b2b/quote/history');
    }

    /**
     * Get orders URL
     *
     * @return string
     */
    public function getOrdersUrl(): string
    {
        return $this->getUrl('sales/order/history');
    }
}
