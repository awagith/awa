<?php
/**
 * Script para listar colunas de uma tabela
 */

use Magento\Framework\App\Bootstrap;

require dirname(__DIR__) . '/app/bootstrap.php';

$params = $_SERVER;
$bootstrap = Bootstrap::create(BP, $params);
$objectManager = $bootstrap->getObjectManager();

$connection = $objectManager->get(\GrupoAwamotos\ERPIntegration\Api\ConnectionInterface::class);

$tables = ['VE_PEDIDO', 'VE_PEDIDOITENS', 'FN_FORNECEDORES'];

foreach ($tables as $tableName) {
    echo "\n📊 TABELA: $tableName\n";
    echo str_repeat("=", 60) . "\n";

    $columns = $connection->query("
        SELECT COLUMN_NAME, DATA_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = ?
        ORDER BY ORDINAL_POSITION
    ", [$tableName]);

    foreach ($columns as $col) {
        echo "   " . $col['COLUMN_NAME'] . " (" . $col['DATA_TYPE'] . ")\n";
    }

    // Amostra de um registro
    echo "\nAmostra:\n";
    $sample = $connection->query("SELECT TOP 1 * FROM [$tableName]");
    if (!empty($sample)) {
        foreach ($sample[0] as $key => $value) {
            echo "   $key: " . substr((string)$value, 0, 50) . "\n";
        }
    }
}
