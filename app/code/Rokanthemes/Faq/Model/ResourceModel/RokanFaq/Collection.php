<?php
namespace Rokanthemes\Faq\Model\ResourceModel\RokanFaq;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Rokanthemes\Faq\Model\RokanFaq::class,
            \Rokanthemes\Faq\Model\ResourceModel\RokanFaq::class
        );
    }
}
