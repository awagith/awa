<?php
/**
 * Script de Teste de Conexão ERP - SQL Server
 *
 * Uso: php scripts/test_erp_connection.php
 */

use Magento\Framework\App\Bootstrap;

require dirname(__DIR__) . '/app/bootstrap.php';

$params = $_SERVER;
$bootstrap = Bootstrap::create(BP, $params);
$objectManager = $bootstrap->getObjectManager();

echo "============================================\n";
echo "  Teste de Conexão ERP - SQL Server\n";
echo "============================================\n\n";

// Get helper and connection
$helper = $objectManager->get(\GrupoAwamotos\ERPIntegration\Helper\Data::class);
$connection = $objectManager->get(\GrupoAwamotos\ERPIntegration\Api\ConnectionInterface::class);

// Check configuration
echo "📋 Configuração Atual:\n";
echo "   - Habilitado: " . ($helper->isEnabled() ? '✅ Sim' : '❌ Não') . "\n";
echo "   - Host: " . $helper->getHost() . "\n";
echo "   - Porta: " . $helper->getPort() . "\n";
echo "   - Database: " . $helper->getDatabase() . "\n";
echo "   - Usuário: " . $helper->getUsername() . "\n";
echo "   - Driver: " . $helper->getDriver() . "\n";
echo "   - Timeout: " . $helper->getConnectionTimeout() . "s\n";
echo "   - Trust Certificate: " . ($helper->getTrustServerCertificate() ? 'Sim' : 'Não') . "\n";
echo "\n";

// Check available drivers
echo "🔌 Drivers PDO Disponíveis:\n";
$pdoDrivers = PDO::getAvailableDrivers();
echo "   - " . implode(', ', $pdoDrivers) . "\n";

$sqlDrivers = array_intersect(['sqlsrv', 'dblib', 'odbc'], $pdoDrivers);
if (empty($sqlDrivers)) {
    echo "   ⚠️  Nenhum driver SQL Server encontrado!\n";
    echo "\n   Instale um dos seguintes:\n";
    echo "   - php-sqlsrv (Microsoft Driver - recomendado)\n";
    echo "   - php-sybase (FreeTDS/dblib)\n";
    echo "   - php-odbc (ODBC)\n";
    exit(1);
} else {
    echo "   ✅ Drivers SQL Server: " . implode(', ', $sqlDrivers) . "\n";
}
echo "\n";

// Test connection
echo "🔗 Testando Conexão...\n\n";

$result = $connection->testConnection();

if ($result['success']) {
    echo "✅ CONEXÃO ESTABELECIDA COM SUCESSO!\n\n";
    echo "📊 Informações do Servidor:\n";
    echo "   - Driver Usado: " . ($result['driver_used'] ?? 'N/A') . "\n";
    echo "   - Nome Servidor: " . ($result['server_name'] ?? 'N/A') . "\n";
    echo "   - Versão: " . ($result['version'] ?? 'N/A') . "\n";
    echo "   - Banco de Dados: " . ($result['database'] ?? 'N/A') . "\n";
    echo "   - Hora Servidor: " . ($result['server_time'] ?? 'N/A') . "\n";
    echo "   - Total Tabelas: " . ($result['table_count'] ?? 0) . "\n";

    if (!empty($result['sample_tables'])) {
        echo "\n📑 Tabelas de Exemplo:\n";
        foreach (array_slice($result['sample_tables'], 0, 10) as $table) {
            echo "   - $table\n";
        }
    }

    echo "\n🎉 Integração ERP pronta para uso!\n";
} else {
    echo "❌ FALHA NA CONEXÃO!\n\n";
    echo "   Erro: " . ($result['message'] ?? 'Desconhecido') . "\n";

    if (!empty($result['available_drivers'])) {
        echo "\n   Drivers disponíveis: " . implode(', ', $result['available_drivers']) . "\n";
    }

    if (!empty($result['troubleshooting'])) {
        echo "\n💡 Dicas de Solução:\n";
        foreach ($result['troubleshooting'] as $tip) {
            echo "   - $tip\n";
        }
    }

    if (!empty($result['installation_tips'])) {
        echo "\n📦 Para instalar drivers (Ubuntu/Debian):\n";
        echo "   sudo apt-get install php-sqlsrv   # Microsoft Driver\n";
        echo "   sudo apt-get install php-sybase   # FreeTDS\n";
        echo "   sudo apt-get install php-odbc     # ODBC\n";
    }
}

echo "\n============================================\n";
echo "  Teste concluído\n";
echo "============================================\n";
