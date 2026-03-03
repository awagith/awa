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

$entityTypeId = $eavSetup->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);
$attributeSetId = 4; // Default
$attributeGroupId = $eavSetup->getDefaultAttributeGroupId($entityTypeId, $attributeSetId);

try {
    $eavSetup->addAttributeToGroup(
        $entityTypeId,
        $attributeSetId,
        $attributeGroupId,
        'manufacturer',
        10
    );
    echo "Atributo 'manufacturer' adicionado ao Attribute Set 4 (Default) no Grupo $attributeGroupId com sucesso.\n";

    // Garantir que é usável na navegação
    $eavSetup->updateAttribute($entityTypeId, 'manufacturer', 'is_filterable', 1);
    $eavSetup->updateAttribute($entityTypeId, 'manufacturer', 'is_filterable_in_search', 1);
    $eavSetup->updateAttribute($entityTypeId, 'manufacturer', 'used_in_product_listing', 1);
    echo "Configurações de Layered Navigation ativadas para 'manufacturer'.\n";

} catch (\Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
