<?php
/**
 * Cria o atributo 'featured' e define produtos em destaque/novidades
 */
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$state = $objectManager->get(\Magento\Framework\App\State::class);

try {
    $state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
} catch (\Exception $e) {}

$eavSetup = $objectManager->create(\Magento\Eav\Setup\EavSetup::class, [
    'setup' => $objectManager->get(\Magento\Framework\Setup\ModuleDataSetupInterface::class)
]);

$entityTypeId = $eavSetup->getEntityTypeId(\Magento\Catalog\Model\Product::ENTITY);

// Verificar se atributo já existe
$attribute = $eavSetup->getAttribute($entityTypeId, 'featured');

if (!$attribute) {
    echo "Criando atributo 'featured'...\n";
    
    $eavSetup->addAttribute(
        \Magento\Catalog\Model\Product::ENTITY,
        'featured',
        [
            'type' => 'int',
            'backend' => '',
            'frontend' => '',
            'label' => 'Produto em Destaque',
            'input' => 'boolean',
            'class' => '',
            'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
            'visible' => true,
            'required' => false,
            'user_defined' => true,
            'default' => '0',
            'searchable' => false,
            'filterable' => false,
            'comparable' => false,
            'visible_on_front' => false,
            'used_in_product_listing' => true,
            'unique' => false,
            'apply_to' => '',
            'group' => 'General',
            'sort_order' => 100,
        ]
    );
    
    echo "✅ Atributo 'featured' criado!\n";
} else {
    echo "ℹ️ Atributo 'featured' já existe.\n";
}

// Adicionar a todos os attribute sets
$attributeSetCollection = $objectManager->create(\Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection::class);
$attributeSetCollection->setEntityTypeFilter($entityTypeId);

$attributeId = $eavSetup->getAttributeId($entityTypeId, 'featured');

foreach ($attributeSetCollection as $attributeSet) {
    $groupId = $eavSetup->getAttributeGroupId($entityTypeId, $attributeSet->getId(), 'General');
    if ($groupId) {
        try {
            $eavSetup->addAttributeToGroup($entityTypeId, $attributeSet->getId(), $groupId, $attributeId);
        } catch (\Exception $e) {}
    }
}

echo "✅ Atributo adicionado a todos os attribute sets.\n";

// Marcar alguns produtos como destaque (primeiros 20 com imagem)
$productCollection = $objectManager->create(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class)->create();
$productCollection->addAttributeToSelect(['name', 'image'])
    ->addAttributeToFilter('status', 1)
    ->addAttributeToFilter('visibility', ['in' => [2, 3, 4]])
    ->addAttributeToFilter('image', ['neq' => 'no_selection'])
    ->addAttributeToFilter('image', ['notnull' => true])
    ->setPageSize(20);

$productAction = $objectManager->get(\Magento\Catalog\Model\Product\Action::class);
$storeId = 0;

$featuredIds = [];
foreach ($productCollection as $product) {
    $featuredIds[] = $product->getId();
}

if (!empty($featuredIds)) {
    $productAction->updateAttributes($featuredIds, ['featured' => 1], $storeId);
    echo "✅ " . count($featuredIds) . " produtos marcados como destaque.\n";
}

// Definir news_from_date e news_to_date para os 20 mais recentes
$newProductCollection = $objectManager->create(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class)->create();
$newProductCollection->addAttributeToSelect('*')
    ->addAttributeToFilter('status', 1)
    ->addAttributeToFilter('visibility', ['in' => [2, 3, 4]])
    ->setOrder('entity_id', 'DESC')
    ->setPageSize(20);

$newsFromDate = date('Y-m-d', strtotime('-7 days'));
$newsToDate = date('Y-m-d', strtotime('+30 days'));

$newIds = [];
foreach ($newProductCollection as $product) {
    $newIds[] = $product->getId();
}

if (!empty($newIds)) {
    $productAction->updateAttributes($newIds, [
        'news_from_date' => $newsFromDate,
        'news_to_date' => $newsToDate
    ], $storeId);
    echo "✅ " . count($newIds) . " produtos marcados como novidade (news_from/to_date).\n";
}

echo "\n🎉 Configuração concluída!\n";
echo "Execute: php bin/magento cache:flush && php bin/magento indexer:reindex\n";
