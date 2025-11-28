<?php
/**
 * Plugin to block add to cart for non-approved users
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Plugin;

use GrupoAwamotos\B2B\Api\PriceVisibilityInterface;
use GrupoAwamotos\B2B\Helper\Config;
use Magento\Checkout\Controller\Cart\Add;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlInterface;

class BlockCartAddPlugin
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

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        PriceVisibilityInterface $priceVisibility,
        Config $config,
        RedirectFactory $redirectFactory,
        ManagerInterface $messageManager,
        UrlInterface $urlBuilder
    ) {
        $this->priceVisibility = $priceVisibility;
        $this->config = $config;
        $this->redirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Around execute - block if not allowed
     *
     * @param Add $subject
     * @param callable $proceed
     * @return \Magento\Framework\Controller\Result\Redirect|mixed
     */
    public function aroundExecute(Add $subject, callable $proceed)
    {
        if (!$this->config->isEnabled()) {
            return $proceed();
        }
        
        if (!$this->priceVisibility->canAddToCart()) {
            $redirect = $this->redirectFactory->create();
            
            if (!$this->priceVisibility->isCustomerApproved()) {
                // Cliente logado mas não aprovado
                $this->messageManager->addWarningMessage(
                    __('Sua conta está pendente de aprovação. Você receberá um e-mail assim que for aprovada.')
                );
                return $redirect->setPath('customer/account');
            }
            
            // Visitante - redirecionar para login
            $this->messageManager->addNoticeMessage(
                __('Faça login ou cadastre-se para adicionar produtos ao carrinho.')
            );
            return $redirect->setPath('customer/account/login');
        }
        
        return $proceed();
    }
}
