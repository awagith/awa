<?php
namespace Rokanthemes\Themeoption\Controller\Fakeorder;

class Getitem extends \Magento\Framework\App\Action\Action
{
	protected $_pageFactory;
	protected $request;
	protected $_productCollectionFactory;
	protected $_helper;
	protected $_helperproducting;
	
	public function __construct( 
		\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
		\Magento\Framework\App\Request\Http $request,
		\Magento\Framework\App\Action\Context $context,
		\Rokanthemes\Themeoption\Helper\Themeconfig $helper,
		\Rokanthemes\Themeoption\Helper\Productimage $helperproducting,
		\Magento\Framework\View\Result\PageFactory $pageFactory)
	{
		$this->_pageFactory = $pageFactory;
		$this->request = $request;
		$this->_helper = $helper;
		$this->_helperproducting = $helperproducting;
		$this->_productCollectionFactory = $productCollectionFactory;
		return parent::__construct($context);
	}

	public function execute()
	{
		$objectManager =  \Magento\Framework\App\ObjectManager::getInstance();        
		$storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
		$store = $storeManager->getStore();
		$storeID = $store->getStoreId(); 
		
		$collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $collection->setVisibility(4);
        $collection->addStoreFilter($storeID)->getSelect()->orderRand()->limit(1);
		$html = '';
		$msg = $this->_helper->getInfoFakeOrder('messages');
		
        foreach($collection as $pro){
			$img = $this->_helperproducting->resizeProductImageSrc($pro, 'product_small_image', 124,124);
			$html .= '
				<div class="purchase-image">
					<a class="purchase-img" href="">
						<img alt="" class="img_purchase" src="'.$img.'">
					</a>
				</div>
				<div class="purchase-info">
					<span class="dib">'.$msg.'</span>
					<h3 class="title"><a href="'.$pro->getProductUrl().'">'.$pro->getName().'</a></h3>
					<div class="minutes-ago"><span class="minute-number">0'.rand(1,9).'</span> <span>'.__('minutes ago').'</span></div>
					<a class="btnProductQuickview" href="'.$pro->getProductUrl().'">
						'.__('view').'
					</a>
				</div>
			';
		}
		echo json_encode(array('html' => $html));
		die;
	}

}
