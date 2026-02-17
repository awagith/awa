<?php
/**
 * Plugin to redirect standard Magento login page to B2B login
 * When B2B mode is set to "strict"
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Plugin\Customer\Account;

use Magento\Customer\Controller\Account\Login;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

class LoginRedirectPlugin
{
    private ScopeConfigInterface $scopeConfig;
    private RedirectFactory $resultRedirectFactory;
    private RequestInterface $request;
    private UrlInterface $urlBuilder;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        RedirectFactory $resultRedirectFactory,
        RequestInterface $request,
        UrlInterface $urlBuilder
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->request = $request;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Redirect to B2B login page if strict mode is enabled
     */
    public function aroundExecute(Login $subject, callable $proceed)
    {
        $isEnabled = $this->scopeConfig->isSetFlag(
            'grupoawamotos_b2b/general/enabled',
            ScopeInterface::SCOPE_STORE
        );

        $b2bMode = $this->scopeConfig->getValue(
            'grupoawamotos_b2b/general/b2b_mode',
            ScopeInterface::SCOPE_STORE
        );

        if ($isEnabled && $b2bMode === 'strict') {
            $resultRedirect = $this->resultRedirectFactory->create();

            // Preserve referer parameter for post-login redirect
            $referer = $this->request->getParam('referer');
            $params = $referer ? ['referer' => $referer] : [];

            $resultRedirect->setPath('b2b/account/login', $params);
            return $resultRedirect;
        }

        return $proceed();
    }
}
