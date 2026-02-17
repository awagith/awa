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

class Login implements HttpGetActionInterface
{
    private PageFactory $resultPageFactory;
    private CustomerSession $customerSession;
    private RedirectFactory $redirectFactory;
    private RequestInterface $request;

    public function __construct(
        PageFactory $resultPageFactory,
        CustomerSession $customerSession,
        RedirectFactory $redirectFactory,
        RequestInterface $request
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->redirectFactory = $redirectFactory;
        $this->request = $request;
    }

    public function execute()
    {
        if ($this->customerSession->isLoggedIn()) {
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('customer/account');
        }

        // Captura referer para redirect pós-login
        $referer = $this->request->getParam('referer');
        if ($referer) {
            $decodedReferer = base64_decode($referer);
            $this->customerSession->setBeforeAuthUrl($decodedReferer);
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Acesse sua conta'));

        return $resultPage;
    }
}
