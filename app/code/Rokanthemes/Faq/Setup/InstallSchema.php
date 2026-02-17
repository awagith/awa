<?php
namespace Rokanthemes\Faq\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws \Zend_Db_Exception
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        if (version_compare($context->getVersion(), '1.0.0') < 0) {
            $installer = $setup;
            $installer->startSetup();

            $RokanFaqTable = $installer->getConnection()->newTable(
                $installer->getTable('rokan_faq')
            )->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                [
                    'identity' => true,
                    'unsigned' => true,
                    'nullable' => false,
                    'primary' => true
                ],
                'Entity ID'
            )->addColumn(
                'status',
                Table::TYPE_TEXT,
                255,
                [],
                'Status'
            )->addColumn(
				'parent_id',
				Table::TYPE_INTEGER,
				11,
				['nullable' => false]
			)->addColumn(
				'question',
				Table::TYPE_TEXT,
				312,
				['nullable' => false]
			)->addColumn(
				'answer',
				Table::TYPE_TEXT,
				312,
				['nullable' => true]
			)->addColumn(
                'created_time',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Creation Time'
            )->addColumn(
                'updated_time',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
                'Updated Time'
            );
            $installer->getConnection()->createTable($RokanFaqTable);
			
            $installer->endSetup();
        }
    }
}
