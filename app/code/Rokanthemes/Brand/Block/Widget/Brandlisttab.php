<?php

namespace Rokanthemes\Brand\Block\Widget;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\DataObject\IdentityInterface;

class Brandlisttab extends \Magento\Catalog\Block\Product\AbstractProduct implements \Magento\Widget\Block\BlockInterface
{

    /**
     * Default toolbar block name
     *
     * @var string
     */
    protected $_defaultToolbarBlock = 'Magento\Catalog\Block\Product\ProductList\Toolbar';

    /**
     * Product Collection
     *
     * @var AbstractCollection
     */
    protected $_productCollection;

    /**
     * Catalog layer
     *
     * @var \Magento\Catalog\Model\Layer
     */
    protected $_catalogLayer;

    /**
     * @var \Magento\Framework\Data\Helper\PostHelper
     */
    protected $_postDataHelper;

    /**
     * @var \Magento\Framework\Url\Helper\Data
     */
    protected $urlHelper;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;
    protected $productCollectionFactory;
    protected $storeManager;
    protected $catalogConfig;
    protected $productVisibility;
    protected $scopeConfig;
    protected $_brandCollection;
    protected $_brandHelper;

    /**
     * @param Context $context
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Rokanthemes\Brand\Model\Brand $brandCollection,
        \Rokanthemes\Brand\Helper\Data $brandHelper,
        array $data = []
    ) {
        $this->_brandHelper = $brandHelper;
        $this->_brandCollection = $brandCollection;
        $this->_catalogLayer = $layerResolver->get();
        $this->_postDataHelper = $postDataHelper;
        $this->categoryRepository = $categoryRepository;
        $this->urlHelper = $urlHelper;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $context->getStoreManager();
        $this->catalogConfig = $context->getCatalogConfig();
        $this->productVisibility = $productVisibility;
        parent::__construct(
            $context,
            $data
        );
    }

    public function _toHtml()
    {
        $this->setTemplate('widget/brand_list_tab.phtml');
        return parent::_toHtml();
    }

    public function getBrandConfig($path)
    {
        return $this->_brandHelper->getConfig($path);
    }

    public function getConfig($key, $default = '')
    {
        if($this->hasData($key))
        {
            return $this->getData($key);
        }
        return $default;
    }

    public function getBrandCollection()
    {
        $brandGroups = $this->getConfig('brand_groups');
        $collection = $this->_brandCollection->getCollection()
        ->addFieldToFilter('status',1);
        $brandGroups = explode(',', $brandGroups);
        if(is_array($brandGroups))
        {
            $collection->addFieldToFilter('group_id',array('in' => $brandGroups));
        }
        $collection->setOrder('position','ASC');
        return $collection;
    }
    
    public function getProductsByBrandId($brand_id)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection();
        $tableName = $resource->getTableName('rokanthemes_brand_product');
        $sql = "Select * FROM " . $tableName." Where brand_id = ".$brand_id;
        $result = $connection->fetchAll($sql);
        $products_load = [];
        if(count($result) > 0){
            foreach ($result as $value_b) {
                $products_load[] = $value_b['product_id'];
            }
        }

        $storeId    = $this->storeManager->getStore()->getId();
        $products = $this->productCollectionFactory->create()->setStoreId($storeId);
        $products
            ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
            ->addAttributeToFilter('entity_id', array('in'=>$products_load))
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addUrlRewrite()
            ->setVisibility($this->productVisibility->getVisibleInCatalogIds());
        $products->setPageSize($this->getConfig('number_item'))->setCurPage(1);
        $this->_eventManager->dispatch(
            'catalog_block_product_list_collection',
            ['collection' => $products]
        );
        return $products;
    }
    
    public function getAddToCartPostParams(\Magento\Catalog\Model\Product $product)
    {
        $url = $this->getAddToCartUrl($product);
        return [
            'action' => $url,
            'data' => [
                'product' => $product->getEntityId(),
                \Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED =>
                    $this->urlHelper->getEncodedUrl($url),
            ]
        ];
    }
    public function getAddToCartUrl($product, $additional = [])
    {
        return $this->_cartHelper->getAddUrl($product, $additional);
    }
}
