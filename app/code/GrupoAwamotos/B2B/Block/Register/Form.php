<?php
/**
 * Block para formulário de cadastro B2B
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Block\Register;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session as CustomerSession;

class Form extends Template
{
    /**
     * @var CustomerSession
     */
    private $customerSession;

    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        array $data = []
    ) {
        $this->customerSession = $customerSession;
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
     * Get form action URL
     *
     * @return string
     */
    public function getFormAction(): string
    {
        return $this->getUrl('b2b/register/save');
    }

    /**
     * Get login URL
     *
     * @return string
     */
    public function getLoginUrl(): string
    {
        return $this->getUrl('customer/account/login');
    }

    /**
     * Get customer dashboard URL
     *
     * @return string
     */
    public function getDashboardUrl(): string
    {
        return $this->getUrl('b2b/account/dashboard');
    }
}
