<?php
declare(strict_types=1);

namespace GrupoAwamotos\Fitment\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class Brands extends Action implements HttpGetActionInterface
{
    public function __construct(
        Context $context,
        private readonly CollectionFactory $productCollectionFactory,
        private readonly JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('marca_moto')
            ->addAttributeToFilter('marca_moto', ['notnull' => true])
            ->addAttributeToFilter('marca_moto', ['neq' => ''])
            ->addAttributeToFilter('status', 1)
            ->addAttributeToFilter('visibility', ['in' => [2, 3, 4]]);

        $marcas = [];
        foreach ($collection as $product) {
            $val = trim((string) $product->getData('marca_moto'));
            if ($val !== '' && !in_array($val, $marcas, true)) {
                $marcas[] = $val;
            }
        }
        sort($marcas, SORT_NATURAL | SORT_FLAG_CASE);

        return $result->setData(['success' => true, 'items' => $marcas]);
    }
}
