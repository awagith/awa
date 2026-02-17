<?php
namespace Rokanthemes\Faq\Controller\Adminhtml\Faq;

use Rokanthemes\Faq\Controller\Adminhtml\AbstractStore;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends AbstractStore
{
    /**
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionStoreFactory->create());

		$collectionSize = $collection->getSize();
		$delete = 0;
        foreach ($collection as $item) {
            try {
				$this->deleteItem($item);
				$this->messageManager->addSuccessMessage(
					__('A total of %1 record(s) have been deleted.', $collectionSize)
				);
			} catch (\Exception $e) {
				$this->logger->critical($e);
				$this->messageManager->addErrorMessage($e->getMessage());
			}
            $delete++;
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('rokanthemes/faq/gird');
    }
	
	protected function deleteItem($item)
    {
        return $item->delete();
    }
}
