<?php

declare(strict_types=1);

namespace Meta\Catalog\Model\Feed;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Meta\BusinessExtension\Api\SystemConfigInterface;
use Meta\BusinessExtension\Helper\GraphAPIAdapter;
use Psr\Log\LoggerInterface;

/**
 * Manages product feed synchronization to Meta Commerce catalog
 */
class ProductFeedManager
{
    private const BATCH_SIZE = 100;

    public function __construct(
        private readonly SystemConfigInterface $config,
        private readonly GraphAPIAdapter $graphApi,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly CollectionFactory $collectionFactory,
        private readonly StockRegistryInterface $stockRegistry,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Sync all visible products to Meta catalog
     */
    public function syncAllProducts(): void
    {
        $storeId = $this->resolveStoreId();
        $catalogId = $this->config->getCatalogId($storeId);
        if ($catalogId === null || !$this->config->isActive($storeId)) {
            $this->logger->info('[Meta Catalog] Sync skipped: inactive or no catalog ID');
            return;
        }

        $collection = $this->collectionFactory->create();
        if ($storeId !== null) {
            $collection->setStoreId($storeId);
            $collection->addStoreFilter($storeId);
        }
        $collection->addAttributeToSelect(['name', 'price', 'special_price', 'image', 'url_key', 'description', 'short_description', 'manufacturer'])
            ->addFieldToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
            ->addFieldToFilter('visibility', ['in' => [
                Visibility::VISIBILITY_IN_CATALOG,
                Visibility::VISIBILITY_IN_SEARCH,
                Visibility::VISIBILITY_BOTH
            ]]);

        $collection->setPageSize(self::BATCH_SIZE);
        $lastPage = $collection->getLastPageNumber();

        $this->logger->info('[Meta Catalog] Starting full sync', [
            'store_id' => $storeId,
            'total_products' => $collection->getSize(),
            'total_pages' => $lastPage
        ]);

        for ($page = 1; $page <= $lastPage; $page++) {
            $collection->setCurPage($page);
            $batch = [];

            foreach ($collection as $product) {
                $productData = $this->buildProductData($product, $storeId);
                if ($productData !== null) {
                    $batch[] = [
                        'method' => 'UPDATE',
                        'data' => $productData
                    ];
                }
            }

            if (!empty($batch)) {
                $result = $this->graphApi->sendCatalogBatch($catalogId, $batch, $storeId);
                if (isset($result['error'])) {
                    $this->logger->warning('[Meta Catalog] Batch API error', [
                        'store_id' => $storeId,
                        'page' => $page,
                        'items' => count($batch),
                        'http_status' => $result['http_status'] ?? null,
                        'error' => $result['error']
                    ]);
                }
                $this->logger->info('[Meta Catalog] Batch sent', [
                    'store_id' => $storeId,
                    'page' => $page,
                    'items' => count($batch),
                    'result' => isset($result['error']) ? 'error' : 'success'
                ]);
            }

            $collection->clear();
        }

        $this->logger->info('[Meta Catalog] Full sync completed', ['store_id' => $storeId]);
    }

    /**
     * Sync a single product
     */
    public function syncProduct(ProductInterface $product): void
    {
        $storeId = $this->resolveStoreIdFromProduct($product);
        $catalogId = $this->config->getCatalogId($storeId);
        if ($catalogId === null || !$this->config->isActive($storeId)) {
            return;
        }

        $productData = $this->buildProductData($product, $storeId);
        if ($productData === null) {
            return;
        }

        $batch = [
            [
                'method' => 'UPDATE',
                'data' => $productData
            ]
        ];

        $result = $this->graphApi->sendCatalogBatch($catalogId, $batch, $storeId);
        if (isset($result['error'])) {
            $this->logger->warning('[Meta Catalog] Single product sync API error', [
                'store_id' => $storeId,
                'sku' => (string) $product->getSku(),
                'http_status' => $result['http_status'] ?? null,
                'error' => $result['error']
            ]);
        }
    }

    /**
     * Delete a product from Meta catalog
     */
    public function deleteProduct(string $sku): void
    {
        $storeId = $this->resolveStoreId();
        $catalogId = $this->config->getCatalogId($storeId);
        if ($catalogId === null || !$this->config->isActive($storeId)) {
            return;
        }

        $batch = [
            [
                'method' => 'DELETE',
                'data' => ['id' => $sku]
            ]
        ];

        $result = $this->graphApi->sendCatalogBatch($catalogId, $batch, $storeId);
        if (isset($result['error'])) {
            $this->logger->warning('[Meta Catalog] Delete API error', [
                'store_id' => $storeId,
                'sku' => $sku,
                'http_status' => $result['http_status'] ?? null,
                'error' => $result['error']
            ]);
        }
        $this->logger->info('[Meta Catalog] Product deleted', ['sku' => $sku, 'store_id' => $storeId]);
    }

    /**
     * Build product data array for Meta catalog API
     *
     * @return array<string, mixed>|null
     */
    private function buildProductData(ProductInterface $product, ?int $storeId = null): ?array
    {
        try {
            $store = $this->storeManager->getStore($storeId);
            $baseUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            $image = $product->getData('image');
            $imageUrl = $image ? $baseUrl . 'catalog/product' . $image : null;

            $stockItem = $this->stockRegistry->getStockItemBySku($product->getSku());
            $qty = (int) $stockItem->getQty();
            $threshold = $this->config->getOutOfStockThreshold($storeId);
            $availability = ($stockItem->getIsInStock() && $qty > $threshold) ? 'in stock' : 'out of stock';

            $price = (float) $product->getData('price');
            $specialPrice = $product->getData('special_price');
            $currencyCode = (string) ($store->getCurrentCurrencyCode() ?: $store->getBaseCurrencyCode() ?: 'BRL');

            $brand = $product->getData('manufacturer');
            if (empty($brand)) {
                $brand = 'AWA Motos';
            }

            $data = [
                'id' => $product->getSku(),
                'title' => $product->getName(),
                'description' => strip_tags((string) ($product->getData('description') ?: $product->getData('short_description') ?: $product->getName())),
                'availability' => $availability,
                'inventory' => $qty,
                'condition' => 'new',
                'price' => number_format($price, 2, '.', '') . ' ' . $currencyCode,
                'brand' => $brand,
                'link' => $product->getProductUrl(),
            ];

            if ($imageUrl) {
                $data['image_link'] = $imageUrl;
            }

            if ($specialPrice !== null && (float) $specialPrice > 0 && (float) $specialPrice < $price) {
                $data['sale_price'] = number_format((float) $specialPrice, 2, '.', '') . ' ' . $currencyCode;
            }

            return $data;
        } catch (\Throwable $e) {
            $this->logger->error('[Meta Catalog] Failed to build product data', [
                'sku' => $product->getSku(),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    private function resolveStoreId(): ?int
    {
        try {
            return (int) $this->storeManager->getStore()->getId();
        } catch (\Throwable) {
            return null;
        }
    }

    private function resolveStoreIdFromProduct(ProductInterface $product): ?int
    {
        $productStoreId = $product->getStoreId();
        if ($productStoreId !== null && (int) $productStoreId > 0) {
            return (int) $productStoreId;
        }

        $storeIds = $product->getStoreIds();
        if (is_array($storeIds)) {
            foreach ($storeIds as $candidateStoreId) {
                $candidateStoreId = (int) $candidateStoreId;
                if ($candidateStoreId > 0) {
                    return $candidateStoreId;
                }
            }
        }

        return $this->resolveStoreId();
    }
}
