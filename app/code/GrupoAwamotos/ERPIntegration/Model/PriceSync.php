<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Model;

use GrupoAwamotos\ERPIntegration\Api\PriceSyncInterface;
use GrupoAwamotos\ERPIntegration\Api\ConnectionInterface;
use GrupoAwamotos\ERPIntegration\Helper\Data as Helper;
use GrupoAwamotos\ERPIntegration\Model\ResourceModel\SyncLog as SyncLogResource;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

class PriceSync implements PriceSyncInterface
{
    private ConnectionInterface $connection;
    private Helper $helper;
    private ProductRepositoryInterface $productRepository;
    private SyncLogResource $syncLogResource;
    private LoggerInterface $logger;

    public function __construct(
        ConnectionInterface $connection,
        Helper $helper,
        ProductRepositoryInterface $productRepository,
        SyncLogResource $syncLogResource,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->helper = $helper;
        $this->productRepository = $productRepository;
        $this->syncLogResource = $syncLogResource;
        $this->logger = $logger;
    }

    public function getPriceBySku(string $sku): ?array
    {
        try {
            $sql = "SELECT p.VLRVENDA, p.VLRLIQUIDO, p.VLRPRODUCAO, p.PERCLUCRO,
                           c.VLRCUSTO, c.MARGEMSUG, c.MARGEMMAX, c.MARGEMMIN
                    FROM MT_COMPOSICAOPRECO p
                    LEFT JOIN MT_MATERIALCUSTO c ON c.MATERIAL = p.MATERIAL AND c.FILIAL = p.FILIAL
                    WHERE p.MATERIAL = :sku AND p.FILIAL = :filial";

            return $this->connection->fetchOne($sql, [
                ':sku' => $sku,
                ':filial' => $this->helper->getStockFilial(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Price query error: ' . $e->getMessage());
            return null;
        }
    }

    public function syncAll(): array
    {
        $result = ['updated' => 0, 'errors' => 0, 'skipped' => 0];

        try {
            $sql = "SELECT p.MATERIAL, p.VLRVENDA,
                           c.VLRCUSTO, c.MARGEMSUG
                    FROM MT_COMPOSICAOPRECO p
                    LEFT JOIN MT_MATERIALCUSTO c ON c.MATERIAL = p.MATERIAL AND c.FILIAL = p.FILIAL
                    WHERE p.FILIAL = :filial AND p.VLRVENDA > 0";

            $rows = $this->connection->query($sql, [
                ':filial' => $this->helper->getStockFilial(),
            ]);

            foreach ($rows as $row) {
                try {
                    $sku = trim($row['MATERIAL']);
                    $price = (float) $row['VLRVENDA'];

                    if ($price <= 0) {
                        $result['skipped']++;
                        continue;
                    }

                    $product = $this->productRepository->get($sku);
                    $currentPrice = (float) $product->getPrice();

                    if (abs($currentPrice - $price) > 0.01) {
                        $product->setPrice($price);

                        $cost = (float) ($row['VLRCUSTO'] ?? 0);
                        if ($cost > 0) {
                            $product->setCustomAttribute('cost', $cost);
                        }

                        $this->productRepository->save($product);
                        $result['updated']++;
                    } else {
                        $result['skipped']++;
                    }
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    $result['skipped']++;
                } catch (\Exception $e) {
                    $result['errors']++;
                    $this->logger->error('[ERP] Price sync error for SKU ' . ($row['MATERIAL'] ?? '?') . ': ' . $e->getMessage());
                }
            }

            $this->syncLogResource->addLog(
                'price',
                'import',
                $result['errors'] > 0 ? 'error' : 'success',
                sprintf('Atualizados: %d, Erros: %d, Ignorados: %d', $result['updated'], $result['errors'], $result['skipped']),
                null,
                null,
                $result['updated']
            );
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Price sync failed: ' . $e->getMessage());
            $this->syncLogResource->addLog('price', 'import', 'error', $e->getMessage());
        }

        return $result;
    }
}
