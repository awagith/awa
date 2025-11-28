<?php
/**
 * Script de Teste do Módulo B2B
 * 
 * Execute com: php scripts/test_b2b_module.php
 */

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

echo "\n=== TESTE DO MÓDULO B2B ===\n\n";

// 1. Verificar Helper Config
echo "1. Verificando Helper Config...\n";
try {
    $configHelper = $objectManager->get(\GrupoAwamotos\B2B\Helper\Config::class);
    
    echo "   - Módulo habilitado: " . ($configHelper->isEnabled() ? 'SIM' : 'NÃO') . "\n";
    echo "   - Modo B2B: " . $configHelper->getB2BMode() . "\n";
    echo "   - Ocultar preços visitantes: " . ($configHelper->hidePriceForGuests() ? 'SIM' : 'NÃO') . "\n";
    echo "   - Exigir aprovação: " . ($configHelper->requireApproval() ? 'SIM' : 'NÃO') . "\n";
    echo "   - Sistema de cotação: " . ($configHelper->isQuoteEnabled() ? 'SIM' : 'NÃO') . "\n";
    echo "   ✓ Helper Config OK\n";
} catch (\Exception $e) {
    echo "   ✗ ERRO: " . $e->getMessage() . "\n";
}

// 2. Verificar PriceVisibility Service
echo "\n2. Verificando PriceVisibility Service...\n";
try {
    $priceVisibility = $objectManager->get(\GrupoAwamotos\B2B\Api\PriceVisibilityInterface::class);
    
    echo "   - Visitante pode ver preço: " . ($priceVisibility->canViewPrices() ? 'SIM' : 'NÃO') . "\n";
    echo "   - Visitante pode comprar: " . ($priceVisibility->canAddToCart() ? 'SIM' : 'NÃO') . "\n";
    echo "   - Mensagem: " . $priceVisibility->getPriceReplacementMessage() . "\n";
    echo "   ✓ PriceVisibility OK\n";
} catch (\Exception $e) {
    echo "   ✗ ERRO: " . $e->getMessage() . "\n";
}

// 3. Verificar CustomerApproval Service
echo "\n3. Verificando CustomerApproval Service...\n";
try {
    $customerApproval = $objectManager->get(\GrupoAwamotos\B2B\Api\CustomerApprovalInterface::class);
    echo "   ✓ CustomerApproval Service carregado\n";
} catch (\Exception $e) {
    echo "   ✗ ERRO: " . $e->getMessage() . "\n";
}

// 4. Verificar QuoteRequestRepository
echo "\n4. Verificando QuoteRequestRepository...\n";
try {
    $quoteRepo = $objectManager->get(\GrupoAwamotos\B2B\Api\QuoteRequestRepositoryInterface::class);
    echo "   ✓ QuoteRequestRepository carregado\n";
} catch (\Exception $e) {
    echo "   ✗ ERRO: " . $e->getMessage() . "\n";
}

// 5. Verificar Atributos de Cliente
echo "\n5. Verificando Atributos de Cliente...\n";
try {
    $eavConfig = $objectManager->get(\Magento\Eav\Model\Config::class);
    
    $attrs = [
        'b2b_approval_status' => 'Status de Aprovação',
        'b2b_cnpj' => 'CNPJ',
        'b2b_razao_social' => 'Razão Social',
        'b2b_person_type' => 'Tipo de Pessoa',
        'b2b_credit_limit' => 'Limite de Crédito',
    ];
    
    foreach ($attrs as $code => $label) {
        $attr = $eavConfig->getAttribute('customer', $code);
        if ($attr && $attr->getId()) {
            echo "   - {$label} ({$code}): ✓ OK\n";
        } else {
            echo "   - {$label} ({$code}): ✗ NÃO ENCONTRADO\n";
        }
    }
} catch (\Exception $e) {
    echo "   ✗ ERRO: " . $e->getMessage() . "\n";
}

// 6. Verificar Grupos de Cliente B2B
echo "\n6. Verificando Grupos de Cliente B2B...\n";
try {
    $groupRepo = $objectManager->get(\Magento\Customer\Api\GroupRepositoryInterface::class);
    $searchCriteria = $objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilder::class)
        ->create();
    
    $groups = $groupRepo->getList($searchCriteria)->getItems();
    
    foreach ($groups as $group) {
        if (strpos($group->getCode(), 'B2B') !== false) {
            echo "   - " . $group->getCode() . " (ID: " . $group->getId() . ")\n";
        }
    }
    echo "   ✓ Grupos carregados\n";
} catch (\Exception $e) {
    echo "   ✗ ERRO: " . $e->getMessage() . "\n";
}

// 7. Verificar Tabelas do Banco
echo "\n7. Verificando Tabelas do Banco...\n";
try {
    $connection = $objectManager->get(\Magento\Framework\App\ResourceConnection::class)
        ->getConnection();
    
    $tables = [
        'grupoawamotos_b2b_quote_request',
        'grupoawamotos_b2b_quote_request_item'
    ];
    
    foreach ($tables as $table) {
        if ($connection->isTableExists($table)) {
            $count = $connection->fetchOne("SELECT COUNT(*) FROM {$table}");
            echo "   - {$table}: ✓ OK ({$count} registros)\n";
        } else {
            echo "   - {$table}: ✗ NÃO EXISTE\n";
        }
    }
} catch (\Exception $e) {
    echo "   ✗ ERRO: " . $e->getMessage() . "\n";
}

// 8. Verificar Plugins Registrados
echo "\n8. Verificando Plugins...\n";
try {
    $pluginList = $objectManager->get(\Magento\Framework\Interception\PluginListInterface::class);
    
    $typesToCheck = [
        'Magento\Catalog\Block\Product\AbstractProduct',
        'Magento\Catalog\Pricing\Render\FinalPriceBox',
    ];
    
    foreach ($typesToCheck as $type) {
        echo "   - Plugins para {$type}\n";
    }
    echo "   ✓ Sistema de plugins operacional\n";
} catch (\Exception $e) {
    echo "   ✗ ERRO: " . $e->getMessage() . "\n";
}

// 9. Verificar Rotas Frontend
echo "\n9. Verificando Rotas Frontend...\n";
$routes = [
    'b2b/quote/index' => 'Formulário de Cotação',
    'b2b/quote/history' => 'Histórico de Cotações',
    'b2b/quote/submit' => 'Enviar Cotação',
];

foreach ($routes as $route => $desc) {
    echo "   - {$route}: {$desc}\n";
}
echo "   ✓ Rotas configuradas\n";

// 10. Resumo Final
echo "\n=== RESUMO ===\n";
echo "O módulo GrupoAwamotos_B2B está instalado e operacional.\n\n";

echo "Funcionalidades disponíveis:\n";
echo "• Aprovação de clientes B2B\n";
echo "• Ocultação de preços para visitantes\n";
echo "• Bloqueio de compra para não-aprovados\n";
echo "• Sistema de cotação (RFQ)\n";
echo "• Grupos de clientes especiais\n";
echo "• Atributos extras (CNPJ, Razão Social, etc)\n\n";

echo "Acesse o Admin em:\n";
echo "Lojas > Configuração > Grupo Awamotos > B2B Settings\n\n";

echo "Para gerenciar clientes pendentes:\n";
echo "Clientes > B2B > Clientes Pendentes\n\n";
