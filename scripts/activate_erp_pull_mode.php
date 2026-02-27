<?php
declare(strict_types=1);

/**
 * Script para ativar modo PULL de sincronização de pedidos com ERP Sectra
 *
 * O que faz:
 * 1. Verifica pré-requisitos (tabelas, colunas, conexão ERP)
 * 2. Ativa as configs necessárias no core_config_data
 * 3. Verifica a coluna customer_erp_code em sales_order
 * 4. Testa se a API REST está acessível
 *
 * Uso: php scripts/activate_erp_pull_mode.php
 */

echo "====================================================\n";
echo "  ATIVAR MODO PULL - SINCRONIZACAO PEDIDOS ERP\n";
echo "  " . date('Y-m-d H:i:s') . "\n";
echo "====================================================\n\n";

// 1. Conectar MySQL
$envFile = dirname(__DIR__) . '/app/etc/env.php';
$env = include $envFile;
$db = $env['db']['connection']['default'] ?? [];
$socket = $db['unix_socket'] ?? '';
$dsn = $socket
    ? "mysql:unix_socket=$socket;dbname=" . ($db['dbname'] ?? 'magento')
    : "mysql:host=" . ($db['host'] ?? 'localhost') . ";dbname=" . ($db['dbname'] ?? 'magento');

try {
    $mysql = new PDO($dsn, $db['username'] ?? '', $db['password'] ?? '');
    $mysql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "[OK] MySQL conectado\n";
} catch (\PDOException $e) {
    echo "[ERRO] MySQL: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Verificar tabelas do módulo
echo "\n== VERIFICANDO TABELAS ==\n";
$requiredTables = [
    'grupoawamotos_erp_entity_map' => 'Mapeamento IDs Magento <-> ERP',
    'grupoawamotos_erp_sync_log'   => 'Log de sincronizações',
];

$missingTables = [];
foreach ($requiredTables as $table => $desc) {
    $exists = $mysql->query("SHOW TABLES LIKE '$table'")->rowCount() > 0;
    echo "  " . ($exists ? '[OK]' : '[FALTA]') . " $table ($desc)\n";
    if (!$exists) {
        $missingTables[] = $table;
    }
}

if (!empty($missingTables)) {
    echo "\n  *** Tabelas faltando! Rode: sudo -u www-data php bin/magento setup:upgrade ***\n";
}

// 3. Verificar coluna customer_erp_code em sales_order
echo "\n== VERIFICANDO COLUNA customer_erp_code ==\n";
$col = $mysql->query(
    "SELECT COLUMN_NAME FROM information_schema.columns 
     WHERE table_schema = DATABASE() AND table_name = 'sales_order' AND column_name = 'customer_erp_code'"
)->fetch();

if ($col) {
    echo "  [OK] Coluna customer_erp_code existe em sales_order\n";
} else {
    echo "  [FALTA] Coluna customer_erp_code NAO existe em sales_order\n";
    echo "  Criando...\n";
    try {
        $mysql->exec("ALTER TABLE sales_order ADD COLUMN customer_erp_code VARCHAR(50) DEFAULT NULL COMMENT 'ERP Client Code'");
        echo "  [OK] Coluna criada com sucesso!\n";
    } catch (\PDOException $e) {
        echo "  [ERRO] " . $e->getMessage() . "\n";
    }
    // Também em sales_order_grid para visibilidade no admin
    try {
        $colGrid = $mysql->query(
            "SELECT COLUMN_NAME FROM information_schema.columns 
             WHERE table_schema = DATABASE() AND table_name = 'sales_order_grid' AND column_name = 'customer_erp_code'"
        )->fetch();
        if (!$colGrid) {
            $mysql->exec("ALTER TABLE sales_order_grid ADD COLUMN customer_erp_code VARCHAR(50) DEFAULT NULL COMMENT 'ERP Client Code'");
            echo "  [OK] Coluna criada em sales_order_grid tambem\n";
        }
    } catch (\PDOException $e) {
        echo "  [AVISO] sales_order_grid: " . $e->getMessage() . "\n";
    }
}

// 4. Verificar e ativar configs
echo "\n== CONFIGURACOES DO MODO PULL ==\n";

$configs = [
    // Conexão (já deve estar configurada)
    'grupoawamotos_erp/connection/enabled' => ['expected' => '1', 'label' => 'Modulo ERP habilitado'],
    // Pedidos
    'grupoawamotos_erp/sync_orders/enabled' => ['expected' => '1', 'label' => 'Sync pedidos habilitado'],
    'grupoawamotos_erp/sync_orders/send_on_place' => ['expected' => '0', 'label' => 'Enviar ao finalizar = NAO (modo PULL)'],
    'grupoawamotos_erp/sync_orders/use_queue' => ['expected' => '0', 'label' => 'Fila = NAO (nao necessario em modo PULL)'],
];

$toUpdate = [];
foreach ($configs as $path => $info) {
    $stmt = $mysql->prepare("SELECT value FROM core_config_data WHERE path = :path AND scope = 'default' AND scope_id = 0");
    $stmt->execute([':path' => $path]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $current = $row ? $row['value'] : null;

    $ok = ($current === $info['expected']);
    $status = $ok ? '[OK]' : '[AJUSTAR]';

    echo sprintf(
        "  %s %s\n         Path: %s | Atual: %s | Esperado: %s\n",
        $status,
        $info['label'],
        $path,
        $current ?? 'NULL',
        $info['expected']
    );

    if (!$ok) {
        $toUpdate[$path] = $info['expected'];
    }
}

// 5. Aplicar configs
if (!empty($toUpdate)) {
    echo "\n== APLICANDO CONFIGURACOES ==\n";
    foreach ($toUpdate as $path => $value) {
        try {
            // Verifica se já existe
            $stmt = $mysql->prepare("SELECT config_id FROM core_config_data WHERE path = :path AND scope = 'default' AND scope_id = 0");
            $stmt->execute([':path' => $path]);
            $exists = $stmt->fetch();

            if ($exists) {
                $stmt = $mysql->prepare("UPDATE core_config_data SET value = :value WHERE path = :path AND scope = 'default' AND scope_id = 0");
            } else {
                $stmt = $mysql->prepare("INSERT INTO core_config_data (scope, scope_id, path, value) VALUES ('default', 0, :path, :value)");
            }

            $stmt->execute([':path' => $path, ':value' => $value]);
            echo "  [OK] $path = $value\n";
        } catch (\PDOException $e) {
            echo "  [ERRO] $path: " . $e->getMessage() . "\n";
        }
    }
    echo "\n  *** LIMPE O CACHE: sudo -u www-data php bin/magento cache:flush ***\n";
} else {
    echo "\n  Todas as configs ja estao corretas!\n";
}

// 6. Verificar integration token para API REST
echo "\n== VERIFICANDO ACESSO API REST ==\n";

// Verificar se existe uma integration para o ERP
$stmt = $mysql->query(
    "SELECT i.integration_id, i.name, i.status,
            (SELECT token FROM oauth_token WHERE consumer_id = i.consumer_id AND type = 'access' LIMIT 1) as token
     FROM integration i 
     WHERE i.name LIKE '%ERP%' OR i.name LIKE '%Sectra%' OR i.name LIKE '%erp%'
     ORDER BY i.integration_id DESC"
);
$integrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($integrations)) {
    foreach ($integrations as $int) {
        $statusLabel = $int['status'] == 1 ? 'Ativa' : 'Inativa';
        echo "  [" . ($int['status'] == 1 ? 'OK' : 'AVISO') . "] Integration: {$int['name']} (Status: $statusLabel)\n";
        if (!empty($int['token'])) {
            echo "         Token: " . substr($int['token'], 0, 8) . "..." . substr($int['token'], -4) . "\n";
        } else {
            echo "         Token: NAO ENCONTRADO - precisa gerar no admin\n";
        }
    }
} else {
    echo "  [FALTA] Nenhuma integration 'ERP' encontrada!\n";
    echo "         Crie em Admin > System > Extensions > Integrations > Add New\n";
    echo "         Nome: 'ERP Sectra'\n";
    echo "         Recursos: GrupoAwamotos_ERPIntegration::order_pull\n";
    echo "         Depois ative para gerar o token Bearer\n";
}

// 7. Listar as APIs disponíveis
echo "\n== ENDPOINTS API DISPONIVEIS PARA SECTRA ==\n";
$baseUrl = 'https://awamotos.com/rest/V1';
$endpoints = [
    ['GET',  '/erp/orders/pending',              'Listar pedidos pendentes (nao enviados ao ERP)'],
    ['GET',  '/erp/orders/{incrementId}',        'Detalhes completos de um pedido'],
    ['POST', '/erp/orders/{incrementId}/ack',     'Confirmar recebimento do pedido pelo ERP'],
    ['GET',  '/erp/orders/held',                 'Pedidos travados (cliente sem erp_code)'],
    ['GET',  '/erp/orders/canceled',             'Pedidos cancelados nao sincronizados'],
    ['GET',  '/erp/customers/b2b',               'Listar clientes B2B com erp_code'],
    ['GET',  '/erp/customers/b2b/unregistered',  'Clientes nao registrados no Sectra'],
    ['GET',  '/erp/health',                      'Status geral da integracao'],
];

foreach ($endpoints as [$method, $path, $desc]) {
    echo "  $method $baseUrl$path\n    -> $desc\n\n";
}

// 8. Resumo de pedidos atuais
echo "== RESUMO DE PEDIDOS ==\n";
try {
    // Total de pedidos
    $total = $mysql->query("SELECT COUNT(*) c FROM sales_order")->fetch()['c'];
    echo "  Total de pedidos: $total\n";

    // Pedidos pendentes (nao enviados ao ERP)
    $pending = $mysql->query(
        "SELECT COUNT(*) c FROM sales_order so
         WHERE so.state IN ('new', 'pending', 'processing', 'pending_payment')
         AND so.entity_id NOT IN (
             SELECT magento_entity_id FROM grupoawamotos_erp_entity_map WHERE entity_type = 'order'
         )"
    )->fetch();
    if ($pending) {
        echo "  Pedidos pendentes (nao no ERP): {$pending['c']}\n";
    }

    // Pedidos já mapeados
    $mapped = $mysql->query(
        "SELECT COUNT(*) c FROM grupoawamotos_erp_entity_map WHERE entity_type = 'order'"
    )->fetch();
    if ($mapped) {
        echo "  Pedidos ja mapeados no ERP: {$mapped['c']}\n";
    }

    // Clientes com erp_code
    $withErp = $mysql->query(
        "SELECT COUNT(*) c FROM customer_entity_varchar cev
         INNER JOIN eav_attribute ea ON ea.attribute_id = cev.attribute_id
         WHERE ea.attribute_code = 'erp_code' AND cev.value IS NOT NULL AND cev.value != ''"
    )->fetch();
    if ($withErp) {
        echo "  Clientes com erp_code: {$withErp['c']}\n";
    }
} catch (\PDOException $e) {
    echo "  [AVISO] Algumas queries de resumo falharam: " . $e->getMessage() . "\n";
}

// 9. Verificar crons ativos
echo "\n== CRONS DO MODULO ==\n";
$crons = [
    'grupoawamotos_erp_sync_order_statuses' => 'Sync status ERP -> Magento (*/15 min)',
    'grupoawamotos_erp_resolve_customer_erp_codes' => 'Auto-link clientes por CPF/CNPJ (*/3h)',
    'grupoawamotos_erp_retry_held_orders' => 'Retry pedidos travados (*/30 min)',
];

foreach ($crons as $code => $desc) {
    try {
        $last = $mysql->prepare(
            "SELECT status, executed_at, finished_at 
             FROM cron_schedule WHERE job_code = :code 
             ORDER BY schedule_id DESC LIMIT 1"
        );
        $last->execute([':code' => $code]);
        $row = $last->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            echo "  [OK] $code\n";
            echo "       $desc\n";
            echo "       Ultima exec: {$row['executed_at']} (status: {$row['status']})\n\n";
        } else {
            echo "  [AVISO] $code - nunca executou\n";
            echo "       $desc\n\n";
        }
    } catch (\PDOException $e) {
        echo "  [?] $code - " . $e->getMessage() . "\n\n";
    }
}

echo "====================================================\n";
echo "  MODO PULL CONFIGURADO!\n";
echo "====================================================\n";
echo "\n";
echo "PROXIMOS PASSOS:\n";
echo "  1. sudo -u www-data php bin/magento cache:flush\n";
echo "  2. Criar Integration no Admin (se nao existir):\n";
echo "     Admin > System > Integrations > Add New\n";
echo "     Nome: 'ERP Sectra'\n";
echo "     Recurso: GrupoAwamotos ERP > Order Pull API\n";
echo "  3. Ativar a Integration e copiar o Access Token\n";
echo "  4. Passar o token + URLs acima para a Sectra\n";
echo "  5. Sectra configura job para chamar:\n";
echo "     GET $baseUrl/erp/orders/pending\n";
echo "     (com header: Authorization: Bearer {token})\n";
echo "  6. Ao importar, Sectra confirma via:\n";
echo "     POST $baseUrl/erp/orders/{id}/ack\n";
echo "\n";
echo "FLUXO COMPLETO:\n";
echo "  Cliente compra -> Observer salva erp_code no pedido\n";
echo "  -> Sectra chama GET /pending -> recebe pedido JSON\n";
echo "  -> Sectra grava VE_PEDIDO -> POST /ack confirma\n";
echo "  -> Cron SyncOrderStatuses: ERP status -> Magento\n";
echo "  -> Tracking/NF-e sincronizam automaticamente\n";
echo "====================================================\n";
