<?php
/**
 * B2B Shopping List Item Model
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Model;

use Magento\Framework\Model\AbstractModel;

class ShoppingListItem extends AbstractModel
{
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(\GrupoAwamotos\B2B\Model\ResourceModel\ShoppingListItem::class);
    }

    /**
     * Get product
     *
     * @return \Magento\Catalog\Model\Product|null
     */
    public function getProduct()
    {
        if (!$this->getProductId()) {
            return null;
        }

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $productRepository = $objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        
        try {
            return $productRepository->getById($this->getProductId());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get line total
     *
     * @return float
     */
    public function getLineTotal(): float
    {
        $product = $this->getProduct();
        if (!$product) {
            return 0;
        }
        return (float)$product->getFinalPrice() * (float)$this->getQty();
    }
}
