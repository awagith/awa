<?php
declare(strict_types=1);

namespace GrupoAwamotos\ERPIntegration\Model;

use GrupoAwamotos\ERPIntegration\Api\ProductSyncInterface;
use GrupoAwamotos\ERPIntegration\Api\ConnectionInterface;
use GrupoAwamotos\ERPIntegration\Helper\Data as Helper;
use GrupoAwamotos\ERPIntegration\Model\ResourceModel\SyncLog as SyncLogResource;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ProductSync implements ProductSyncInterface
{
    private ConnectionInterface $connection;
    private Helper $helper;
    private ProductRepositoryInterface $productRepository;
    private ProductInterfaceFactory $productFactory;
    private StoreManagerInterface $storeManager;
    private SyncLogResource $syncLogResource;
    private LoggerInterface $logger;

    public function __construct(
        ConnectionInterface $connection,
        Helper $helper,
        ProductRepositoryInterface $productRepository,
        ProductInterfaceFactory $productFactory,
        StoreManagerInterface $storeManager,
        SyncLogResource $syncLogResource,
        LoggerInterface $logger
    ) {
        $this->connection = $connection;
        $this->helper = $helper;
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->storeManager = $storeManager;
        $this->syncLogResource = $syncLogResource;
        $this->logger = $logger;
    }

    public function getErpProducts(int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT m.CODIGO, m.DESCRICAO, m.COMPLEMENTO, m.CODINTERNO,
                       m.NCM, m.CPESO, m.VPESO, m.DIMENSOES, m.UNDVENDA,
                       m.CKCOMERCIALIZA, m.CCKATIVO, m.VCKATIVO, m.TPMATERIAL,
                       m.GRUPOCOMERCIAL, m.EDITDATE,
                       c.VLRCUSTO, c.MARGEMSUG,
                       p.VLRVENDA
                FROM MT_MATERIAL m
                LEFT JOIN MT_MATERIALCUSTO c ON c.MATERIAL = m.CODIGO AND c.FILIAL = :filial1
                LEFT JOIN MT_COMPOSICAOPRECO p ON p.MATERIAL = m.CODIGO AND p.FILIAL = :filial2
                WHERE m.CCKATIVO = 'S'";

        if ($this->helper->filterComercializa()) {
            $sql .= " AND m.CKCOMERCIALIZA = 'S'";
        }

        $sql .= " ORDER BY m.CODIGO";

        if ($limit > 0) {
            $sql .= " OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";
        }

        $params = [
            ':filial1' => $this->helper->getStockFilial(),
            ':filial2' => $this->helper->getStockFilial(),
        ];

        if ($limit > 0) {
            $params[':offset'] = $offset;
            $params[':limit'] = $limit;
        }

        return $this->connection->query($sql, $params);
    }

    public function syncAll(): array
    {
        $result = ['created' => 0, 'updated' => 0, 'errors' => 0, 'skipped' => 0];

        try {
            $erpProducts = $this->getErpProducts();
            $websiteIds = [$this->storeManager->getDefaultStoreView()->getWebsiteId()];
            $defaultAttributeSetId = 4; // Default attribute set

            foreach ($erpProducts as $erpProduct) {
                try {
                    $sku = trim($erpProduct['CODIGO']);
                    if (empty($sku)) {
                        $result['skipped']++;
                        continue;
                    }

                    $dataHash = md5(json_encode($erpProduct));
                    $existingMapId = $this->syncLogResource->getEntityMap('product', $sku);

                    try {
                        $product = $this->productRepository->get($sku);
                        $isNew = false;
                    } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                        $product = $this->productFactory->create();
                        $product->setSku($sku);
                        $product->setTypeId(Type::TYPE_SIMPLE);
                        $product->setAttributeSetId($defaultAttributeSetId);
                        $product->setWebsiteIds($websiteIds);
                        $product->setVisibility(Visibility::VISIBILITY_BOTH);
                        $isNew = true;
                    }

                    $name = trim($erpProduct['DESCRICAO'] ?? '');
                    if (empty($name)) {
                        $result['skipped']++;
                        continue;
                    }

                    $product->setName($name);

                    if (!empty($erpProduct['COMPLEMENTO'])) {
                        $product->setShortDescription(trim($erpProduct['COMPLEMENTO']));
                    }

                    $price = (float) ($erpProduct['VLRVENDA'] ?? 0);
                    if ($price > 0) {
                        $product->setPrice($price);
                    }

                    $weight = (float) ($erpProduct['VPESO'] ?? $erpProduct['CPESO'] ?? 0);
                    if ($weight > 0) {
                        $product->setWeight($weight);
                    }

                    $isActive = ($erpProduct['CCKATIVO'] ?? 'N') === 'S';
                    $product->setStatus($isActive ? Status::STATUS_ENABLED : Status::STATUS_DISABLED);

                    if (!empty($erpProduct['CODINTERNO'])) {
                        $product->setCustomAttribute('erp_internal_code', $erpProduct['CODINTERNO']);
                    }
                    if (!empty($erpProduct['NCM'])) {
                        $product->setCustomAttribute('erp_ncm', $erpProduct['NCM']);
                    }

                    $this->productRepository->save($product);

                    $this->syncLogResource->setEntityMap(
                        'product',
                        $sku,
                        (int) $product->getId(),
                        $dataHash
                    );

                    $isNew ? $result['created']++ : $result['updated']++;
                } catch (\Exception $e) {
                    $result['errors']++;
                    $this->logger->error(
                        '[ERP] Product sync error for SKU ' . ($erpProduct['CODIGO'] ?? '?') . ': ' . $e->getMessage()
                    );
                }
            }

            $this->syncLogResource->addLog(
                'product',
                'import',
                $result['errors'] > 0 ? 'error' : 'success',
                sprintf(
                    'Criados: %d, Atualizados: %d, Erros: %d, Ignorados: %d',
                    $result['created'],
                    $result['updated'],
                    $result['errors'],
                    $result['skipped']
                ),
                null,
                null,
                $result['created'] + $result['updated']
            );
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Product sync failed: ' . $e->getMessage());
            $this->syncLogResource->addLog('product', 'import', 'error', $e->getMessage());
            $result['errors']++;
        }

        return $result;
    }

    public function syncBySku(string $sku): bool
    {
        try {
            $sql = "SELECT m.CODIGO, m.DESCRICAO, m.COMPLEMENTO, m.CODINTERNO,
                           m.NCM, m.CPESO, m.VPESO, m.DIMENSOES, m.UNDVENDA,
                           m.CKCOMERCIALIZA, m.CCKATIVO,
                           c.VLRCUSTO, c.MARGEMSUG,
                           p.VLRVENDA
                    FROM MT_MATERIAL m
                    LEFT JOIN MT_MATERIALCUSTO c ON c.MATERIAL = m.CODIGO AND c.FILIAL = :filial1
                    LEFT JOIN MT_COMPOSICAOPRECO p ON p.MATERIAL = m.CODIGO AND p.FILIAL = :filial2
                    WHERE m.CODIGO = :sku";

            $row = $this->connection->fetchOne($sql, [
                ':filial1' => $this->helper->getStockFilial(),
                ':filial2' => $this->helper->getStockFilial(),
                ':sku' => $sku,
            ]);

            return $row !== null;
        } catch (\Exception $e) {
            $this->logger->error('[ERP] Single product sync error: ' . $e->getMessage());
            return false;
        }
    }
}
