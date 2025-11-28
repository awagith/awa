<?php
/**
 * Plugin to block checkout for non-approved customers
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Plugin;

use GrupoAwamotos\B2B\Api\PriceVisibilityInterface;
use GrupoAwamotos\B2B\Helper\Config;
use Magento\Checkout\Controller\Index\Index;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;

class BlockCheckoutPlugin
{
    /**
     * @var PriceVisibilityInterface
     */
    private $priceVisibility;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var RedirectFactory
     */
    private $redirectFactory;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    public function __construct(
        PriceVisibilityInterface $priceVisibility,
        Config $config,
        RedirectFactory $redirectFactory,
        ManagerInterface $messageManager
    ) {
        $this->priceVisibility = $priceVisibility;
        $this->config = $config;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
    }

    /**
     * Around execute - block checkout if not approved
     *
     * @param Index $subject
     * @param callable $proceed
     * @return \Magento\Framework\Controller\Result\Redirect|mixed
     */
    public function aroundExecute(Index $subject, callable $proceed)
    {
        if (!$this->config->isEnabled()) {
            return $proceed();
        }
        
        // Verificar se cliente está aprovado
        if (!$this->priceVisibility->isCustomerApproved()) {
            $redirect = $this->redirectFactory->create();
            
            $status = $this->priceVisibility->getCustomerApprovalStatus();
            
            if ($status === null) {
                // Visitante - não logado
                $this->messageManager->addNoticeMessage(
                    __('Faça login para finalizar sua compra.')
                );
                return $redirect->setPath('customer/account/login');
            }
            
            // Cliente logado mas não aprovado
            $this->messageManager->addWarningMessage(
                __('Sua conta está pendente de aprovação. Você não pode finalizar compras até que sua conta seja aprovada.')
            );
            return $redirect->setPath('checkout/cart');
        }
        
        // Verificar valor mínimo do pedido
        if ($this->config->isMinQtyEnabled()) {
            $minAmount = $this->config->getMinOrderAmount();
            if ($minAmount > 0) {
                // Implementar verificação de valor mínimo aqui se necessário
            }
        }
        
        return $proceed();
    }
}
