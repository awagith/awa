<?php
use Magento\Framework\App\Bootstrap;
require 'app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
$state = $om->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

$setupFactory = $om->create('\Magento\Eav\Setup\EavSetupFactory');
$setup = $om->create('\Magento\Framework\Setup\ModuleDataSetupInterface');
$eavSetup = $setupFactory->create(['setup' => $setup]);

$attrCode = 'product_manual_pdf';
if (!$eavSetup->getAttributeId(\Magento\Catalog\Model\Product::ENTITY, $attrCode)) {
    $eavSetup->addAttribute(
        \Magento\Catalog\Model\Product::ENTITY,
        $attrCode,
        [
            'type' => 'varchar',
            'label' => 'Ficha Técnica (PDF URL)',
            'input' => 'text',
            'required' => false,
            'sort_order' => 100,
            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
            'group' => 'General',
            'visible_on_front' => true,
            'is_used_in_grid' => true,
            'is_visible_in_grid' => true,
            'is_filterable_in_grid' => true
        ]
    );
    echo "Created attribute. \n";
} else {
    echo "Attribute exists. \n";
}

$rep = $om->get('\Magento\Catalog\Api\ProductRepositoryInterface');
try {
    $p = $rep->get('DEMO-NOTEBOOK-001', true);
    $p->setCustomAttribute($attrCode, 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf');
    $rep->save($p);
    echo 'Saved to DEMO-NOTEBOOK-001' . "\n";
} catch (\Exception $e) {
    echo $e->getMessage() . "\n";
}
