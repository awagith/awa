<?php
/**
 * Quote Form Block
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Block\Quote;

use GrupoAwamotos\B2B\Helper\Config;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class Form extends Template
{
    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        Config $config,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
        parent::__construct($context, $data);
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * Get current customer
     *
     * @return \Magento\Customer\Model\Customer|null
     */
    public function getCustomer()
    {
        return $this->isLoggedIn() ? $this->customerSession->getCustomer() : null;
    }

    /**
     * Get customer email
     *
     * @return string
     */
    public function getCustomerEmail(): string
    {
        $customer = $this->getCustomer();
        return $customer ? $customer->getEmail() : '';
    }

    /**
     * Get customer name
     *
     * @return string
     */
    public function getCustomerName(): string
    {
        $customer = $this->getCustomer();
        return $customer ? $customer->getName() : '';
    }

    /**
     * Get customer company name
     *
     * @return string
     */
    public function getCompanyName(): string
    {
        $customer = $this->getCustomer();
        return $customer ? (string) $customer->getData('b2b_razao_social') : '';
    }

    /**
     * Get customer CNPJ
     *
     * @return string
     */
    public function getCnpj(): string
    {
        $customer = $this->getCustomer();
        return $customer ? (string) $customer->getData('b2b_cnpj') : '';
    }

    /**
     * Get customer phone
     *
     * @return string
     */
    public function getPhone(): string
    {
        $customer = $this->getCustomer();
        return $customer ? (string) $customer->getData('b2b_phone') : '';
    }

    /**
     * Get quote
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * Get cart items
     *
     * @return array
     */
    public function getCartItems(): array
    {
        $quote = $this->getQuote();
        return $quote->getAllVisibleItems();
    }

    /**
     * Check if cart has items
     *
     * @return bool
     */
    public function hasItems(): bool
    {
        return $this->getQuote()->hasItems();
    }

    /**
     * Get cart total
     *
     * @return float
     */
    public function getCartTotal(): float
    {
        return (float) $this->getQuote()->getGrandTotal();
    }

    /**
     * Get submit URL
     *
     * @return string
     */
    public function getSubmitUrl(): string
    {
        return $this->getUrl('b2b/quote/submit');
    }

    /**
     * Get cart URL
     *
     * @return string
     */
    public function getCartUrl(): string
    {
        return $this->getUrl('checkout/cart');
    }

    /**
     * Get expiry days
     *
     * @return int
     */
    public function getExpiryDays(): int
    {
        return $this->config->getQuoteExpiryDays();
    }
}
