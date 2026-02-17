<?php
namespace Rokanthemes\Faq\Model;

use Magento\Framework\Model\AbstractModel;
use Rokanthemes\Faq\Model\ResourceModel\RokanFaq as RokanFaqResourceModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class RokanFaq extends AbstractModel
{
    /**
     * @var CodeFactory
     */
    private $codeModelFactory;

    /**
     * @var CollectionFactory
     */
    private $productCollection;
	

    /**
     * Pattern constructor.
     * @param Context $context
     * @param Registry $registry
     * @param CodeFactory $codeModelFactory
     * @param CollectionFactory $productCollection
     */
    public function __construct(
        Context $context,
        Registry $registry
    ) {
        parent::__construct(
            $context,
            $registry
        );
		
    }

    /**
     * @return void
     */
    public function _construct()
    {
        $this->_init(RokanFaqResourceModel::class);
    }
}
