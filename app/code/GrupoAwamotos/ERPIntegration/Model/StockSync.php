<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Model;

use GrupoAwamotos\ERPIntegration\Api\StockSyncInterface;
use GrupoAwamotos\ERPIntegration\Api\ConnectionInterface;
use GrupoAwamotos\ERPIntegration\Helper\Data as Helper;
use GrupoAwamotos\ERPIntegration\Model\ResourceModel\SyncLog as SyncLogResource;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\CacheInterface;
use Psr\Log\LoggerInterface;

class StockSync implements StockSyncInterface
{
    private const CACHE_PREFIX = 'erp_stock_';

    private ConnectionInterface $connection;
    private Helper $helper;
    private StockRegistryInterface $stockRegistry;
    private SyncLogResource $syncLogResource;
    private CacheInterface $cache;
    private LoggerInterface $logger;

    public function __construct(
        ConnectionInterface $connection,
        Helper $helper,
        StockRegistryInterface $stockRegistry,
        SyncLogResource $syncLogResource,
        CacheInterface $cache,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->helper = $helper;
        $this->stockRegistry = $stockRegistry;
        $this->syncLogResource = $syncLogResource;
        $this->cache = $cache;
        $this->logger = $logger;
    }

    public function getStockBySku(string $sku): ?array
    {
        $cacheKey = self::CACHE_PREFIX . md5($sku . '_' . $this->helper->getStockFilial());
        $cached = $this->cache->load($cacheKey);

        if ($cached !== false) {
            return json_decode($cached, true);
        }

        try {
            $sql = "SELECT TOP 1 QTDE, VLRMEDIA, DATA
                    FROM MT_ESTOQUEMEDIA
                    WHERE MATERIAL = :sku AND FILIAL = :filial
                    ORDER BY DATA DESC, CODIGO DESC";

            $row = $this->connection->fetchOne($sql, [
                ':sku' => $sku,
                ':filial' => $this->helper->getStockFilial(),
            ]);

            if ($row) {
                $result = [
                    'qty' => (float) $row['QTDE'],
                    'cost' => (float) $row['VLRMEDIA'],
                    'date' => $row['DATA'],
                ];

                $this->cache->save(
                    json_encode($result),
                    $cacheKey,
                    ['erp_stock'],
                    $this->helper->getStockCacheTtl()
                );

                return $result;
            }
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Stock query error for SKU ' . $sku . ': ' . $e->getMessage());
        }

        return null;
    }

    public function syncAll(): array
    {
        $result = ['updated' => 0, 'errors' => 0];

        try {
            $sql = "SELECT m.MATERIAL, m.QTDE, m.VLRMEDIA
                    FROM MT_ESTOQUEMEDIA m
                    INNER JOIN (
                        SELECT MATERIAL, MAX(CODIGO) AS MaxCodigo
                        FROM MT_ESTOQUEMEDIA
                        WHERE FILIAL = :filial
                        GROUP BY MATERIAL
                    ) latest ON m.MATERIAL = latest.MATERIAL AND m.CODIGO = latest.MaxCodigo
                    WHERE m.FILIAL = :filial2";

            $rows = $this->connection->query($sql, [
                ':filial' => $this->helper->getStockFilial(),
                ':filial2' => $this->helper->getStockFilial(),
            ]);

            foreach ($rows as $row) {
                try {
                    $sku = trim($row['MATERIAL']);
                    $qty = (float) $row['QTDE'];

                    $stockItem = $this->stockRegistry->getStockItemBySku($sku);
                    $stockItem->setQty($qty);
                    $stockItem->setIsInStock($qty > 0);
                    $this->stockRegistry->updateStockItemBySku($sku, $stockItem);

                    $result['updated']++;
                } catch (\Exception $e) {
                    $result['errors']++;
                }
            }

            $this->syncLogResource->addLog(
                'stock',
                'import',
                $result['errors'] > 0 ? 'error' : 'success',
                sprintf('Atualizados: %d, Erros: %d', $result['updated'], $result['errors']),
                null,
                null,
                $result['updated']
            );
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Stock sync failed: ' . $e->getMessage());
            $this->syncLogResource->addLog('stock', 'import', 'error', $e->getMessage());
        }

        return $result;
    }
}
