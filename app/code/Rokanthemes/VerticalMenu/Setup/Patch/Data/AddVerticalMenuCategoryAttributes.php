<?php
/**
 * Data patch to create vc_menu_* category attributes for Vertical Menu.
 */

namespace Rokanthemes\VerticalMenu\Setup\Patch\Data;

use Magento\Catalog\Setup\CategorySetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class AddVerticalMenuCategoryAttributes implements DataPatchInterface
{
    private ModuleDataSetupInterface $moduleDataSetup;
    private CategorySetupFactory $categorySetupFactory;

    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        CategorySetupFactory $categorySetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->categorySetupFactory = $categorySetupFactory;
    }

    public function apply(): self
    {
        $categorySetup = $this->categorySetupFactory->create(['setup' => $this->moduleDataSetup]);
        $entityTypeId = $categorySetup->getEntityTypeId(\Magento\Catalog\Model\Category::ENTITY);
        $attributeSetId = $categorySetup->getDefaultAttributeSetId($entityTypeId);

        $attributes = [
            'vc_menu_hide_item' => [
                'type' => 'int',
                'label' => 'Hide This Menu Item',
                'input' => 'select',
                'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                'required' => false,
                'sort_order' => 10,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Vertical Menu',
            ],
            'vc_menu_type' => [
                'type' => 'varchar',
                'label' => 'Menu Type',
                'input' => 'select',
                'source' => 'Rokanthemes\VerticalMenu\Model\Attribute\Menutype',
                'required' => false,
                'sort_order' => 20,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Vertical Menu',
            ],
            'vc_menu_static_width' => [
                'type' => 'varchar',
                'label' => 'Static Width',
                'input' => 'text',
                'required' => false,
                'sort_order' => 30,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Vertical Menu',
            ],
            'vc_menu_cat_columns' => [
                'type' => 'varchar',
                'label' => 'Sub Category Columns',
                'input' => 'select',
                'source' => 'Rokanthemes\VerticalMenu\Model\Attribute\Subcatcolumns',
                'required' => false,
                'sort_order' => 40,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Vertical Menu',
            ],
            'vc_menu_float_type' => [
                'type' => 'varchar',
                'label' => 'Float',
                'input' => 'select',
                'source' => 'Rokanthemes\VerticalMenu\Model\Attribute\Floattype',
                'required' => false,
                'sort_order' => 50,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Vertical Menu',
            ],
            'vc_menu_cat_label' => [
                'type' => 'varchar',
                'label' => 'Category Label',
                'input' => 'select',
                'source' => 'Rokanthemes\VerticalMenu\Model\Attribute\Categorylabel',
                'required' => false,
                'sort_order' => 60,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Vertical Menu',
            ],
            'vc_menu_icon_img' => [
                'type' => 'varchar',
                'label' => 'Icon Image',
                'input' => 'image',
                'backend' => 'Rokanthemes\VerticalMenu\Model\Category\Attribute\Backend\Iconimage',
                'required' => false,
                'sort_order' => 70,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Vertical Menu',
            ],
            'vc_menu_font_icon' => [
                'type' => 'varchar',
                'label' => 'Font Icon Class',
                'input' => 'text',
                'required' => false,
                'sort_order' => 80,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Vertical Menu',
            ],
            'vc_menu_block_top_content' => [
                'type' => 'text',
                'label' => 'Top Block',
                'input' => 'textarea',
                'required' => false,
                'sort_order' => 90,
                'wysiwyg_enabled' => true,
                'is_html_allowed_on_front' => true,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Vertical Menu',
            ],
            'vc_menu_block_left_width' => [
                'type' => 'varchar',
                'label' => 'Left Block Width',
                'input' => 'select',
                'source' => 'Rokanthemes\VerticalMenu\Model\Attribute\Width',
                'required' => false,
                'sort_order' => 100,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Vertical Menu',
            ],
            'vc_menu_block_left_content' => [
                'type' => 'text',
                'label' => 'Left Block',
                'input' => 'textarea',
                'required' => false,
                'sort_order' => 110,
                'wysiwyg_enabled' => true,
                'is_html_allowed_on_front' => true,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Vertical Menu',
            ],
            'vc_menu_block_right_width' => [
                'type' => 'varchar',
                'label' => 'Right Block Width',
                'input' => 'select',
                'source' => 'Rokanthemes\VerticalMenu\Model\Attribute\Width',
                'required' => false,
                'sort_order' => 120,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Vertical Menu',
            ],
            'vc_menu_block_right_content' => [
                'type' => 'text',
                'label' => 'Right Block',
                'input' => 'textarea',
                'required' => false,
                'sort_order' => 130,
                'wysiwyg_enabled' => true,
                'is_html_allowed_on_front' => true,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Vertical Menu',
            ],
            'vc_menu_block_bottom_content' => [
                'type' => 'text',
                'label' => 'Bottom Block',
                'input' => 'textarea',
                'required' => false,
                'sort_order' => 140,
                'wysiwyg_enabled' => true,
                'is_html_allowed_on_front' => true,
                'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'group' => 'Vertical Menu',
            ],
        ];

        foreach ($attributes as $code => $data) {
            $categorySetup->addAttribute(
                \Magento\Catalog\Model\Category::ENTITY,
                $code,
                $data
            );
        }

        $groupId = $categorySetup->getAttributeGroupId($entityTypeId, $attributeSetId, 'Vertical Menu');

        foreach ($attributes as $code => $data) {
            $categorySetup->addAttributeToGroup(
                $entityTypeId,
                $attributeSetId,
                $groupId,
                $code,
                $data['sort_order']
            );
        }

        return $this;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
