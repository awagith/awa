<?php
/**
 * Blueskytechco
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Blueskytechco.com license that is
 * available through the world-wide-web at this URL:
 * http://www.blueskytechco.com/license-agreement.html
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category   Blueskytechco
 * @package    Rokanthemes_Brand
 * @copyright  Copyright (c) 2014 Blueskytechco (http://www.blueskytechco.com/)
 * @license    http://www.blueskytechco.com/LICENSE-1.0.html
 */
namespace Rokanthemes\Brand\Block\Category;
use Magento\Customer\Model\Context as CustomerContext;

class View extends \Magento\Framework\View\Element\Template
{
    /**
     * Group Collection
     */
    protected $_brandCollection;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Catalog\Helper\Category
     */
    protected $_brandHelper;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context         
     * @param \Magento\Framework\Registry                      $registry        
     * @param \Rokanthemes\Brand\Helper\Data                           $brandHelper     
     * @param \Rokanthemes\Brand\Model\Brand                           $brandCollection 
     * @param array                                            $data            
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Rokanthemes\Brand\Helper\Data $brandHelper,
        \Rokanthemes\Brand\Model\Brand $brandCollection,
        \Magento\Framework\App\ResourceConnection $resource,
        array $data = []
        ) {
        $this->_brandCollection = $brandCollection;
        $this->_brandHelper = $brandHelper;
        $this->_coreRegistry = $registry;
        $this->_resource = $resource;
        parent::__construct($context, $data); 
    }

    /**
     * Retrieve current product model
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getCategory()
    {
        return $this->_coreRegistry->registry('current_category');
    }

    public function getBrandCollection(){
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
		$adapter  = $resource->getConnection();
        $category = $this->getCategory();
		$sql = 'SELECT product_id FROM catalog_category_product WHERE category_id="'.$category->getId().'"';
		$data_query = $adapter->fetchAll($sql);
		$product_id = [];
		foreach ($data_query as $data){
			$product_id[] = $data['product_id'];
		}
		if(count($product_id) == 0){
			return false;
		}
        $connection = $this->_resource->getConnection(); 
        $table_name = $this->_resource->getTableName('rokanthemes_brand_product');
        $brandIds = $connection->fetchCol(" SELECT brand_id FROM ".$table_name." WHERE product_id IN (". implode(',', $product_id).")");
        if($brandIds || count($brandIds) > 0) {
            $collection = $this->_brandCollection->getCollection()
                ->setOrder('position','ASC')
                ->addFieldToFilter('status',1);
            $collection->getSelect()->where('brand_id IN (?)', $brandIds);
            return $collection;
        }
        return false;
    }

}