<?php
/**
 * Copyright © 2019 Rokanthemes. All rights reserved. 
 */

namespace Rokanthemes\Faq\Controller\Index;

use Magento\Framework\Controller\ResultFactory; 

class Index extends \Rokanthemes\Faq\Controller\Index
{
    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
		$faq_setting = $this->_scopeConfig->getValue('faq_setting/faq_rokan/enable', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
		$defaultNoRouteUrl = $this->_scopeConfig->getValue(
                'web/default/no_route',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
		if(!$faq_setting){
			$resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
			$norouteUrl = $this->url->getUrl($defaultNoRouteUrl);
			$resultRedirect->setUrl($norouteUrl);
			return $resultRedirect;
		}
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Frequently Asked Questions'));

        return $resultPage;
    }
}
