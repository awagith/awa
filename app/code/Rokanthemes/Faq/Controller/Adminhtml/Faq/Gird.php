<?php

namespace Rokanthemes\Faq\Controller\Adminhtml\Faq;

use Rokanthemes\Faq\Controller\Adminhtml\AbstractStore;

class Gird extends AbstractStore
{
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Rokanthemes_Faq::rokanfaq'); 
        $resultPage
            ->getConfig()
            ->getTitle() 
            ->prepend(__('Manage FAQ'));

        return $resultPage;
    }
}
