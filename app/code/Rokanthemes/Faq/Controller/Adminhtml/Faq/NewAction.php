<?php
namespace Rokanthemes\Faq\Controller\Adminhtml\Faq;

use Magento\Framework\Controller\ResultFactory;
use Rokanthemes\Faq\Controller\Adminhtml\AbstractStore;

class NewAction extends AbstractStore
{
    /**
     * @return \Magento\Backend\Model\View\Result\Forward
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Forward $resultForward */
        $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
        return $resultForward->forward('edit');
    }
}
