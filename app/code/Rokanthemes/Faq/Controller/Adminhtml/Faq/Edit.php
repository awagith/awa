<?php
namespace Rokanthemes\Faq\Controller\Adminhtml\Faq;

use Rokanthemes\Faq\Controller\Adminhtml\AbstractStore;
use Magento\Framework\Controller\ResultFactory;

class Edit extends AbstractStore
{

    public function execute() 
    {
        $patternId = $this->getRequest()->getParam('id');
        if ($patternId) {
            try {
                $pattern = $this->faqFactory->create()->load($patternId);
                $this->registry->register('faq', $pattern);
                $pageTitle = sprintf("%s", $pattern->getQuestion());
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->messageManager->addErrorMessage(__('This pattern no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('rokanthemes/faq/gird/');
            }
        } else {
            $pageTitle = __('New FAQ');
        }

        $breadcrumb = $patternId ? __('Edit New FAQ') : __('New FAQ');
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Rokanthemes_MultiStore::rokanfaq');
        $resultPage->addBreadcrumb($breadcrumb, $breadcrumb);
        $resultPage->getConfig()->getTitle()->prepend(__('Frequently Asked Questions'));
        $resultPage->getConfig()->getTitle()->prepend($pageTitle);
        return $resultPage;
    }
}
