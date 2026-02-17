<?php
declare(strict_types=1);

namespace GrupoAwamotos\SmartSuggestions\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Install Schema for SmartSuggestions
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @inheritDoc
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context): void
    {
        $setup->startSetup();

        $this->createSuggestionHistoryTable($setup);

        $setup->endSetup();
    }

    /**
     * Create suggestion history table
     */
    private function createSuggestionHistoryTable(SchemaSetupInterface $setup): void
    {
        $tableName = $setup->getTable('smart_suggestions_history');

        if ($setup->getConnection()->isTableExists($tableName)) {
            return;
        }

        $table = $setup->getConnection()->newTable($tableName)
            ->addColumn(
                'history_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true],
                'History ID'
            )
            ->addColumn(
                'customer_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true],
                'Customer ID'
            )
            ->addColumn(
                'customer_name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Customer Name'
            )
            ->addColumn(
                'customer_phone',
                Table::TYPE_TEXT,
                50,
                ['nullable' => true],
                'Customer Phone'
            )
            ->addColumn(
                'suggestion_data',
                Table::TYPE_TEXT,
                '2M',
                ['nullable' => true],
                'Suggestion Data JSON'
            )
            ->addColumn(
                'total_value',
                Table::TYPE_DECIMAL,
                '12,4',
                ['nullable' => false, 'default' => '0.0000'],
                'Total Suggested Value'
            )
            ->addColumn(
                'products_count',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'default' => 0, 'unsigned' => true],
                'Products Count'
            )
            ->addColumn(
                'status',
                Table::TYPE_TEXT,
                50,
                ['nullable' => false, 'default' => 'generated'],
                'Status (generated, sent, converted, expired)'
            )
            ->addColumn(
                'channel',
                Table::TYPE_TEXT,
                50,
                ['nullable' => true],
                'Channel (whatsapp, email, manual)'
            )
            ->addColumn(
                'whatsapp_message_id',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'WhatsApp Message ID'
            )
            ->addColumn(
                'error_message',
                Table::TYPE_TEXT,
                '64k',
                ['nullable' => true],
                'Error Message'
            )
            ->addColumn(
                'converted_order_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => true, 'unsigned' => true],
                'Converted Order ID'
            )
            ->addColumn(
                'conversion_value',
                Table::TYPE_DECIMAL,
                '12,4',
                ['nullable' => true],
                'Actual Conversion Value'
            )
            ->addColumn(
                'admin_user_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => true, 'unsigned' => true],
                'Admin User ID'
            )
            ->addColumn(
                'notes',
                Table::TYPE_TEXT,
                '64k',
                ['nullable' => true],
                'Notes'
            )
            ->addColumn(
                'created_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Created At'
            )
            ->addColumn(
                'sent_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true],
                'Sent At'
            )
            ->addColumn(
                'converted_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => true],
                'Converted At'
            )
            ->addIndex(
                $setup->getIdxName($tableName, ['customer_id']),
                ['customer_id']
            )
            ->addIndex(
                $setup->getIdxName($tableName, ['status']),
                ['status']
            )
            ->addIndex(
                $setup->getIdxName($tableName, ['created_at']),
                ['created_at']
            )
            ->addIndex(
                $setup->getIdxName($tableName, ['channel']),
                ['channel']
            )
            ->setComment('Smart Suggestions History');

        $setup->getConnection()->createTable($table);
    }
}
