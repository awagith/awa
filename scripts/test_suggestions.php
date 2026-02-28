<?php
/**
 * Script para testar o sistema de sugestoes de produtos ERP
 *
 * Uso: php scripts/test_suggestions.php [cnpj]
 */

use Magento\Framework\App\Bootstrap;

require dirname(__DIR__) . '/app/bootstrap.php';

$params = $_SERVER;
$bootstrap = Bootstrap::create(BP, $params);
$objectManager = $bootstrap->getObjectManager();

$purchaseHistory = $objectManager->get(\GrupoAwamotos\ERPIntegration\Model\PurchaseHistory::class);
$productSuggestion = $objectManager->get(\GrupoAwamotos\ERPIntegration\Model\ProductSuggestion::class);

echo "============================================\n";
echo "  Teste do Sistema de Sugestoes ERP\n";
echo "============================================\n\n";

// Get CNPJ from command line or use a test one
$cnpj = $argv[1] ?? '';

if (empty($cnpj)) {
    // Try to find a customer with history
    echo "Buscando um cliente com historico de compras...\n";

    $connection = $objectManager->get(\GrupoAwamotos\ERPIntegration\Api\ConnectionInterface::class);

    try {
        $topCustomer = $connection->fetchOne("
            SELECT TOP 1
                f.CODIGO,
                f.RAZAO,
                f.FANTASIA,
                f.CGC,
                COUNT(DISTINCT p.CODIGO) as total_pedidos
            FROM FN_FORNECEDORES f
            INNER JOIN VE_PEDIDO p ON f.CODIGO = p.CLIENTE
            WHERE f.CKCLIENTE = 'S'
            AND f.CGC IS NOT NULL
            AND f.CGC <> ''
            AND p.STATUS NOT IN ('C', 'X')
            GROUP BY f.CODIGO, f.RAZAO, f.FANTASIA, f.CGC
            HAVING COUNT(DISTINCT p.CODIGO) > 10
            ORDER BY COUNT(DISTINCT p.CODIGO) DESC
        ");

        if ($topCustomer) {
            $customerCode = (int)$topCustomer['CODIGO'];
            $cnpj = $topCustomer['CGC'];
            echo "Cliente encontrado:\n";
            echo "  Codigo: {$customerCode}\n";
            echo "  Razao: {$topCustomer['RAZAO']}\n";
            echo "  CNPJ: {$cnpj}\n";
            echo "  Pedidos: {$topCustomer['total_pedidos']}\n\n";
        } else {
            echo "Nenhum cliente encontrado com historico suficiente.\n";
            exit(1);
        }
    } catch (\Exception $e) {
        echo "Erro ao buscar cliente: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    $customerCode = $purchaseHistory->getCustomerCodeByCnpj($cnpj);
    if (!$customerCode) {
        echo "Cliente com CNPJ {$cnpj} nao encontrado no ERP.\n";
        exit(1);
    }
    echo "Cliente encontrado - Codigo ERP: {$customerCode}\n\n";
}

// Test 1: Customer Info
echo "1. INFORMACOES DO CLIENTE\n";
echo str_repeat("-", 50) . "\n";
$customerInfo = $purchaseHistory->getCustomerInfo($customerCode);
if ($customerInfo) {
    foreach ($customerInfo as $key => $value) {
        echo sprintf("   %s: %s\n", $key, $value ?? 'N/A');
    }
}
echo "\n";

// Test 2: Purchase Summary
echo "2. RESUMO DE COMPRAS\n";
echo str_repeat("-", 50) . "\n";
$summary = $purchaseHistory->getCustomerSummary($customerCode);
if ($summary) {
    echo "   Total de Pedidos: " . $summary['total_pedidos'] . "\n";
    echo "   Valor Total: R$ " . number_format($summary['valor_total'], 2, ',', '.') . "\n";
    echo "   Primeira Compra: " . ($summary['primeira_compra'] ?? 'N/A') . "\n";
    echo "   Ultima Compra: " . ($summary['ultima_compra'] ?? 'N/A') . "\n";
    echo "   Produtos Diferentes: " . $summary['produtos_diferentes'] . "\n";
}
echo "\n";

// Test 3: Last Orders
echo "3. ULTIMOS PEDIDOS\n";
echo str_repeat("-", 50) . "\n";
$orders = $purchaseHistory->getLastOrders($customerCode, 5);
foreach ($orders as $order) {
    echo sprintf(
        "   #%s | %s | %s | %d itens | R$ %s\n",
        $order['pedido_id'],
        substr($order['data_pedido'] ?? '', 0, 10),
        $order['status'],
        $order['qtd_itens'],
        number_format((float)$order['valor_total'], 2, ',', '.')
    );
}
echo "\n";

// Test 4: Most Purchased Products
echo "4. PRODUTOS MAIS COMPRADOS\n";
echo str_repeat("-", 50) . "\n";
$products = $purchaseHistory->getMostPurchasedProducts($customerCode, 5);
foreach ($products as $product) {
    echo sprintf(
        "   %s | %s\n   -> %d pedidos | %.0f un. | R$ %s/un.\n\n",
        $product['codigo_material'],
        substr($product['descricao'] ?? '', 0, 50),
        $product['vezes_comprado'],
        $product['quantidade_total'],
        number_format((float)($product['preco_medio'] ?? 0), 2, ',', '.')
    );
}

// Test 5: Product Suggestions
echo "5. SUGESTOES DE PRODUTOS\n";
echo str_repeat("-", 50) . "\n";
$suggestions = $productSuggestion->getSuggestions($customerCode, 5);
if (empty($suggestions)) {
    echo "   Nenhuma sugestao gerada (cliente pode ter historico limitado)\n";
} else {
    foreach ($suggestions as $suggestion) {
        $available = !empty($suggestion['available_in_store']) ? 'Disponivel' : 'Nao disponivel';
        echo sprintf(
            "   %s | %s\n   -> %d clientes compraram | %s no Magento\n\n",
            $suggestion['codigo_material'],
            substr($suggestion['descricao'] ?? '', 0, 50),
            $suggestion['clientes_compraram'],
            $available
        );
    }
}

// Test 6: Trending Products
echo "6. PRODUTOS EM ALTA (ultimos 30 dias)\n";
echo str_repeat("-", 50) . "\n";
$trending = $productSuggestion->getTrendingProducts(5);
foreach ($trending as $product) {
    echo sprintf(
        "   %s | %s\n   -> %d pedidos | %.0f un. vendidas\n\n",
        $product['codigo_material'],
        substr($product['descricao'] ?? '', 0, 50),
        $product['total_pedidos'],
        $product['quantidade_total']
    );
}

echo "============================================\n";
echo "  Teste concluido com sucesso!\n";
echo "============================================\n";
