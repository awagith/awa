<?php
use Magento\Framework\App\Bootstrap;
require 'app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

/** @var \Magento\Eav\Setup\EavSetupFactory $eavSetupFactory */
$eavSetupFactory = $objectManager->get('\Magento\Eav\Setup\EavSetupFactory');
$setup = $objectManager->get('\Magento\Framework\Setup\ModuleDataSetupInterface');
$eavSetup = $eavSetupFactory->create(['setup' => $setup]);

/** @var \Magento\Eav\Model\Entity\Attribute\OptionManagement $optionManagement */
$attributeRepository = $objectManager->get('\Magento\Catalog\Model\Product\Attribute\Repository');
/** @var \Magento\Eav\Model\Entity\Attribute\Source\Table $sourceTable */

// Marcas
$brands = ['Honda', 'Yamaha', 'Suzuki', 'Kawasaki'];
$attributeCode = 'manufacturer';
$attribute = $attributeRepository->get($attributeCode);

$optionIds = [];
$options = $attribute->getSource()->getAllOptions();
foreach ($options as $option) {
    if ($option['value']) {
        $optionIds[$option['label']] = $option['value'];
    }
}

// Add missing options
$optionFactory = $objectManager->get('\Magento\Eav\Api\Data\AttributeOptionInterfaceFactory');
$optionLabelFactory = $objectManager->get('\Magento\Eav\Api\Data\AttributeOptionLabelInterfaceFactory');
$attributeOptionManagement = $objectManager->get('\Magento\Eav\Api\AttributeOptionManagementInterface');

foreach ($brands as $brand) {
    if (!isset($optionIds[$brand])) {
        $optionLabel = $optionLabelFactory->create();
        $optionLabel->setStoreId(0);
        $optionLabel->setLabel($brand);

        $option = $optionFactory->create();
        $option->setLabel($brand);
        $option->setStoreLabels([$optionLabel]);
        $option->setSortOrder(0);
        $option->setIsDefault(false);
        try {
            $attributeOptionManagement->add(\Magento\Catalog\Model\Product::ENTITY, $attributeCode, $option);
            echo "Adicionada opção: $brand\n";
        } catch (\Exception $e) {
            echo "Erro ao adicionar $brand: " . $e->getMessage() . "\n";
        }
    }
}

// Refresh options
$attribute = $attributeRepository->get($attributeCode);
$options = $attribute->getSource()->getAllOptions();
foreach ($options as $option) {
    if ($option['value']) {
        $optionIds[$option['label']] = $option['value'];
    }
}

// Assign to 50 products
/** @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory */
$productCollectionFactory = $objectManager->get('\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
$collection = $productCollectionFactory->create();
$collection->addAttributeToSelect('*')->setPageSize(50)->setCurPage(1);

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get('\Magento\Catalog\Api\ProductRepositoryInterface');

$count = 0;
foreach ($collection as $product) {
    try {
        $productToUpdate = $productRepository->get($product->getSku(), true);
        $randomBrand = $brands[array_rand($brands)];
        $optionId = $optionIds[$randomBrand];
        $productToUpdate->setCustomAttribute($attributeCode, $optionId);
        $productRepository->save($productToUpdate);
        $count++;
        echo "Updating Product SKU: " . $product->getSku() . " to $randomBrand (ID $optionId) ($count/50)\n";
    } catch (\Exception $e) {
        // Silencioso
    }
}
echo "Sucesso! $count produtos atualizados com manufacturer.\n";
