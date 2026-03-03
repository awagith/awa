<?php
use Magento\Framework\App\Bootstrap;
require 'app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

/** @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory */
$productCollectionFactory = $objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
$collection = $productCollectionFactory->create();
$collection->addAttributeToSelect('*')->setPageSize(50)->setCurPage(1);

$marcas = ['Honda', 'Yamaha', 'Suzuki', 'Kawasaki'];
$modelos = ['CG Titan', 'Bros', 'Fazer 250', 'Ninja 400', 'Biz 125'];
$anos = ['2023', '2024', '2025', 'Todos os anos'];

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get('\Magento\Catalog\Api\ProductRepositoryInterface');

$count = 0;
foreach ($collection as $product) {
    try {
        $productToUpdate = $productRepository->get($product->getSku(), true);
        $productToUpdate->setCustomAttribute('marca_moto', $marcas[array_rand($marcas)]);
        $productToUpdate->setCustomAttribute('modelo_moto', $modelos[array_rand($modelos)]);
        $productToUpdate->setCustomAttribute('ano_moto', $anos[array_rand($anos)]);
        $productRepository->save($productToUpdate);
        $count++;
        echo "Updating Product SKU: " . $product->getSku() . " ($count/50)\n";
    } catch (\Exception $e) {
        // Silencioso
    }
}
echo "Sucesso! $count produtos atualizados com atributos de teste.\n";
