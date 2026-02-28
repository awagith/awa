<?php
/**
 * Controller para página de login B2B (estilo Forceline)
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Controller\Account;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Store\Model\StoreManagerInterface;

class Login implements HttpGetActionInterface
{
    private PageFactory $resultPageFactory;
    private CustomerSession $customerSession;
    private RedirectFactory $redirectFactory;
    private RequestInterface $request;
    private ?StoreManagerInterface $storeManager;

    public function __construct(
        PageFactory $resultPageFactory,
        CustomerSession $customerSession,
        RedirectFactory $redirectFactory,
        RequestInterface $request,
        ?StoreManagerInterface $storeManager = null
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->redirectFactory = $redirectFactory;
        $this->request = $request;
        $this->storeManager = $storeManager;
    }

    public function execute()
    {
        if ($this->customerSession->isLoggedIn()) {
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('customer/account');
        }

        // Captura referer para redirect pós-login (com proteção contra open redirect)
        $referer = $this->request->getParam('referer');
        if ($referer) {
            $decodedReferer = base64_decode($referer, true);
            if ($decodedReferer !== false && $this->isInternalUrl($decodedReferer)) {
                $this->customerSession->setBeforeAuthUrl($decodedReferer);
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Acesse sua conta'));

        return $resultPage;
    }

    /**
     * Validate that URL belongs to the same store (prevents open redirect)
     */
    private function isInternalUrl(string $url): bool
    {
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $urlHost = parse_url($url, PHP_URL_HOST);
        if ($this->storeManager === null) {
            return false;
        }

        $storeHost = parse_url(
            $this->storeManager->getStore()->getBaseUrl(),
            PHP_URL_HOST
        );

        return $urlHost !== null && $storeHost !== null
            && strcasecmp($urlHost, $storeHost) === 0;
    }
}
