<?php
/**
 * Script para explorar detalhes das tabelas principais do ERP
 *
 * Uso: php scripts/explore_erp_details.php
 */

use Magento\Framework\App\Bootstrap;

require dirname(__DIR__) . '/app/bootstrap.php';

$params = $_SERVER;
$bootstrap = Bootstrap::create(BP, $params);
$objectManager = $bootstrap->getObjectManager();

$connection = $objectManager->get(\GrupoAwamotos\ERPIntegration\Api\ConnectionInterface::class);

echo "============================================\n";
echo "  Detalhes das Tabelas Principais\n";
echo "============================================\n\n";

try {
    // Função para mostrar estrutura de uma tabela
    $showTable = function($tableName) use ($connection) {
        echo "📊 TABELA: $tableName\n";
        echo str_repeat("=", 60) . "\n";

        // Verifica se existe
        $exists = $connection->fetchOne("
            SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?
        ", [$tableName]);

        if (!$exists) {
            echo "   ⚠️ Tabela não encontrada\n\n";
            return;
        }

        // Colunas
        echo "Colunas:\n";
        $columns = $connection->query("
            SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, CHARACTER_MAXIMUM_LENGTH
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME = ?
            ORDER BY ORDINAL_POSITION
        ", [$tableName]);

        foreach ($columns as $col) {
            $type = $col['DATA_TYPE'];
            if ($col['CHARACTER_MAXIMUM_LENGTH']) {
                $type .= "(" . min($col['CHARACTER_MAXIMUM_LENGTH'], 255) . ")";
            }
            echo "   " . str_pad($col['COLUMN_NAME'], 35) . " $type\n";
        }

        // Conta registros
        try {
            $count = $connection->fetchColumn("SELECT COUNT(*) FROM [$tableName]");
            echo "\nTotal de registros: $count\n";
        } catch (\Exception $e) {
            echo "\n";
        }

        // Amostra
        echo "\nAmostra (5 registros):\n";
        try {
            $rows = $connection->query("SELECT TOP 5 * FROM [$tableName]");
            foreach ($rows as $idx => $row) {
                echo "--- Registro " . ($idx + 1) . " ---\n";
                $i = 0;
                foreach ($row as $key => $value) {
                    if ($i++ > 15) {
                        echo "   ... (mais colunas)\n";
                        break;
                    }
                    $displayValue = is_null($value) ? 'NULL' : substr((string)$value, 0, 60);
                    echo "   $key: $displayValue\n";
                }
            }
        } catch (\Exception $e) {
            echo "   Erro: " . $e->getMessage() . "\n";
        }

        echo "\n\n";
    };

    // 1. Tabela de Fornecedores/Clientes
    $showTable('FN_FORNECEDORES');

    // 2. Tabela de Materiais/Produtos
    $showTable('MT_MATERIAL');

    // 3. Tabela de Pedidos de Venda
    $showTable('VE_PEDIDO');

    // 4. Tabela de Itens do Pedido
    $showTable('VE_PEDIDOITENS');

    // 5. Histórico de Vendas
    $showTable('FN_VENDHISTORICO');

    // 6. Estoque
    echo "============================================\n";
    echo "  CONSULTA DE HISTÓRICO DE COMPRAS\n";
    echo "============================================\n\n";

    // Tenta construir uma query de histórico
    echo "Testando query de histórico de compras...\n\n";

    // Primeiro, vamos ver se conseguimos juntar pedido + itens + material + cliente
    $testQuery = $connection->query("
        SELECT TOP 10
            p.NROPEDIDO,
            p.DTPEDIDO,
            p.CODFORNECEDOR,
            f.FORNECEDOR as NOME_CLIENTE,
            f.CNPJCPF,
            i.CODMATERIAL,
            m.MATERIAL as NOME_MATERIAL,
            i.QUANTIDADE,
            i.VLRUNITARIO,
            i.VLRTOTAL
        FROM VE_PEDIDO p
        INNER JOIN VE_PEDIDOITENS i ON p.NROPEDIDO = i.NROPEDIDO AND p.FILIAL = i.FILIAL
        INNER JOIN MT_MATERIAL m ON i.CODMATERIAL = m.CODIGO
        LEFT JOIN FN_FORNECEDORES f ON p.CODFORNECEDOR = f.CODIGO
        WHERE p.SITUACAO NOT IN ('C', 'X')  -- Excluir cancelados
        ORDER BY p.DTPEDIDO DESC
    ");

    echo "📋 Últimos 10 pedidos com detalhes:\n";
    echo str_repeat("-", 100) . "\n";

    foreach ($testQuery as $row) {
        echo sprintf(
            "Pedido: %s | Data: %s | Cliente: %s (%s) | Material: %s | Qtd: %s | Valor: R$ %s\n",
            $row['NROPEDIDO'] ?? 'N/A',
            $row['DTPEDIDO'] ?? 'N/A',
            substr($row['NOME_CLIENTE'] ?? 'N/A', 0, 30),
            $row['CNPJCPF'] ?? 'N/A',
            substr($row['NOME_MATERIAL'] ?? 'N/A', 0, 30),
            $row['QUANTIDADE'] ?? '0',
            number_format((float)($row['VLRTOTAL'] ?? 0), 2, ',', '.')
        );
    }

    echo "\n\n";

    // Teste de agrupamento por cliente
    echo "📊 Top 10 Clientes por volume de compras:\n";
    echo str_repeat("-", 80) . "\n";

    $topClients = $connection->query("
        SELECT TOP 10
            p.CODFORNECEDOR,
            f.FORNECEDOR as NOME_CLIENTE,
            f.CNPJCPF,
            COUNT(DISTINCT p.NROPEDIDO) as TOTAL_PEDIDOS,
            SUM(i.VLRTOTAL) as VALOR_TOTAL
        FROM VE_PEDIDO p
        INNER JOIN VE_PEDIDOITENS i ON p.NROPEDIDO = i.NROPEDIDO AND p.FILIAL = i.FILIAL
        LEFT JOIN FN_FORNECEDORES f ON p.CODFORNECEDOR = f.CODIGO
        WHERE p.SITUACAO NOT IN ('C', 'X')
        GROUP BY p.CODFORNECEDOR, f.FORNECEDOR, f.CNPJCPF
        ORDER BY SUM(i.VLRTOTAL) DESC
    ");

    foreach ($topClients as $client) {
        echo sprintf(
            "Código: %s | Cliente: %s | CNPJ: %s | Pedidos: %d | Total: R$ %s\n",
            $client['CODFORNECEDOR'] ?? 'N/A',
            substr($client['NOME_CLIENTE'] ?? 'N/A', 0, 40),
            $client['CNPJCPF'] ?? 'N/A',
            $client['TOTAL_PEDIDOS'] ?? 0,
            number_format((float)($client['VALOR_TOTAL'] ?? 0), 2, ',', '.')
        );
    }

    echo "\n\n";

    // Teste de produtos mais vendidos por cliente
    echo "📊 Produtos mais comprados (exemplo para código de cliente 1):\n";
    echo str_repeat("-", 80) . "\n";

    $productsByClient = $connection->query("
        SELECT TOP 10
            m.CODIGO,
            m.MATERIAL as NOME_MATERIAL,
            m.CODORIGINAL as SKU,
            COUNT(*) as VEZES_COMPRADO,
            SUM(i.QUANTIDADE) as QTD_TOTAL,
            MAX(p.DTPEDIDO) as ULTIMA_COMPRA
        FROM VE_PEDIDO p
        INNER JOIN VE_PEDIDOITENS i ON p.NROPEDIDO = i.NROPEDIDO AND p.FILIAL = i.FILIAL
        INNER JOIN MT_MATERIAL m ON i.CODMATERIAL = m.CODIGO
        WHERE p.SITUACAO NOT IN ('C', 'X')
        GROUP BY m.CODIGO, m.MATERIAL, m.CODORIGINAL
        ORDER BY COUNT(*) DESC
    ");

    foreach ($productsByClient as $prod) {
        echo sprintf(
            "SKU: %s | Produto: %s | Compras: %d | Qtd Total: %s | Última: %s\n",
            $prod['SKU'] ?? 'N/A',
            substr($prod['NOME_MATERIAL'] ?? 'N/A', 0, 40),
            $prod['VEZES_COMPRADO'] ?? 0,
            $prod['QTD_TOTAL'] ?? 0,
            $prod['ULTIMA_COMPRA'] ?? 'N/A'
        );
    }

    echo "\n============================================\n";
    echo "  Exploração concluída\n";
    echo "============================================\n";

} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
    exit(1);
}
