<?php
/**
 * Quick Order SKU Search Controller
 * Endpoint: GET b2b/quickorder/search?q=SKU_FRAGMENT
 * Returns JSON array of matching products for autocomplete
 */
declare(strict_types=1);

namespace GrupoAwamotos\B2B\Controller\Quickorder;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Psr\Log\LoggerInterface;

class Search implements HttpGetActionInterface
{
    private const MAX_RESULTS = 10;
    private const MIN_QUERY_LENGTH = 2;

    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $resultJsonFactory,
        private readonly CollectionFactory $productCollectionFactory,
        private readonly StockRegistryInterface $stockRegistry,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly CustomerSession $customerSession,
        private readonly LoggerInterface $logger
    ) {}

    public function execute(): Json
    {
        $result = $this->resultJsonFactory->create();

        if (!$this->customerSession->isLoggedIn()) {
            return $result->setData(['products' => []]);
        }

        $query = trim((string) $this->request->getParam('q', ''));

        if (strlen($query) < self::MIN_QUERY_LENGTH) {
            return $result->setData(['products' => []]);
        }

        try {
            $products = $this->searchProducts($query);
            return $result->setData(['products' => $products]);
        } catch (\Exception $e) {
            $this->logger->error('[B2B QuickOrder Search] Error', ['exception' => $e]);
            return $result->setData(['products' => [], 'error' => true]);
        }
    }

    /**
     * Search products by SKU prefix and name fragment
     *
     * @return array<int, array{sku:string,name:string,price:string,stock:int,in_stock:bool,url:string}>
     */
    private function searchProducts(string $query): array
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'price', 'sku', 'status', 'url_key']);
        $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
        $collection->addAttributeToFilter('visibility', ['neq' => 1]); // exclude not-visible-individually

        // Search: SKU starts with OR SKU contains OR name contains
        $collection->addAttributeToFilter([
            ['attribute' => 'sku', 'like' => $this->like($query)],
            ['attribute' => 'name', 'like' => '%' . $query . '%'],
        ]);

        $collection->setPageSize(self::MAX_RESULTS);
        $collection->setCurPage(1);
        $collection->addUrlRewrite();

        $results = [];

        /** @var ProductInterface $product */
        foreach ($collection as $product) {
            try {
                $stockItem = $this->stockRegistry->getStockItem((int) $product->getId());
                $qty = (int) $stockItem->getQty();
                $inStock = $stockItem->getIsInStock() && $qty > 0;
            } catch (\Exception $e) {
                $qty = 0;
                $inStock = false;
            }

            $results[] = [
                'sku'      => (string) $product->getSku(),
                'name'     => (string) $product->getName(),
                'price'    => $this->priceCurrency->format(
                    (float) $product->getFinalPrice(),
                    false,
                    PriceCurrencyInterface::DEFAULT_PRECISION
                ),
                'price_raw' => (float) $product->getFinalPrice(),
                'stock'    => $qty,
                'in_stock' => $inStock,
                'url'      => (string) $product->getProductUrl(),
            ];
        }

        // Sort: exact SKU match first, then by SKU prefix match, then alpha
        usort($results, function (array $a, array $b) use ($query): int {
            $qLower = strtolower($query);
            $aExact = strtolower($a['sku']) === $qLower ? 0 : 1;
            $bExact = strtolower($b['sku']) === $qLower ? 0 : 1;
            if ($aExact !== $bExact) {
                return $aExact - $bExact;
            }

            $aPrefix = str_starts_with(strtolower($a['sku']), $qLower) ? 0 : 1;
            $bPrefix = str_starts_with(strtolower($b['sku']), $qLower) ? 0 : 1;
            if ($aPrefix !== $bPrefix) {
                return $aPrefix - $bPrefix;
            }

            return strcmp($a['sku'], $b['sku']);
        });

        return $results;
    }

    private function like(string $query): string
    {
        // Escape LIKE special chars, then add wildcard
        $escaped = str_replace(['%', '_', '\\'], ['\\%', '\\_', '\\\\'], $query);
        return $escaped . '%';
    }
}
