<?php
/**
 * Script para testar histórico de compras e sugestões
 *
 * Uso: php scripts/test_purchase_history.php [CNPJ]
 *
 * Estrutura das tabelas do ERP:
 * - VE_PEDIDO: CODIGO (ID), CLIENTE (FK), STATUS, DTPEDIDO
 * - VE_PEDIDOITENS: CODIGO (ID), PEDIDO (FK), MATERIAL, DESCRICAO, QTDE, VLRUNITARIO, VLRTOTAL
 * - FN_FORNECEDORES: CODIGO (ID), CGC (CNPJ), RAZAO, FANTASIA, CKCLIENTE='S'
 */

use Magento\Framework\App\Bootstrap;

require dirname(__DIR__) . '/app/bootstrap.php';

$params = $_SERVER;
$bootstrap = Bootstrap::create(BP, $params);
$objectManager = $bootstrap->getObjectManager();

$connection = $objectManager->get(\GrupoAwamotos\ERPIntegration\Api\ConnectionInterface::class);

echo "============================================\n";
echo "  Histórico de Compras e Sugestões\n";
echo "============================================\n\n";

$cnpj = $argv[1] ?? null;

try {
    // 1. Top 20 Clientes por valor de compras
    echo "📊 TOP 20 CLIENTES POR VALOR DE COMPRAS:\n";
    echo str_repeat("-", 110) . "\n";

    $topClients = $connection->query("
        SELECT TOP 20
            f.CODIGO,
            f.RAZAO,
            f.FANTASIA,
            f.CGC,
            f.CIDADE,
            f.UF,
            COUNT(DISTINCT p.CODIGO) as TOTAL_PEDIDOS,
            SUM(i.VLRTOTAL) as VALOR_TOTAL
        FROM VE_PEDIDO p
        INNER JOIN VE_PEDIDOITENS i ON p.CODIGO = i.PEDIDO
        INNER JOIN FN_FORNECEDORES f ON p.CLIENTE = f.CODIGO
        WHERE p.STATUS NOT IN ('C', 'X')
        AND f.CKCLIENTE = 'S'
        GROUP BY f.CODIGO, f.RAZAO, f.FANTASIA, f.CGC, f.CIDADE, f.UF
        HAVING SUM(i.VLRTOTAL) > 0
        ORDER BY SUM(i.VLRTOTAL) DESC
    ");

    echo sprintf(
        "%-8s | %-45s | %-18s | %-6s | %-12s | %s\n",
        "Código", "Razão Social", "CNPJ", "Pedidos", "Cidade/UF", "Valor Total"
    );
    echo str_repeat("-", 110) . "\n";

    foreach ($topClients as $client) {
        echo sprintf(
            "%-8s | %-45s | %-18s | %-6d | %-12s | R$ %s\n",
            $client['CODIGO'],
            substr($client['RAZAO'] ?? $client['FANTASIA'] ?? 'N/A', 0, 45),
            $client['CGC'] ?? 'N/A',
            $client['TOTAL_PEDIDOS'] ?? 0,
            substr(($client['CIDADE'] ?? '') . '/' . ($client['UF'] ?? ''), 0, 12),
            number_format((float)($client['VALOR_TOTAL'] ?? 0), 2, ',', '.')
        );
    }

    echo "\n\n";

    // 2. Produtos mais vendidos
    echo "📊 TOP 20 PRODUTOS MAIS VENDIDOS:\n";
    echo str_repeat("-", 100) . "\n";

    $topProducts = $connection->query("
        SELECT TOP 20
            i.MATERIAL as CODIGO,
            i.DESCRICAO,
            COUNT(DISTINCT i.PEDIDO) as VEZES_VENDIDO,
            SUM(i.QTDE) as QTD_TOTAL,
            AVG(i.VLRUNITARIO) as PRECO_MEDIO
        FROM VE_PEDIDOITENS i
        INNER JOIN VE_PEDIDO p ON i.PEDIDO = p.CODIGO
        WHERE p.STATUS NOT IN ('C', 'X')
        GROUP BY i.MATERIAL, i.DESCRICAO
        HAVING SUM(i.QTDE) > 0
        ORDER BY SUM(i.QTDE) DESC
    ");

    echo sprintf(
        "%-12s | %-50s | %-8s | %-10s | %s\n",
        "Código", "Descrição", "Pedidos", "Qtd Total", "Preço Médio"
    );
    echo str_repeat("-", 100) . "\n";

    foreach ($topProducts as $prod) {
        echo sprintf(
            "%-12s | %-50s | %-8d | %-10s | R$ %s\n",
            $prod['CODIGO'] ?? 'N/A',
            substr($prod['DESCRICAO'] ?? 'N/A', 0, 50),
            $prod['VEZES_VENDIDO'] ?? 0,
            number_format((float)($prod['QTD_TOTAL'] ?? 0), 0, '', '.'),
            number_format((float)($prod['PRECO_MEDIO'] ?? 0), 2, ',', '.')
        );
    }

    echo "\n\n";

    // 3. Histórico de um cliente específico (se CNPJ fornecido)
    if ($cnpj) {
        $cleanCnpj = preg_replace('/[^0-9]/', '', $cnpj);

        // Busca cliente pelo CNPJ
        $cliente = $connection->fetchOne("
            SELECT CODIGO, RAZAO, FANTASIA, CGC, CIDADE, UF
            FROM FN_FORNECEDORES
            WHERE REPLACE(REPLACE(REPLACE(CGC, '.', ''), '-', ''), '/', '') = ?
            AND CKCLIENTE = 'S'
        ", [$cleanCnpj]);

        if ($cliente) {
            echo "============================================\n";
            echo "  HISTÓRICO DO CLIENTE\n";
            echo "============================================\n";
            echo "Cliente: " . ($cliente['RAZAO'] ?? $cliente['FANTASIA']) . "\n";
            echo "CNPJ: " . $cliente['CGC'] . "\n";
            echo "Cidade: " . ($cliente['CIDADE'] ?? '') . "/" . ($cliente['UF'] ?? '') . "\n";
            echo "Código ERP: " . $cliente['CODIGO'] . "\n\n";

            $clientCode = $cliente['CODIGO'];

            // Últimos pedidos
            echo "📋 ÚLTIMOS 10 PEDIDOS:\n";
            echo str_repeat("-", 80) . "\n";

            $orders = $connection->query("
                SELECT TOP 10
                    p.CODIGO,
                    p.DTPEDIDO,
                    p.STATUS,
                    COUNT(i.CODIGO) as QTD_ITENS,
                    SUM(i.VLRTOTAL) as VALOR_TOTAL
                FROM VE_PEDIDO p
                LEFT JOIN VE_PEDIDOITENS i ON p.CODIGO = i.PEDIDO
                WHERE p.CLIENTE = ?
                GROUP BY p.CODIGO, p.DTPEDIDO, p.STATUS
                ORDER BY p.DTPEDIDO DESC
            ", [$clientCode]);

            foreach ($orders as $order) {
                $status = match($order['STATUS'] ?? '') {
                    'F' => 'Faturado',
                    'A' => 'Aberto',
                    'P' => 'Pendente',
                    'C' => 'Cancelado',
                    'X' => 'Excluído',
                    default => $order['STATUS'] ?? 'N/A'
                };
                echo sprintf(
                    "Pedido: %s | Data: %s | Status: %-10s | Itens: %d | Valor: R$ %s\n",
                    $order['CODIGO'],
                    substr($order['DTPEDIDO'] ?? 'N/A', 0, 10),
                    $status,
                    $order['QTD_ITENS'] ?? 0,
                    number_format((float)($order['VALOR_TOTAL'] ?? 0), 2, ',', '.')
                );
            }

            echo "\n";

            // Produtos mais comprados pelo cliente
            echo "🛒 PRODUTOS MAIS COMPRADOS POR ESTE CLIENTE:\n";
            echo str_repeat("-", 90) . "\n";

            $clientProducts = $connection->query("
                SELECT TOP 15
                    i.MATERIAL as CODIGO,
                    i.DESCRICAO,
                    COUNT(DISTINCT i.PEDIDO) as VEZES_COMPRADO,
                    SUM(i.QTDE) as QTD_TOTAL,
                    AVG(i.VLRUNITARIO) as PRECO_MEDIO,
                    MAX(p.DTPEDIDO) as ULTIMA_COMPRA
                FROM VE_PEDIDOITENS i
                INNER JOIN VE_PEDIDO p ON i.PEDIDO = p.CODIGO
                WHERE p.CLIENTE = ?
                AND p.STATUS NOT IN ('C', 'X')
                GROUP BY i.MATERIAL, i.DESCRICAO
                ORDER BY SUM(i.QTDE) DESC
            ", [$clientCode]);

            foreach ($clientProducts as $prod) {
                echo sprintf(
                    "%-12s | %-45s | Compras: %d | Qtd: %s | Última: %s\n",
                    $prod['CODIGO'] ?? 'N/A',
                    substr($prod['DESCRICAO'] ?? 'N/A', 0, 45),
                    $prod['VEZES_COMPRADO'] ?? 0,
                    number_format((float)($prod['QTD_TOTAL'] ?? 0), 0, '', '.'),
                    substr($prod['ULTIMA_COMPRA'] ?? 'N/A', 0, 10)
                );
            }

            echo "\n";

            // Sugestão: produtos que outros clientes compraram junto
            echo "💡 SUGESTÕES (produtos que outros clientes também compraram):\n";
            echo str_repeat("-", 90) . "\n";

            // Pega os produtos que o cliente já comprou
            $clientMaterials = array_column($clientProducts, 'CODIGO');

            if (!empty($clientMaterials)) {
                $placeholders = implode(',', array_fill(0, count($clientMaterials), '?'));

                // Busca outros clientes que compraram os mesmos produtos
                $suggestions = $connection->query("
                    SELECT TOP 10
                        i2.MATERIAL as CODIGO,
                        i2.DESCRICAO,
                        COUNT(DISTINCT p2.CLIENTE) as CLIENTES_COMPRARAM,
                        SUM(i2.QTDE) as QTD_TOTAL
                    FROM VE_PEDIDOITENS i2
                    INNER JOIN VE_PEDIDO p2 ON i2.PEDIDO = p2.CODIGO
                    WHERE p2.CLIENTE IN (
                        SELECT DISTINCT p.CLIENTE
                        FROM VE_PEDIDO p
                        INNER JOIN VE_PEDIDOITENS i ON p.CODIGO = i.PEDIDO
                        WHERE i.MATERIAL IN ($placeholders)
                        AND p.CLIENTE <> ?
                        AND p.STATUS NOT IN ('C', 'X')
                    )
                    AND i2.MATERIAL NOT IN ($placeholders)
                    AND p2.STATUS NOT IN ('C', 'X')
                    GROUP BY i2.MATERIAL, i2.DESCRICAO
                    ORDER BY COUNT(DISTINCT p2.CLIENTE) DESC
                ", array_merge($clientMaterials, [$clientCode], $clientMaterials));

                foreach ($suggestions as $sug) {
                    echo sprintf(
                        "%-12s | %-50s | %d clientes também compraram\n",
                        $sug['CODIGO'] ?? 'N/A',
                        substr($sug['DESCRICAO'] ?? 'N/A', 0, 50),
                        $sug['CLIENTES_COMPRARAM'] ?? 0
                    );
                }
            }

        } else {
            echo "❌ Cliente com CNPJ $cnpj não encontrado.\n";
        }
    }

    echo "\n============================================\n";
    echo "  Consulta concluída\n";
    echo "============================================\n";

} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
    exit(1);
}
