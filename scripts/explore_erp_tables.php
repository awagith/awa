<?php
/**
 * Script para explorar tabelas do ERP
 * Identifica tabelas de clientes, pedidos e produtos
 *
 * Uso: php scripts/explore_erp_tables.php
 */

use Magento\Framework\App\Bootstrap;

require dirname(__DIR__) . '/app/bootstrap.php';

$params = $_SERVER;
$bootstrap = Bootstrap::create(BP, $params);
$objectManager = $bootstrap->getObjectManager();

$connection = $objectManager->get(\GrupoAwamotos\ERPIntegration\Api\ConnectionInterface::class);

echo "============================================\n";
echo "  Exploração de Tabelas do ERP\n";
echo "============================================\n\n";

try {
    $pdo = $connection->getConnection();

    // 1. Buscar tabelas de clientes
    echo "📋 TABELAS DE CLIENTES:\n";
    $clientTables = $connection->query("
        SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_TYPE = 'BASE TABLE'
        AND (TABLE_NAME LIKE '%CLIENTE%'
             OR TABLE_NAME LIKE '%FORNEC%'
             OR TABLE_NAME LIKE '%PARCEIRO%'
             OR TABLE_NAME LIKE '%PESSOA%'
             OR TABLE_NAME LIKE '%CUSTOMER%')
        ORDER BY TABLE_NAME
    ");
    foreach ($clientTables as $table) {
        echo "   - " . $table['TABLE_NAME'] . "\n";
    }
    echo "\n";

    // 2. Buscar tabelas de pedidos/vendas
    echo "📋 TABELAS DE PEDIDOS/VENDAS:\n";
    $orderTables = $connection->query("
        SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_TYPE = 'BASE TABLE'
        AND (TABLE_NAME LIKE '%PEDIDO%'
             OR TABLE_NAME LIKE '%VENDA%'
             OR TABLE_NAME LIKE '%ORDER%'
             OR TABLE_NAME LIKE '%NOTA%'
             OR TABLE_NAME LIKE '%FATURA%')
        ORDER BY TABLE_NAME
    ");
    foreach ($orderTables as $table) {
        echo "   - " . $table['TABLE_NAME'] . "\n";
    }
    echo "\n";

    // 3. Buscar tabelas de produtos/materiais
    echo "📋 TABELAS DE PRODUTOS/MATERIAIS:\n";
    $productTables = $connection->query("
        SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_TYPE = 'BASE TABLE'
        AND (TABLE_NAME LIKE '%MATERIAL%'
             OR TABLE_NAME LIKE '%PRODUTO%'
             OR TABLE_NAME LIKE '%ITEM%'
             OR TABLE_NAME LIKE '%ESTOQUE%')
        ORDER BY TABLE_NAME
    ");
    foreach ($productTables as $table) {
        echo "   - " . $table['TABLE_NAME'] . "\n";
    }
    echo "\n";

    // 4. Buscar tabelas de histórico
    echo "📋 TABELAS DE HISTÓRICO:\n";
    $historyTables = $connection->query("
        SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_TYPE = 'BASE TABLE'
        AND (TABLE_NAME LIKE '%HISTORICO%'
             OR TABLE_NAME LIKE '%LOG%'
             OR TABLE_NAME LIKE '%MOVIMENT%')
        ORDER BY TABLE_NAME
    ");
    foreach ($historyTables as $table) {
        echo "   - " . $table['TABLE_NAME'] . "\n";
    }
    echo "\n";

    // 5. Buscar estrutura de tabelas importantes
    $importantTables = [
        'CK_MATERIAL',   // Produtos
        'CK_CLIENTE',    // Clientes (se existir)
        'CK_PEDIDO',     // Pedidos
        'CK_PEDIDOITEM', // Itens do pedido
        'VD_PEDIDO',     // Vendas/Pedidos
        'VD_PEDIDOITEM', // Itens da venda
        'CK_ESTOQUE',    // Estoque
    ];

    echo "============================================\n";
    echo "  ESTRUTURA DAS TABELAS PRINCIPAIS\n";
    echo "============================================\n\n";

    foreach ($importantTables as $tableName) {
        // Verifica se tabela existe
        $exists = $connection->fetchOne("
            SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_NAME = ?
        ", [$tableName]);

        if ($exists) {
            echo "📊 TABELA: $tableName\n";
            echo str_repeat("-", 50) . "\n";

            // Busca colunas
            $columns = $connection->query("
                SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, CHARACTER_MAXIMUM_LENGTH
                FROM INFORMATION_SCHEMA.COLUMNS
                WHERE TABLE_NAME = ?
                ORDER BY ORDINAL_POSITION
            ", [$tableName]);

            foreach ($columns as $col) {
                $type = $col['DATA_TYPE'];
                if ($col['CHARACTER_MAXIMUM_LENGTH']) {
                    $type .= "(" . $col['CHARACTER_MAXIMUM_LENGTH'] . ")";
                }
                $nullable = $col['IS_NULLABLE'] === 'YES' ? 'NULL' : 'NOT NULL';
                echo "   " . str_pad($col['COLUMN_NAME'], 30) . " $type $nullable\n";
            }

            // Conta registros
            try {
                $count = $connection->fetchColumn("SELECT COUNT(*) FROM [$tableName]");
                echo "\n   Total de registros: $count\n";
            } catch (\Exception $e) {
                echo "\n   (Não foi possível contar registros)\n";
            }

            echo "\n";
        }
    }

    // 6. Buscar exemplo de clientes com compras
    echo "============================================\n";
    echo "  AMOSTRA DE DADOS\n";
    echo "============================================\n\n";

    // Tenta encontrar a tabela de clientes correta
    $clientTable = $connection->fetchOne("
        SELECT TOP 1 TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_TYPE = 'BASE TABLE'
        AND TABLE_NAME LIKE '%CLIENTE%'
        ORDER BY TABLE_NAME
    ");

    if ($clientTable) {
        $tableName = $clientTable['TABLE_NAME'];
        echo "📋 Amostra de clientes ($tableName):\n";
        $clients = $connection->query("SELECT TOP 5 * FROM [$tableName]");
        foreach ($clients as $idx => $client) {
            echo "   Cliente " . ($idx + 1) . ":\n";
            foreach (array_slice($client, 0, 8) as $key => $value) {
                echo "      $key: " . substr((string)$value, 0, 50) . "\n";
            }
            echo "\n";
        }
    }

    // Busca tabela de pedidos/vendas
    $orderTable = $connection->fetchOne("
        SELECT TOP 1 TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_TYPE = 'BASE TABLE'
        AND (TABLE_NAME LIKE 'VD_PEDIDO%' OR TABLE_NAME LIKE 'CK_PEDIDO%')
        AND TABLE_NAME NOT LIKE '%ITEM%'
        ORDER BY TABLE_NAME
    ");

    if ($orderTable) {
        $tableName = $orderTable['TABLE_NAME'];
        echo "📋 Amostra de pedidos ($tableName):\n";
        $orders = $connection->query("SELECT TOP 5 * FROM [$tableName] ORDER BY 1 DESC");
        foreach ($orders as $idx => $order) {
            echo "   Pedido " . ($idx + 1) . ":\n";
            foreach (array_slice($order, 0, 8) as $key => $value) {
                echo "      $key: " . substr((string)$value, 0, 50) . "\n";
            }
            echo "\n";
        }
    }

    echo "============================================\n";
    echo "  Exploração concluída\n";
    echo "============================================\n";

} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
