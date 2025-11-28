<?php
/**
 * Price Visibility Service
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Model;

use GrupoAwamotos\B2B\Api\PriceVisibilityInterface;
use GrupoAwamotos\B2B\Helper\Config;
use GrupoAwamotos\B2B\Model\Customer\Attribute\Source\ApprovalStatus;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\UrlInterface;

class PriceVisibility implements PriceVisibilityInterface
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
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var bool|null
     */
    private $canViewPricesCache = null;

    /**
     * @var bool|null
     */
    private $canAddToCartCache = null;

    public function __construct(
        Config $config,
        CustomerSession $customerSession,
        UrlInterface $urlBuilder
    ) {
        $this->config = $config;
        $this->customerSession = $customerSession;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @inheritDoc
     */
    public function canViewPrices(): bool
    {
        if ($this->canViewPricesCache !== null) {
            return $this->canViewPricesCache;
        }
        
        // Se módulo desabilitado, mostrar preços
        if (!$this->config->isEnabled()) {
            $this->canViewPricesCache = true;
            return true;
        }
        
        // Se usuário está logado
        if ($this->customerSession->isLoggedIn()) {
            // Verificar status de aprovação
            $customer = $this->customerSession->getCustomer();
            $approvalStatus = $customer->getData('b2b_approval_status');
            
            // Cliente aprovado pode ver preços
            if ($approvalStatus === ApprovalStatus::STATUS_APPROVED) {
                $this->canViewPricesCache = true;
                return true;
            }
            
            // Cliente pendente - depende da configuração
            if ($approvalStatus === ApprovalStatus::STATUS_PENDING) {
                $this->canViewPricesCache = $this->config->showPriceForPending();
                return $this->canViewPricesCache;
            }
            
            // Cliente rejeitado ou suspenso não vê preços
            $this->canViewPricesCache = false;
            return false;
        }
        
        // Visitante (não logado) - depende da configuração
        $this->canViewPricesCache = !$this->config->hidePriceForGuests();
        return $this->canViewPricesCache;
    }

    /**
     * @inheritDoc
     */
    public function canAddToCart(): bool
    {
        if ($this->canAddToCartCache !== null) {
            return $this->canAddToCartCache;
        }
        
        // Se módulo desabilitado, permitir
        if (!$this->config->isEnabled()) {
            $this->canAddToCartCache = true;
            return true;
        }
        
        // Se usuário está logado
        if ($this->customerSession->isLoggedIn()) {
            // Somente clientes aprovados podem adicionar ao carrinho
            $this->canAddToCartCache = $this->isCustomerApproved();
            return $this->canAddToCartCache;
        }
        
        // Visitante - depende da configuração
        $this->canAddToCartCache = !$this->config->hideAddToCartForGuests();
        return $this->canAddToCartCache;
    }

    /**
     * @inheritDoc
     */
    public function getPriceReplacementMessage(): string
    {
        $message = $this->config->getLoginMessage();
        
        if (empty($message)) {
            $message = '<a href="{{login_url}}">Faça login</a> para ver os preços';
        }
        
        // Substituir placeholders
        $loginUrl = $this->urlBuilder->getUrl('customer/account/login');
        $registerUrl = $this->urlBuilder->getUrl('customer/account/create');
        
        $message = str_replace(
            ['{{login_url}}', '{{register_url}}'],
            [$loginUrl, $registerUrl],
            $message
        );
        
        return $message;
    }

    /**
     * @inheritDoc
     */
    public function isCustomerApproved(): bool
    {
        if (!$this->customerSession->isLoggedIn()) {
            return false;
        }
        
        $customer = $this->customerSession->getCustomer();
        $approvalStatus = $customer->getData('b2b_approval_status');
        
        // Se não há status definido, considerar como aprovado (compatibilidade)
        if (empty($approvalStatus)) {
            return true;
        }
        
        return $approvalStatus === ApprovalStatus::STATUS_APPROVED;
    }
    
    /**
     * Get customer approval status
     *
     * @return string|null
     */
    public function getCustomerApprovalStatus(): ?string
    {
        if (!$this->customerSession->isLoggedIn()) {
            return null;
        }
        
        $customer = $this->customerSession->getCustomer();
        return $customer->getData('b2b_approval_status');
    }
    
    /**
     * Clear cached values (useful after login/logout)
     */
    public function clearCache(): void
    {
        $this->canViewPricesCache = null;
        $this->canAddToCartCache = null;
    }
}
