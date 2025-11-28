<?php
/**
 * B2B Shopping List Model
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Model;

use Magento\Framework\Model\AbstractModel;

class ShoppingList extends AbstractModel
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(\GrupoAwamotos\B2B\Model\ResourceModel\ShoppingList::class);
    }

    /**
     * Get items collection
     *
     * @return \GrupoAwamotos\B2B\Model\ResourceModel\ShoppingListItem\Collection
     */
    public function getItemsCollection()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $collection = $objectManager->create(\GrupoAwamotos\B2B\Model\ResourceModel\ShoppingListItem\Collection::class);
        $collection->addFieldToFilter('list_id', $this->getId());
        return $collection;
    }

    /**
     * Get items count
     *
     * @return int
     */
    public function getItemsCount(): int
    {
        return $this->getItemsCollection()->getSize();
    }
}
