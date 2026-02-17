<?php
namespace Rokanthemes\Faq\Controller\Adminhtml\Faq;

use Rokanthemes\Faq\Controller\Adminhtml\AbstractStore;
use Magento\Framework\Exception\LocalizedException;

class Save extends AbstractStore
{
    public function execute()
    {
        $params = $this->getRequest()->getPost();
        $resultRedirect = $this->resultRedirectFactory->create();
        $patternId = $params['entity_id'];
        $patternModel = $this->faqFactory->create();
        $redirectBack = $this->getRequest()->getParam('back', false);

        if ($patternId) {
            $patternModel->load($patternId); 
			$patternModel->setStatus($params['status']);
			$patternModel->setParentId($params['parent_id']);
			$patternModel->setQuestion($params['question']);
			$patternModel->setAnswer($params['answer']);
			$patternModel->save();
            $this->messageManager->addSuccessMessage(
                __('Saved FAQ Success.')
            );
            return $this->returnResult($redirectBack, $resultRedirect, $patternId);
        }
        try {
			$patternModel->setStatus($params['status']);
			$patternModel->setParentId($params['parent_id']);
			$patternModel->setQuestion($params['question']);
			$patternModel->setAnswer($params['answer']);
			$patternModel->save();
			$patternId = $patternModel->getEntityId();
        } catch (LocalizedException $e) {
            $this->logger->critical($e);
            $this->messageManager->addExceptionMessage($e);
            $redirectBack = $patternId ? true : 'new';
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage($e->getMessage());
            $redirectBack = $patternId ? true : 'new';
        }

        return $this->returnResult($redirectBack, $resultRedirect, $patternId);
    }

    /**
     * @param string|boolean $redirectBack
     * @param \Magento\Framework\Controller\Result\Redirect $resultRedirect
     * @param integer|null $patternId
     * @return mixed
     */
    protected function returnResult($redirectBack, $resultRedirect, $patternId)
    {
        if ($redirectBack === 'new') {
            $resultRedirect->setPath(
                'rokanthemes/faq/new'
            );
        } elseif ($redirectBack === 'edit') {
            $resultRedirect->setPath(
                'rokanthemes/faq/edit',
                ['id' => $patternId]
            );
        } else {
            $resultRedirect->setPath('rokanthemes/faq/gird');
        }

        return $resultRedirect;
    }
}
