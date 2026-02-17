<?php
/**
 * Copyright © 2019 Rokanthemes. All rights reserved.
 */

namespace Rokanthemes\Faq\Controller;

use \Magento\Framework\App\Action\Action;
use \Magento\Framework\App\Action\Context;
use \Magento\Framework\View\Result\PageFactory;

abstract class Index extends Action
{
    protected $resultPageFactory;
	protected $scopeConfig;
	protected $url;
	protected $_scopeConfig;

    public function __construct(
        Context $context,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Framework\UrlInterface $url,
        PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
		$this->_scopeConfig = $scopeConfig;
		$this->url = $url;
        parent::__construct($context);
    }
}
