<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Model;

use GrupoAwamotos\ERPIntegration\Api\ConnectionInterface;
use GrupoAwamotos\ERPIntegration\Helper\Data as Helper;
use Magento\Framework\App\CacheInterface;
use Psr\Log\LoggerInterface;

/**
 * Purchase History Model
 *
 * Retrieves customer purchase history from ERP SQL Server
 */
class PurchaseHistory
{
    private const CACHE_PREFIX = 'erp_purchase_history_';
    private const CACHE_TTL = 3600; // 1 hour

    private ConnectionInterface $connection;
    private Helper $helper;
    private CacheInterface $cache;
    private LoggerInterface $logger;

    public function __construct(
        ConnectionInterface $connection,
        Helper $helper,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->helper = $helper;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    /**
     * Get customer code from ERP by CNPJ
     */
    public function getCustomerCodeByCnpj(string $cnpj): ?int
    {
        $cleanCnpj = preg_replace('/[^0-9]/', '', $cnpj);

        try {
            $customer = $this->connection->fetchOne("
                SELECT CODIGO
                FROM FN_FORNECEDORES
                WHERE REPLACE(REPLACE(REPLACE(CGC, '.', ''), '-', ''), '/', '') = ?
                AND CKCLIENTE = 'S'
            ", [$cleanCnpj]);

            return $customer ? (int)$customer['CODIGO'] : null;
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Error getting customer code: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get customer info from ERP
     */
    public function getCustomerInfo(int $customerCode): ?array
    {
        $cacheKey = self::CACHE_PREFIX . 'customer_' . $customerCode;
        $cached = $this->cache->load($cacheKey);

        if ($cached) {
            return json_decode($cached, true);
        }

        try {
            $customer = $this->connection->fetchOne("
                SELECT
                    CODIGO,
                    RAZAO,
                    FANTASIA,
                    CGC,
                    CIDADE,
                    UF,
                    ULTIMACOMPRA
                FROM FN_FORNECEDORES
                WHERE CODIGO = ?
                AND CKCLIENTE = 'S'
            ", [$customerCode]);

            if ($customer) {
                $this->cache->save(json_encode($customer), $cacheKey, [], self::CACHE_TTL);
            }

            return $customer;
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Error getting customer info: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get customer purchase summary
     */
    public function getCustomerSummary(int $customerCode): array
    {
        $cacheKey = self::CACHE_PREFIX . 'summary_' . $customerCode;
        $cached = $this->cache->load($cacheKey);

        if ($cached) {
            return json_decode($cached, true);
        }

        try {
            $summary = $this->connection->fetchOne("
                SELECT
                    COUNT(DISTINCT p.CODIGO) as total_pedidos,
                    SUM(i.VLRTOTAL) as valor_total,
                    MIN(p.DTPEDIDO) as primeira_compra,
                    MAX(p.DTPEDIDO) as ultima_compra,
                    COUNT(DISTINCT i.MATERIAL) as produtos_diferentes
                FROM VE_PEDIDO p
                INNER JOIN VE_PEDIDOITENS i ON p.CODIGO = i.PEDIDO
                WHERE p.CLIENTE = ?
                AND p.STATUS NOT IN ('C', 'X')
            ", [$customerCode]);

            $result = [
                'total_pedidos' => (int)($summary['total_pedidos'] ?? 0),
                'valor_total' => (float)($summary['valor_total'] ?? 0),
                'primeira_compra' => $summary['primeira_compra'] ?? null,
                'ultima_compra' => $summary['ultima_compra'] ?? null,
                'produtos_diferentes' => (int)($summary['produtos_diferentes'] ?? 0),
            ];

            $this->cache->save(json_encode($result), $cacheKey, [], self::CACHE_TTL);

            return $result;
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Error getting customer summary: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get last orders for a customer
     */
    public function getLastOrders(int $customerCode, int $limit = 10): array
    {
        try {
            return $this->connection->query("
                SELECT TOP " . (int)$limit . "
                    p.CODIGO as pedido_id,
                    p.DTPEDIDO as data_pedido,
                    p.STATUS as status,
                    p.VLRTOTAL as valor_total,
                    COUNT(i.CODIGO) as qtd_itens
                FROM VE_PEDIDO p
                LEFT JOIN VE_PEDIDOITENS i ON p.CODIGO = i.PEDIDO
                WHERE p.CLIENTE = ?
                GROUP BY p.CODIGO, p.DTPEDIDO, p.STATUS, p.VLRTOTAL
                ORDER BY p.DTPEDIDO DESC
            ", [$customerCode]);
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Error getting last orders: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get most purchased products by a customer
     */
    public function getMostPurchasedProducts(int $customerCode, int $limit = 20): array
    {
        $cacheKey = self::CACHE_PREFIX . 'products_' . $customerCode;
        $cached = $this->cache->load($cacheKey);

        if ($cached) {
            return json_decode($cached, true);
        }

        try {
            $products = $this->connection->query("
                SELECT TOP " . (int)$limit . "
                    i.MATERIAL as codigo_material,
                    i.DESCRICAO as descricao,
                    COUNT(DISTINCT i.PEDIDO) as vezes_comprado,
                    SUM(i.QTDE) as quantidade_total,
                    AVG(i.VLRUNITARIO) as preco_medio,
                    MAX(p.DTPEDIDO) as ultima_compra
                FROM VE_PEDIDOITENS i
                INNER JOIN VE_PEDIDO p ON i.PEDIDO = p.CODIGO
                WHERE p.CLIENTE = ?
                AND p.STATUS NOT IN ('C', 'X')
                GROUP BY i.MATERIAL, i.DESCRICAO
                ORDER BY SUM(i.QTDE) DESC
            ", [$customerCode]);

            $this->cache->save(json_encode($products), $cacheKey, [], self::CACHE_TTL);

            return $products;
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Error getting most purchased products: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get products purchased in last N days
     */
    public function getRecentlyPurchasedProducts(int $customerCode, int $days = 90): array
    {
        try {
            return $this->connection->query("
                SELECT
                    i.MATERIAL as codigo_material,
                    i.DESCRICAO as descricao,
                    SUM(i.QTDE) as quantidade,
                    MAX(p.DTPEDIDO) as data_compra
                FROM VE_PEDIDOITENS i
                INNER JOIN VE_PEDIDO p ON i.PEDIDO = p.CODIGO
                WHERE p.CLIENTE = ?
                AND p.STATUS NOT IN ('C', 'X')
                AND p.DTPEDIDO >= DATEADD(day, -" . (int)$days . ", GETDATE())
                GROUP BY i.MATERIAL, i.DESCRICAO
                ORDER BY MAX(p.DTPEDIDO) DESC
            ", [$customerCode]);
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Error getting recent products: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get purchase frequency analysis
     */
    public function getPurchaseFrequency(int $customerCode): array
    {
        try {
            // Get average days between purchases
            $frequency = $this->connection->fetchOne("
                WITH OrderDates AS (
                    SELECT
                        DTPEDIDO,
                        LAG(DTPEDIDO) OVER (ORDER BY DTPEDIDO) as prev_date
                    FROM VE_PEDIDO
                    WHERE CLIENTE = ?
                    AND STATUS NOT IN ('C', 'X')
                )
                SELECT
                    AVG(DATEDIFF(day, prev_date, DTPEDIDO)) as avg_days_between,
                    MIN(DATEDIFF(day, prev_date, DTPEDIDO)) as min_days,
                    MAX(DATEDIFF(day, prev_date, DTPEDIDO)) as max_days,
                    COUNT(*) as total_orders
                FROM OrderDates
                WHERE prev_date IS NOT NULL
            ", [$customerCode]);

            return [
                'avg_days_between_orders' => (int)($frequency['avg_days_between'] ?? 0),
                'min_days' => (int)($frequency['min_days'] ?? 0),
                'max_days' => (int)($frequency['max_days'] ?? 0),
                'total_orders' => (int)($frequency['total_orders'] ?? 0),
            ];
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Error getting purchase frequency: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Clear cache for a customer
     */
    public function clearCustomerCache(int $customerCode): void
    {
        $this->cache->remove(self::CACHE_PREFIX . 'customer_' . $customerCode);
        $this->cache->remove(self::CACHE_PREFIX . 'summary_' . $customerCode);
        $this->cache->remove(self::CACHE_PREFIX . 'products_' . $customerCode);
    }
}
