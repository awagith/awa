<?php
/**
 * Schema para integração com Power BI - Sistema REXIS ML
 * Replica estrutura do dataset_recomendacao e df_network
 */
namespace GrupoAwamotos\RexisML\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        /**
         * Tabela 1: dataset_recomendacao
         * Replica a tabela principal de recomendações do Power BI
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('rexis_dataset_recomendacao')
        )->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )->addColumn(
            'chave_global',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Chave Global (Cliente-Produto-Mes)'
        )->addColumn(
            'mes_rexis_code',
            Table::TYPE_TEXT,
            10,
            ['nullable' => false],
            'Código do Mês REXIS (ex: 11-2025)'
        )->addColumn(
            'identificador_cliente',
            Table::TYPE_TEXT,
            50,
            ['nullable' => false],
            'ID do Cliente (CNPJ ou código ERP)'
        )->addColumn(
            'customer_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => true],
            'Customer ID Magento'
        )->addColumn(
            'identificador_produto',
            Table::TYPE_TEXT,
            50,
            ['nullable' => false],
            'ID do Produto (SKU ou código ERP)'
        )->addColumn(
            'product_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => true],
            'Product ID Magento'
        )->addColumn(
            'classificacao_cliente',
            Table::TYPE_TEXT,
            50,
            ['nullable' => true],
            'Classificação do Cliente (Recorrente, Novo, Inativo, etc.)'
        )->addColumn(
            'classificacao_produto',
            Table::TYPE_TEXT,
            50,
            ['nullable' => true],
            'Classificação do Produto (Churn, Cross-sell, Irregular)'
        )->addColumn(
            'ja_comprou',
            Table::TYPE_BOOLEAN,
            null,
            ['nullable' => false, 'default' => 0],
            'Cliente já comprou esse produto?'
        )->addColumn(
            'pred',
            Table::TYPE_DECIMAL,
            '10,4',
            ['nullable' => true],
            'Predição do Modelo ML (Score 0-1)'
        )->addColumn(
            'probabilidade_compra',
            Table::TYPE_DECIMAL,
            '10,4',
            ['nullable' => true],
            'Probabilidade de Compra (%)'
        )->addColumn(
            'previsao_gasto_round_up',
            Table::TYPE_DECIMAL,
            '12,2',
            ['nullable' => true],
            'Previsão de Gasto (Arredondado)'
        )->addColumn(
            'valor_total_esperado',
            Table::TYPE_DECIMAL,
            '12,2',
            ['nullable' => true],
            'Valor Total Esperado'
        )->addColumn(
            'valor_convertida',
            Table::TYPE_DECIMAL,
            '12,2',
            ['nullable' => true, 'default' => 0],
            'Valor Convertido (Real)'
        )->addColumn(
            'quantidade_convertida',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => true, 'default' => 0],
            'Quantidade Convertida (Real)'
        )->addColumn(
            'valor_unitario',
            Table::TYPE_DECIMAL,
            '12,2',
            ['nullable' => true],
            'Valor Unitário do Produto'
        )->addColumn(
            'tipo_recomendacao',
            Table::TYPE_TEXT,
            50,
            ['nullable' => true],
            'Tipo de Recomendação (Cross-sell, Upsell, Reativação)'
        )->addColumn(
            'recencia',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Recência (dias desde última compra do produto)'
        )->addColumn(
            'created_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
            'Data de Criação'
        )->addColumn(
            'updated_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
            'Data de Atualização'
        )->addIndex(
            $installer->getIdxName('rexis_dataset_recomendacao', ['chave_global']),
            ['chave_global'],
            ['type' => 'unique']
        )->addIndex(
            $installer->getIdxName('rexis_dataset_recomendacao', ['mes_rexis_code']),
            ['mes_rexis_code']
        )->addIndex(
            $installer->getIdxName('rexis_dataset_recomendacao', ['identificador_cliente']),
            ['identificador_cliente']
        )->addIndex(
            $installer->getIdxName('rexis_dataset_recomendacao', ['customer_id']),
            ['customer_id']
        )->addIndex(
            $installer->getIdxName('rexis_dataset_recomendacao', ['classificacao_produto']),
            ['classificacao_produto']
        )->addForeignKey(
            $installer->getFkName('rexis_dataset_recomendacao', 'customer_id', 'customer_entity', 'entity_id'),
            'customer_id',
            $installer->getTable('customer_entity'),
            'entity_id',
            Table::ACTION_SET_NULL
        )->setComment('Dataset de Recomendações REXIS ML');

        $installer->getConnection()->createTable($table);

        /**
         * Tabela 2: df_network (Market Basket Analysis)
         * Regras de associação entre produtos
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('rexis_network_rules')
        )->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )->addColumn(
            'antecedent',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Produto Antecedente (SKU)'
        )->addColumn(
            'consequent',
            Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Produto Consequente (SKU)'
        )->addColumn(
            'antecedents',
            Table::TYPE_TEXT,
            '64k',
            ['nullable' => true],
            'Lista de Antecedentes (JSON)'
        )->addColumn(
            'consequents',
            Table::TYPE_TEXT,
            '64k',
            ['nullable' => true],
            'Lista de Consequentes (JSON)'
        )->addColumn(
            'support',
            Table::TYPE_DECIMAL,
            '10,6',
            ['nullable' => false],
            'Support (Frequência)'
        )->addColumn(
            'confidence',
            Table::TYPE_DECIMAL,
            '10,6',
            ['nullable' => false],
            'Confidence (Confiança da regra)'
        )->addColumn(
            'lift',
            Table::TYPE_DECIMAL,
            '10,4',
            ['nullable' => false],
            'Lift (Força da associação)'
        )->addColumn(
            'conviction',
            Table::TYPE_DECIMAL,
            '10,4',
            ['nullable' => true],
            'Conviction'
        )->addColumn(
            'leverage',
            Table::TYPE_DECIMAL,
            '10,6',
            ['nullable' => true],
            'Leverage'
        )->addColumn(
            'is_active',
            Table::TYPE_BOOLEAN,
            null,
            ['nullable' => false, 'default' => 1],
            'Regra Ativa?'
        )->addColumn(
            'created_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
            'Data de Criação'
        )->addColumn(
            'updated_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
            'Data de Atualização'
        )->addIndex(
            $installer->getIdxName('rexis_network_rules', ['antecedent', 'consequent']),
            ['antecedent', 'consequent'],
            ['type' => 'unique']
        )->addIndex(
            $installer->getIdxName('rexis_network_rules', ['lift']),
            ['lift']
        )->addIndex(
            $installer->getIdxName('rexis_network_rules', ['confidence']),
            ['confidence']
        )->setComment('Regras de Associação - Market Basket Analysis');

        $installer->getConnection()->createTable($table);

        /**
         * Tabela 3: Classificação de Clientes (RFM)
         * Cache das classificações calculadas
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('rexis_customer_classification')
        )->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )->addColumn(
            'customer_id',
            Table::TYPE_INTEGER,
            null,
            ['unsigned' => true, 'nullable' => false],
            'Customer ID'
        )->addColumn(
            'identificador_cliente',
            Table::TYPE_TEXT,
            50,
            ['nullable' => false],
            'Identificador ERP (CNPJ)'
        )->addColumn(
            'mes_rexis_code',
            Table::TYPE_TEXT,
            10,
            ['nullable' => false],
            'Mês de Referência'
        )->addColumn(
            'classificacao_cliente',
            Table::TYPE_TEXT,
            50,
            ['nullable' => false],
            'Classificação (Recorrente, Novo, Inativo, etc.)'
        )->addColumn(
            'rfm_score',
            Table::TYPE_TEXT,
            10,
            ['nullable' => true],
            'Score RFM (ex: 555, 111)'
        )->addColumn(
            'recency',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Recência (dias desde última compra)'
        )->addColumn(
            'frequency',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => true],
            'Frequência (número de pedidos)'
        )->addColumn(
            'monetary',
            Table::TYPE_DECIMAL,
            '12,2',
            ['nullable' => true],
            'Monetary (valor total gasto)'
        )->addColumn(
            'mean_ticket_per_order',
            Table::TYPE_DECIMAL,
            '12,2',
            ['nullable' => true],
            'Ticket Médio por Pedido'
        )->addColumn(
            'ltv',
            Table::TYPE_DECIMAL,
            '12,2',
            ['nullable' => true],
            'Life Time Value'
        )->addColumn(
            'created_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
            'Data de Criação'
        )->addColumn(
            'updated_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
            'Data de Atualização'
        )->addIndex(
            $installer->getIdxName('rexis_customer_classification', ['customer_id', 'mes_rexis_code']),
            ['customer_id', 'mes_rexis_code'],
            ['type' => 'unique']
        )->addIndex(
            $installer->getIdxName('rexis_customer_classification', ['classificacao_cliente']),
            ['classificacao_cliente']
        )->addForeignKey(
            $installer->getFkName('rexis_customer_classification', 'customer_id', 'customer_entity', 'entity_id'),
            'customer_id',
            $installer->getTable('customer_entity'),
            'entity_id',
            Table::ACTION_CASCADE
        )->setComment('Classificação de Clientes - RFM');

        $installer->getConnection()->createTable($table);

        /**
         * Tabela 4: Métricas de Conversão
         * Armazena as medidas do Power BI
         */
        $table = $installer->getConnection()->newTable(
            $installer->getTable('rexis_metricas_conversao')
        )->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'ID'
        )->addColumn(
            'mes_rexis_code',
            Table::TYPE_TEXT,
            10,
            ['nullable' => false],
            'Mês de Referência'
        )->addColumn(
            'n_clientes_rec_mes_atual',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'default' => 0],
            'Nº Clientes Recomendados'
        )->addColumn(
            'n_cliente_comprou_mes_atual',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'default' => 0],
            'Nº Clientes que Compraram'
        )->addColumn(
            'perc_conversao_cliente',
            Table::TYPE_DECIMAL,
            '10,4',
            ['nullable' => true],
            '% Conversão Cliente'
        )->addColumn(
            'n_produto_rec_mes_atual',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'default' => 0],
            'Nº Produtos Recomendados'
        )->addColumn(
            'n_produto_comprou_mes_atual',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'default' => 0],
            'Nº Produtos Comprados'
        )->addColumn(
            'perc_conversao_produto',
            Table::TYPE_DECIMAL,
            '10,4',
            ['nullable' => true],
            '% Conversão Produto'
        )->addColumn(
            'valor_esperado_atual',
            Table::TYPE_DECIMAL,
            '12,2',
            ['nullable' => true, 'default' => 0],
            'Valor Esperado Total'
        )->addColumn(
            'valor_convertido_atual',
            Table::TYPE_DECIMAL,
            '12,2',
            ['nullable' => true, 'default' => 0],
            'Valor Convertido Total'
        )->addColumn(
            'created_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
            'Data de Criação'
        )->addColumn(
            'updated_at',
            Table::TYPE_TIMESTAMP,
            null,
            ['nullable' => false, 'default' => Table::TIMESTAMP_INIT_UPDATE],
            'Data de Atualização'
        )->addIndex(
            $installer->getIdxName('rexis_metricas_conversao', ['mes_rexis_code']),
            ['mes_rexis_code'],
            ['type' => 'unique']
        )->setComment('Métricas de Conversão REXIS');

        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}
