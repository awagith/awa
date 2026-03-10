<?php
/**
 * Product Schema.org Block
 * Gera markup JSON-LD para páginas de produto
 */
declare(strict_types=1);

namespace GrupoAwamotos\SchemaOrg\Block;

use DateTimeImmutable;
use JsonException;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class ProductSchema extends Template
{
    private const SCHEMA_CONTEXT = 'https://schema.org';
    private const SCHEMA_PRODUCT_TYPE = 'Product';
    private const SCHEMA_CURRENCY = 'BRL';
    private const SCHEMA_IN_STOCK = 'https://schema.org/InStock';
    private const SCHEMA_OUT_OF_STOCK = 'https://schema.org/OutOfStock';
    private const SCHEMA_NEW_CONDITION = 'https://schema.org/NewCondition';
    private const SCHEMA_SALE_PRICE = 'https://schema.org/SalePrice';
    private const DEFAULT_SELLER_NAME = 'Grupo Awamotos';

    private readonly Registry $registry;
    private readonly StoreManagerInterface $storeManager;
    private readonly LoggerInterface $logger;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        parent::__construct($context, $data);
    }

    /**
     * Get current product
     *
     * @return Product|null
     */
    public function getProduct(): ?Product
    {
        $product = $this->registry->registry('current_product');

        return $product instanceof Product ? $product : null;
    }

    /**
     * Get product schema data
     *
     * @return array<string, mixed>
     */
    public function getProductSchemaData(): array
    {
        $product = $this->getProduct();
        if (!$product) {
            return [];
        }

        $productUrl = (string) $product->getProductUrl();
        $imageUrl = $this->buildProductImageUrl($product);
        $description = $product->getShortDescription() ?: $product->getDescription();

        $schema = [
            '@context' => self::SCHEMA_CONTEXT,
            '@type' => self::SCHEMA_PRODUCT_TYPE,
            'name' => (string) $product->getName(),
            'description' => $description ? $this->normalizeDescription($description) : '',
            'sku' => (string) $product->getSku(),
            'image' => $imageUrl,
            'url' => $productUrl,
        ];

        $manufacturer = $product->getAttributeText('manufacturer');
        if ($manufacturer) {
            $schema['brand'] = [
                '@type' => 'Brand',
                'name' => (string) $manufacturer,
            ];
        }

        $inStock = (bool) $product->isAvailable();
        $finalPrice = (float) $product->getFinalPrice();
        $regularPrice = (float) $product->getPrice();
        $specialPrice = $product->getSpecialPrice() !== null ? (float) $product->getSpecialPrice() : null;

        $schema['offers'] = [
            '@type' => 'Offer',
            'priceCurrency' => self::SCHEMA_CURRENCY,
            'availability' => $inStock ? self::SCHEMA_IN_STOCK : self::SCHEMA_OUT_OF_STOCK,
            'url' => $productUrl,
            'priceValidUntil' => $this->getPriceValidUntil(),
            'itemCondition' => self::SCHEMA_NEW_CONDITION,
            'seller' => [
                '@type' => 'Organization',
                'name' => self::DEFAULT_SELLER_NAME,
            ],
        ];

        if ($finalPrice > 0) {
            $schema['offers']['price'] = number_format($finalPrice, 2, '.', '');
        }

        if ($specialPrice !== null && $specialPrice > 0 && $regularPrice > 0 && $specialPrice < $regularPrice) {
            $schema['offers']['priceSpecification'] = [
                '@type' => 'UnitPriceSpecification',
                'priceType' => self::SCHEMA_SALE_PRICE,
                'price' => number_format($specialPrice, 2, '.', ''),
                'priceCurrency' => self::SCHEMA_CURRENCY,
            ];
        }

        $reviewSummary = $product->getRatingSummary();
        if ($reviewSummary && method_exists($reviewSummary, 'getRatingSummary')) {
            $ratingSummary = (float) $reviewSummary->getRatingSummary();
            $ratingValue = $ratingSummary > 0 ? round($ratingSummary / 20, 1) : 0.0;

            $reviewCount = method_exists($reviewSummary, 'getReviewsCount')
                ? (int) $reviewSummary->getReviewsCount()
                : 0;

            if ($ratingValue > 0 && $reviewCount > 0) {
                $schema['aggregateRating'] = [
                    '@type' => 'AggregateRating',
                    'ratingValue' => number_format($ratingValue, 1, '.', ''),
                    'reviewCount' => (string) $reviewCount,
                ];
            }
        }

        $gtin = trim((string) $product->getData('gtin'));
        $ean = trim((string) $product->getData('ean'));
        if ($gtin !== '') {
            $schema['gtin'] = $gtin;
        } elseif ($ean !== '') {
            $schema['gtin13'] = $ean;
        }

        $schema['itemCondition'] = self::SCHEMA_NEW_CONDITION;

        return $schema;
    }

    /**
     * Get schema JSON
     *
     * @return string
     */
    public function getSchemaJson(): string
    {
        $data = $this->getProductSchemaData();
        if (empty($data)) {
            return '';
        }

        try {
            return json_encode(
                $data,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            $this->logger->error('Falha ao gerar JSON-LD de produto.', [
                'error' => $e->getMessage(),
                'product_id' => $this->getProduct()?->getId(),
            ]);

            return '';
        }
    }

    /**
     * Build a normalized media URL for the current product image.
     *
     * @param Product $product
     * @return string
     */
    private function buildProductImageUrl(Product $product): string
    {
        $image = trim((string) $product->getImage());
        if ($image === '' || $image === 'no_selection') {
            return '';
        }

        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore();

        return rtrim(
            $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA),
            '/'
        ) . '/catalog/product/' . ltrim($image, '/');
    }

    /**
     * Normaliza descrição para JSON-LD removendo tags e espaços em excesso.
     *
     * @param string $description
     * @return string
     */
    private function normalizeDescription(string $description): string
    {
        $plainText = trim(strip_tags($description));

        return (string) preg_replace('/\s+/u', ' ', $plainText);
    }

    /**
     * Data limite de validade do preço (31/12 do próximo ano).
     *
     * @return string
     */
    private function getPriceValidUntil(): string
    {
        return (new DateTimeImmutable('+1 year'))->format('Y-12-31');
    }
}
