<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Model;

use GrupoAwamotos\ERPIntegration\Api\ConnectionInterface;
use GrupoAwamotos\ERPIntegration\Helper\Data as Helper;
use Magento\Framework\App\CacheInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;

/**
 * Product Suggestion Model
 *
 * Generates product suggestions based on customer purchase history
 */
class ProductSuggestion
{
    private const CACHE_PREFIX = 'erp_suggestions_';
    private const CACHE_TTL = 1800; // 30 minutes

    private ConnectionInterface $connection;
    private PurchaseHistory $purchaseHistory;
    private Helper $helper;
    private CacheInterface $cache;
    private ProductRepositoryInterface $productRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private LoggerInterface $logger;

    public function __construct(
        ConnectionInterface $connection,
        PurchaseHistory $purchaseHistory,
        Helper $helper,
        CacheInterface $cache,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->purchaseHistory = $purchaseHistory;
        $this->helper = $helper;
        $this->cache = $cache;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
    }

    /**
     * Get product suggestions for a customer
     *
     * Algorithm:
     * 1. Get products the customer has purchased
     * 2. Find other customers who bought the same products
     * 3. Get products those customers bought that this customer hasn't
     * 4. Rank by number of customers who bought
     */
    public function getSuggestions(int $customerCode, int $limit = 10): array
    {
        if (!$this->helper->isSuggestionsEnabled()) {
            return [];
        }

        $cacheKey = self::CACHE_PREFIX . $customerCode;
        $cached = $this->cache->load($cacheKey);

        if ($cached) {
            return json_decode($cached, true);
        }

        try {
            // Get customer's purchased products
            $customerProducts = $this->purchaseHistory->getMostPurchasedProducts($customerCode, 50);

            if (empty($customerProducts)) {
                return [];
            }

            $materialCodes = array_column($customerProducts, 'codigo_material');

            // Build placeholders for IN clause
            $placeholders = implode(',', array_fill(0, count($materialCodes), '?'));

            // Find products bought by similar customers
            $suggestions = $this->connection->query("
                SELECT TOP " . (int)$limit . "
                    i2.MATERIAL as codigo_material,
                    i2.DESCRICAO as descricao,
                    COUNT(DISTINCT p2.CLIENTE) as clientes_compraram,
                    SUM(i2.QTDE) as quantidade_total,
                    AVG(i2.VLRUNITARIO) as preco_medio
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
            ", [...$materialCodes, $customerCode, ...$materialCodes]);

            // Enrich with Magento product data
            $enrichedSuggestions = $this->enrichWithMagentoData($suggestions);

            $this->cache->save(json_encode($enrichedSuggestions), $cacheKey, [], self::CACHE_TTL);

            return $enrichedSuggestions;
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Error getting suggestions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get reorder suggestions (products customer bought before that might need reordering)
     */
    public function getReorderSuggestions(int $customerCode, int $limit = 10): array
    {
        try {
            // Get products with their purchase frequency
            $products = $this->connection->query("
                SELECT TOP " . (int)$limit . "
                    i.MATERIAL as codigo_material,
                    i.DESCRICAO as descricao,
                    COUNT(DISTINCT i.PEDIDO) as vezes_comprado,
                    SUM(i.QTDE) as quantidade_total,
                    AVG(i.QTDE) as quantidade_media_pedido,
                    MAX(p.DTPEDIDO) as ultima_compra,
                    DATEDIFF(day, MAX(p.DTPEDIDO), GETDATE()) as dias_desde_ultima,
                    AVG(DATEDIFF(day, prev.DTPEDIDO, p.DTPEDIDO)) as media_dias_entre_compras
                FROM VE_PEDIDOITENS i
                INNER JOIN VE_PEDIDO p ON i.PEDIDO = p.CODIGO
                LEFT JOIN (
                    SELECT i2.MATERIAL, p2.DTPEDIDO, p2.CLIENTE
                    FROM VE_PEDIDOITENS i2
                    INNER JOIN VE_PEDIDO p2 ON i2.PEDIDO = p2.CODIGO
                    WHERE p2.STATUS NOT IN ('C', 'X')
                ) prev ON prev.MATERIAL = i.MATERIAL
                    AND prev.CLIENTE = p.CLIENTE
                    AND prev.DTPEDIDO < p.DTPEDIDO
                WHERE p.CLIENTE = ?
                AND p.STATUS NOT IN ('C', 'X')
                GROUP BY i.MATERIAL, i.DESCRICAO
                HAVING COUNT(DISTINCT i.PEDIDO) >= 2
                AND DATEDIFF(day, MAX(p.DTPEDIDO), GETDATE()) >=
                    COALESCE(AVG(DATEDIFF(day, prev.DTPEDIDO, p.DTPEDIDO)), 30)
                ORDER BY DATEDIFF(day, MAX(p.DTPEDIDO), GETDATE()) DESC
            ", [$customerCode]);

            return $this->enrichWithMagentoData($products);
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Error getting reorder suggestions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get trending products (most sold in last 30 days)
     */
    public function getTrendingProducts(int $limit = 10): array
    {
        $cacheKey = self::CACHE_PREFIX . 'trending';
        $cached = $this->cache->load($cacheKey);

        if ($cached) {
            return json_decode($cached, true);
        }

        try {
            $products = $this->connection->query("
                SELECT TOP " . (int)$limit . "
                    i.MATERIAL as codigo_material,
                    i.DESCRICAO as descricao,
                    COUNT(DISTINCT p.CLIENTE) as clientes_compraram,
                    COUNT(DISTINCT i.PEDIDO) as total_pedidos,
                    SUM(i.QTDE) as quantidade_total,
                    AVG(i.VLRUNITARIO) as preco_medio
                FROM VE_PEDIDOITENS i
                INNER JOIN VE_PEDIDO p ON i.PEDIDO = p.CODIGO
                WHERE p.STATUS NOT IN ('C', 'X')
                AND p.DTPEDIDO >= DATEADD(day, -30, GETDATE())
                GROUP BY i.MATERIAL, i.DESCRICAO
                ORDER BY SUM(i.QTDE) DESC
            ");

            $enriched = $this->enrichWithMagentoData($products);

            $this->cache->save(json_encode($enriched), $cacheKey, [], self::CACHE_TTL);

            return $enriched;
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Error getting trending products: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get complementary products (frequently bought together)
     */
    public function getComplementaryProducts(string $materialCode, int $limit = 5): array
    {
        $cacheKey = self::CACHE_PREFIX . 'complementary_' . md5($materialCode);
        $cached = $this->cache->load($cacheKey);

        if ($cached) {
            return json_decode($cached, true);
        }

        try {
            $products = $this->connection->query("
                SELECT TOP " . (int)$limit . "
                    i2.MATERIAL as codigo_material,
                    i2.DESCRICAO as descricao,
                    COUNT(DISTINCT i2.PEDIDO) as vezes_comprado_junto
                FROM VE_PEDIDOITENS i2
                WHERE i2.PEDIDO IN (
                    SELECT i.PEDIDO
                    FROM VE_PEDIDOITENS i
                    INNER JOIN VE_PEDIDO p ON i.PEDIDO = p.CODIGO
                    WHERE i.MATERIAL = ?
                    AND p.STATUS NOT IN ('C', 'X')
                )
                AND i2.MATERIAL <> ?
                GROUP BY i2.MATERIAL, i2.DESCRICAO
                ORDER BY COUNT(DISTINCT i2.PEDIDO) DESC
            ", [$materialCode, $materialCode]);

            $enriched = $this->enrichWithMagentoData($products);

            $this->cache->save(json_encode($enriched), $cacheKey, [], self::CACHE_TTL);

            return $enriched;
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Error getting complementary products: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Enrich ERP data with Magento product information
     */
    private function enrichWithMagentoData(array $erpProducts): array
    {
        if (empty($erpProducts)) {
            return [];
        }

        // Get SKUs from ERP data
        $skus = array_column($erpProducts, 'codigo_material');

        // Search for products in Magento by SKU
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('sku', $skus, 'in')
            ->create();

        try {
            $magentoProducts = $this->productRepository->getList($searchCriteria)->getItems();
            $magentoProductsBySku = [];

            foreach ($magentoProducts as $product) {
                $magentoProductsBySku[$product->getSku()] = [
                    'entity_id' => $product->getId(),
                    'name' => $product->getName(),
                    'url_key' => $product->getUrlKey(),
                    'price' => $product->getPrice(),
                    'final_price' => $product->getFinalPrice(),
                    'image' => $product->getImage(),
                    'status' => $product->getStatus(),
                    'in_stock' => $product->isAvailable(),
                ];
            }
        } catch (\Exception $e) {
            $this->logger->warning('[ERP] Could not enrich with Magento data: ' . $e->getMessage());
            $magentoProductsBySku = [];
        }

        // Merge ERP and Magento data
        $enriched = [];
        foreach ($erpProducts as $product) {
            $sku = $product['codigo_material'];
            $enrichedProduct = $product;

            if (isset($magentoProductsBySku[$sku])) {
                $enrichedProduct['magento'] = $magentoProductsBySku[$sku];
                $enrichedProduct['available_in_store'] = true;
            } else {
                $enrichedProduct['magento'] = null;
                $enrichedProduct['available_in_store'] = false;
            }

            $enriched[] = $enrichedProduct;
        }

        return $enriched;
    }

    /**
     * Clear suggestion cache for a customer
     */
    public function clearCache(int $customerCode): void
    {
        $this->cache->remove(self::CACHE_PREFIX . $customerCode);
    }

    /**
     * Clear all suggestion caches
     */
    public function clearAllCache(): void
    {
        // This would require a more sophisticated cache implementation
        // For now, trending products cache will expire naturally
        $this->cache->remove(self::CACHE_PREFIX . 'trending');
    }
}
