<?php
/**
 * Plugin to block add to cart for non-approved users.
 * Handles both standard requests (redirect) and AJAX requests (JSON response).
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Plugin;

use GrupoAwamotos\B2B\Api\PriceVisibilityInterface;
use GrupoAwamotos\B2B\Helper\Config;
use Magento\Checkout\Controller\Cart\Add;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
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
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        PriceVisibilityInterface $priceVisibility,
        Config $config,
        RedirectFactory $redirectFactory,
        JsonFactory $jsonFactory,
        ManagerInterface $messageManager,
        UrlInterface $urlBuilder,
        CustomerSession $customerSession,
        RequestInterface $request
    ) {
        $this->priceVisibility = $priceVisibility;
        $this->config = $config;
        $this->redirectFactory = $redirectFactory;
        $this->jsonFactory = $jsonFactory;
        $this->messageManager = $messageManager;
        $this->urlBuilder = $urlBuilder;
        $this->customerSession = $customerSession;
        $this->request = $request;
    }

    /**
     * Around execute - block if not allowed.
     * Returns JSON for AJAX requests (AjaxSuite), redirect for standard requests.
     *
     * @param Add $subject
     * @param callable $proceed
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\Result\Json|mixed
     */
    public function aroundExecute(Add $subject, callable $proceed)
    {
        if (!$this->config->isEnabled()) {
            return $proceed();
        }

        if (!$this->priceVisibility->canAddToCart()) {
            $isAjax = $this->request->isXmlHttpRequest()
                || $this->request->getParam('ajax_post')
                || str_contains((string) $this->request->getHeader('Accept'), 'application/json');

            if (!$this->customerSession->isLoggedIn()) {
                $message = (string) __('Faça login ou cadastre-se para adicionar produtos ao carrinho.');
                $url = $this->urlBuilder->getUrl('b2b/account/login');

                if ($isAjax) {
                    return $this->jsonFactory->create()->setData([
                        'error' => $message,
                        'url' => $url,
                        'success' => '',
                    ]);
                }

                $this->messageManager->addNoticeMessage($message);
                return $this->redirectFactory->create()->setPath('b2b/account/login');
            }

            // Cliente logado mas bloqueado — distinguir motivo
            if ($this->priceVisibility->isApprovedPendingErp()) {
                $message = (string) __('Sua tabela de preços está sendo definida. Em breve você poderá realizar compras.');
            } else {
                $message = (string) __('Sua conta está pendente de aprovação. Você receberá um e-mail assim que for aprovada.');
            }
            $url = $this->urlBuilder->getUrl('customer/account');

            if ($isAjax) {
                return $this->jsonFactory->create()->setData([
                    'error' => $message,
                    'url' => $url,
                    'success' => '',
                ]);
            }

            $this->messageManager->addWarningMessage($message);
            return $this->redirectFactory->create()->setPath('customer/account');
        }

        return $proceed();
    }
}
