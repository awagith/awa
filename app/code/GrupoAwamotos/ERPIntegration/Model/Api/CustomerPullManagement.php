<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Model\Api;

use GrupoAwamotos\ERPIntegration\Api\CustomerPullInterface;
use GrupoAwamotos\ERPIntegration\Api\ConnectionInterface;
use GrupoAwamotos\ERPIntegration\Model\B2BClientRegistration;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class CustomerPullManagement implements CustomerPullInterface
{
    private const ERP_CODE_ATTR_ID = 198;

    private ConnectionInterface $connection;
    private B2BClientRegistration $b2bRegistration;
    private ResourceConnection $resourceConnection;
    private LoggerInterface $logger;

    public function __construct(
        ConnectionInterface $connection,
        B2BClientRegistration $b2bRegistration,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->b2bRegistration = $b2bRegistration;
        $this->resourceConnection = $resourceConnection;
        $this->logger = $logger;
    }

    public function getB2BCustomers(int $limit = 100, int $offset = 0): array
    {
        $this->logger->info('[ERP API] getB2BCustomers called', ['limit' => $limit, 'offset' => $offset]);

        $db = $this->resourceConnection->getConnection();

        $customers = $db->fetchAll(
            "SELECT ce.entity_id, ce.email, ce.firstname, ce.lastname, ce.group_id, ce.created_at,
                    cev.value AS erp_code
             FROM customer_entity ce
             JOIN customer_entity_varchar cev ON ce.entity_id = cev.entity_id AND cev.attribute_id = ?
             WHERE cev.value IS NOT NULL AND cev.value != '' AND cev.value REGEXP '^[0-9]+$'
             ORDER BY CAST(cev.value AS UNSIGNED)
             LIMIT ? OFFSET ?",
            [self::ERP_CODE_ATTR_ID, $limit, $offset]
        );

        $result = [];
        foreach ($customers as $c) {
            $result[] = $this->buildCustomerPayload($c);
        }

        $totalCount = (int) $db->fetchOne(
            "SELECT COUNT(*) FROM customer_entity_varchar
             WHERE attribute_id = ? AND value IS NOT NULL AND value != '' AND value REGEXP '^[0-9]+$'",
            [self::ERP_CODE_ATTR_ID]
        );

        return [[
            'customers' => $result,
            'total_count' => $totalCount,
            'returned_count' => count($result),
            'offset' => $offset,
            'timestamp' => date('c'),
        ]];
    }

    public function getUnregisteredCustomers(int $limit = 100): array
    {
        $this->logger->info('[ERP API] getUnregisteredCustomers called', ['limit' => $limit]);

        $db = $this->resourceConnection->getConnection();

        // Get all Magento customers with ERP codes
        $customers = $db->fetchAll(
            "SELECT ce.entity_id, ce.email, ce.firstname, ce.lastname, ce.group_id, ce.created_at,
                    cev.value AS erp_code
             FROM customer_entity ce
             JOIN customer_entity_varchar cev ON ce.entity_id = cev.entity_id AND cev.attribute_id = ?
             WHERE cev.value IS NOT NULL AND cev.value != '' AND cev.value REGEXP '^[0-9]+$'
             ORDER BY CAST(cev.value AS UNSIGNED)",
            [self::ERP_CODE_ATTR_ID]
        );

        $unregistered = [];
        foreach ($customers as $c) {
            if (count($unregistered) >= $limit) {
                break;
            }

            $erpCode = (int) $c['erp_code'];
            if (!$this->b2bRegistration->isClientRegistered($erpCode)) {
                $unregistered[] = $this->buildCustomerPayload($c, true);
            }
        }

        return [[
            'customers' => $unregistered,
            'total_unregistered' => count($unregistered),
            'timestamp' => date('c'),
        ]];
    }

    public function getCustomerByErpCode(int $erpCode): array
    {
        $this->logger->info('[ERP API] getCustomerByErpCode called', ['erp_code' => $erpCode]);

        $db = $this->resourceConnection->getConnection();

        $customer = $db->fetchRow(
            "SELECT ce.entity_id, ce.email, ce.firstname, ce.lastname, ce.group_id, ce.created_at,
                    cev.value AS erp_code
             FROM customer_entity ce
             JOIN customer_entity_varchar cev ON ce.entity_id = cev.entity_id AND cev.attribute_id = ?
             WHERE cev.value = ?",
            [self::ERP_CODE_ATTR_ID, (string) $erpCode]
        );

        if (!$customer) {
            throw new NoSuchEntityException(
                __('Customer with ERP code "%1" not found in Magento.', $erpCode)
            );
        }

        return [$this->buildCustomerPayload($customer, true)];
    }

    public function getRegistrationSQL(int $limit = 500): array
    {
        $this->logger->info('[ERP API] getRegistrationSQL called', ['limit' => $limit]);

        $db = $this->resourceConnection->getConnection();

        $origemCliente = '7D4C6FBD-62CF-427F-A0ED-3C06602F05D7';
        $origemEndereco = 'FEB11981-5319-49EB-9F1E-4BA02BD22B90';

        // Get all Magento ERP codes
        $erpCodes = $db->fetchCol(
            "SELECT DISTINCT cev.value FROM customer_entity_varchar cev
             WHERE cev.attribute_id = ? AND cev.value IS NOT NULL AND cev.value != '' AND cev.value REGEXP '^[0-9]+$'",
            [self::ERP_CODE_ATTR_ID]
        );

        // Get registered in Sectra
        $registered = $this->connection->query(
            "SELECT CHAVE FROM GR_INTEGRACAOVALIDADOR WHERE INTEGRACAOORIGEM = '$origemCliente'"
        );
        $registeredSet = array_flip(array_column($registered, 'CHAVE'));

        // Filter unregistered
        $unregistered = [];
        foreach ($erpCodes as $code) {
            if (!isset($registeredSet[$code])) {
                $unregistered[] = $code;
            }
            if (count($unregistered) >= $limit) {
                break;
            }
        }

        if (empty($unregistered)) {
            return [[
                'sql' => '-- Todos os clientes Magento B2B ja estao registrados no Sectra.',
                'count' => 0,
                'timestamp' => date('c'),
            ]];
        }

        // Get max CHAVEEXTERNA for address
        $maxExt = (int) $this->connection->fetchColumn(
            "SELECT MAX(CAST(CHAVEEXTERNA AS INT)) FROM GR_INTEGRACAOVALIDADOR WHERE INTEGRACAOORIGEM = '$origemEndereco'"
        );
        $nextExt = $maxExt + 1;

        $sql = "-- Auto-sync: " . count($unregistered) . " clientes Magento B2B\n";
        $sql .= "-- Gerado: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "BEGIN TRANSACTION;\n";

        foreach ($unregistered as $code) {
            // md5 mantido: hash armazenado em GR_INTEGRACAOVALIDADOR.VALIDADOR (ERP Sectra, coluna 32 chars)
            $h1 = strtoupper(md5(json_encode(['CODIGO' => (int) $code, 'source' => 'magento_b2b'])));
            $h2 = strtoupper(md5(json_encode(['CODIGO' => (int) $code, 'ENDERECO' => 1, 'source' => 'magento_b2b'])));

            $sql .= "INSERT INTO GR_INTEGRACAOVALIDADOR(INTEGRACAOORIGEM,CHAVE,VALIDADOR,CHAVEEXTERNA,DTSINCRONIZACAO)";
            $sql .= "VALUES('$origemCliente','$code','$h1','$code',GETDATE());\n";
            $sql .= "INSERT INTO GR_INTEGRACAOVALIDADOR(INTEGRACAOORIGEM,CHAVE,VALIDADOR,CHAVEEXTERNA,DTSINCRONIZACAO)";
            $sql .= "VALUES('$origemEndereco','$code;1','$h2','$nextExt',GETDATE());\n";
            $nextExt++;
        }

        $sql .= "COMMIT;\n";

        return [[
            'sql' => $sql,
            'count' => count($unregistered),
            'timestamp' => date('c'),
        ]];
    }

    public function getHealthStatus(): array
    {
        $this->logger->info('[ERP API] getHealthStatus called');
        $health = ['status' => 'ok', 'checks' => [], 'timestamp' => date('c')];

        // 1. SQL Server connection
        try {
            $row = $this->connection->fetchOne("SELECT 1 AS ping");
            $health['checks']['sql_server'] = ['status' => 'ok', 'message' => 'Connected'];
        } catch (\Exception $e) {
            $health['checks']['sql_server'] = ['status' => 'error', 'message' => $e->getMessage()];
            $health['status'] = 'degraded';
        }

        // 2. Magento DB
        $db = $this->resourceConnection->getConnection();
        try {
            $db->fetchOne("SELECT 1");
            $health['checks']['magento_db'] = ['status' => 'ok'];
        } catch (\Exception $e) {
            $health['checks']['magento_db'] = ['status' => 'error', 'message' => $e->getMessage()];
            $health['status'] = 'error';
        }

        // 3. B2B customer sync stats
        try {
            $totalMagento = (int) $db->fetchOne(
                "SELECT COUNT(*) FROM customer_entity_varchar WHERE attribute_id = ? AND value IS NOT NULL AND value != '' AND value REGEXP '^[0-9]+$'",
                [self::ERP_CODE_ATTR_ID]
            );

            $origemCliente = '7D4C6FBD-62CF-427F-A0ED-3C06602F05D7';
            $totalSectra = (int) $this->connection->fetchColumn(
                "SELECT COUNT(*) FROM GR_INTEGRACAOVALIDADOR WHERE INTEGRACAOORIGEM = '$origemCliente'"
            );

            $lastSync = $this->connection->fetchColumn(
                "SELECT MAX(DTSINCRONIZACAO) FROM GR_INTEGRACAOVALIDADOR WHERE INTEGRACAOORIGEM = '$origemCliente'"
            );

            $health['checks']['b2b_sync'] = [
                'status' => 'ok',
                'magento_customers' => $totalMagento,
                'sectra_registered' => $totalSectra,
                'unregistered' => $totalMagento - $totalSectra,
                'last_sectra_sync' => $lastSync,
                'coverage_pct' => $totalMagento > 0 ? round($totalSectra / $totalMagento * 100, 1) : 0,
            ];

            if (($totalMagento - $totalSectra) > 100) {
                $health['checks']['b2b_sync']['status'] = 'warning';
                $health['checks']['b2b_sync']['message'] = 'Many unregistered clients';
            }
        } catch (\Exception $e) {
            $health['checks']['b2b_sync'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        // 4. Pending orders
        try {
            $pendingOrders = (int) $db->fetchOne(
                "SELECT COUNT(*) FROM sales_order WHERE state IN ('new','pending_payment','processing')
                 AND entity_id NOT IN (SELECT magento_entity_id FROM grupoawamotos_erp_entity_map WHERE entity_type='order')"
            );

            $health['checks']['pending_orders'] = [
                'status' => $pendingOrders > 0 ? 'warning' : 'ok',
                'count' => $pendingOrders,
            ];
        } catch (\Exception $e) {
            $health['checks']['pending_orders'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        return [$health];
    }

    private function buildCustomerPayload(array $customer, bool $includeErpData = false): array
    {
        $erpCode = (int) $customer['erp_code'];

        $payload = [
            'magento_id' => (int) $customer['entity_id'],
            'erp_code' => $erpCode,
            'email' => $customer['email'],
            'name' => trim($customer['firstname'] . ' ' . $customer['lastname']),
            'group_id' => (int) $customer['group_id'],
            'created_at' => $customer['created_at'],
            'registered_in_b2b' => $this->b2bRegistration->isClientRegistered($erpCode),
        ];

        if ($includeErpData && $erpCode > 0) {
            try {
                $erpData = $this->connection->fetchOne(
                    "SELECT f.CODIGO, f.RAZAO, f.CGC, f.INSCEST, f.FILIAL,
                            f.VENDPREF, f.CONDPAGTO, f.FATORPRECO, f.TRANSPPREF,
                            f.TPFATOR, f.PERCFATOR
                     FROM FN_FORNECEDORES f
                     WHERE f.CODIGO = :code AND f.CKCLIENTE = 'S'",
                    [':code' => $erpCode]
                );

                if ($erpData) {
                    $payload['erp_data'] = [
                        'razao' => trim($erpData['RAZAO'] ?? ''),
                        'cnpj' => trim($erpData['CGC'] ?? ''),
                        'ie' => trim($erpData['INSCEST'] ?? ''),
                        'filial' => (int) ($erpData['FILIAL'] ?? 0),
                        'vendedor' => (int) ($erpData['VENDPREF'] ?? 0),
                        'cond_pagto' => (int) ($erpData['CONDPAGTO'] ?? 0),
                        'fator_preco' => (int) ($erpData['FATORPRECO'] ?? 0),
                        'transportadora' => (int) ($erpData['TRANSPPREF'] ?? 0),
                        'tp_fator' => (string) ($erpData['TPFATOR'] ?? ''),
                        'perc_fator' => (float) ($erpData['PERCFATOR'] ?? 0),
                    ];
                }
            } catch (\Exception $e) {
                $this->logger->warning('[ERP API] Failed to fetch ERP data for client ' . $erpCode);
            }
        }

        return $payload;
    }
}
