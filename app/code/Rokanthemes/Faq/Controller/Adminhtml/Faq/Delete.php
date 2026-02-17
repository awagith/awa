<?php

namespace Rokanthemes\Faq\Controller\Adminhtml\Faq;

use Rokanthemes\Faq\Controller\Adminhtml\AbstractStore;

class Delete extends AbstractStore
{
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $store = $this->storeFactory->create()->load($id);
            if ($store->getEntityId()) {
                $store->delete();
                $this->messageManager->addSuccessMessage(__('Success'));
                return $resultRedirect->setPath('rokanthemes/faq/gird');
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath(
                'rokanthemes/faq/edit',
                ['id' => $id]
            );
        }
        $this->messageManager->addErrorMessage(__('We can\'t find an FAQ to delete.'));
        return $resultRedirect->setPath('rokanthemes/faq/gird');
    }
}
