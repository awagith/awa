<?php
use Magento\Framework\App\Bootstrap;
require 'app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

$categoryFactory = $objectManager->get('\Magento\Catalog\Model\CategoryFactory');
$category = $categoryFactory->create()->loadByAttribute('name', 'Bauletos');

if (!$category) { echo "Categoria Bauletos não encontrada.\n"; exit; }
$categoryId = $category->getId();

/** @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory */
$productCollectionFactory = $objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
$collection = $productCollectionFactory->create();
$collection->addCategoryFilter($category)->addAttributeToSelect('*')->setPageSize(50)->setCurPage(1);

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
        echo "Erro ao salvar SKU: " . $product->getSku() . " - " . $e->getMessage() . "\n";
    }
}
echo "Sucesso! $count produtos atualizados com atributos de teste.\n";
