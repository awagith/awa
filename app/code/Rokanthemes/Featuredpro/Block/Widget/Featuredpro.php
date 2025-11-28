<?php

namespace Rokanthemes\Featuredpro\Block\Widget;

use Rokanthemes\Featuredpro\Block\Featured;
use Magento\Widget\Block\BlockInterface;

class Featuredpro extends Featured implements BlockInterface
{
    /**
     * Template do widget. Mantemos wrapper pois CMS/layout podem referenciar widget/featuredpro.phtml.
     * @var string
     */
    protected $_template = 'widget/featuredpro.phtml';

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $postDataHelper,
            $layerResolver,
            $categoryRepository,
            $urlHelper,
            $productCollectionFactory,
            $productVisibility,
            $data
        );
    }
}
