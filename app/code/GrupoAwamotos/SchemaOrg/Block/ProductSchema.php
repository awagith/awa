<?php
/**
 * Product Schema.org Block
 * Gera markup JSON-LD para páginas de produto
 */
declare(strict_types=1);

namespace GrupoAwamotos\SchemaOrg\Block;

use Magento\Catalog\Block\Product\View;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use Magento\Review\Model\ReviewFactory;
use Magento\Store\Model\StoreManagerInterface;

class ProductSchema extends Template
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ReviewFactory
     */
    protected $reviewFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param ReviewFactory $reviewFactory
     * @param StoreManagerInterface $storeManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ReviewFactory $reviewFactory,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->reviewFactory = $reviewFactory;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * Get current product
     *
     * @return \Magento\Catalog\Model\Product|null
     */
    public function getProduct()
    {
        return $this->registry->registry('current_product');
    }

    /**
     * Get product schema data
     *
     * @return array
     */
    public function getProductSchemaData()
    {
        $product = $this->getProduct();
        if (!$product) {
            return [];
        }

        $store = $this->storeManager->getStore();
        $baseUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
        
        // Imagem principal
        $imageUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 
                    'catalog/product' . $product->getImage();

        // Dados básicos
        $description = $product->getShortDescription() ?: $product->getDescription();
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->getName(),
            'description' => $description ? strip_tags($description) : '',
            'sku' => $product->getSku(),
            'image' => $imageUrl,
            'url' => $product->getProductUrl(),
        ];

        // Marca
        if ($manufacturer = $product->getAttributeText('manufacturer')) {
            $schema['brand'] = [
                '@type' => 'Brand',
                'name' => $manufacturer
            ];
        }

        // Preço e disponibilidade
        $extensionAttributes = $product->getExtensionAttributes();
        $stockItem = $extensionAttributes ? $extensionAttributes->getStockItem() : null;
        $inStock = $stockItem && $stockItem->getIsInStock();
        
        $schema['offers'] = [
            '@type' => 'Offer',
            'price' => number_format($product->getFinalPrice(), 2, '.', ''),
            'priceCurrency' => 'BRL',
            'availability' => $inStock ? 
                'https://schema.org/InStock' : 
                'https://schema.org/OutOfStock',
            'url' => $product->getProductUrl(),
            'priceValidUntil' => date('Y-12-31'),
        ];

        // Special price
        if ($product->getSpecialPrice() && $product->getSpecialPrice() < $product->getPrice()) {
            $schema['offers']['priceSpecification'] = [
                '@type' => 'UnitPriceSpecification',
                'price' => number_format($product->getSpecialPrice(), 2, '.', ''),
                'priceCurrency' => 'BRL'
            ];
        }

        // Ratings e reviews
        try {
            $reviewSummary = $product->getRatingSummary();
            if ($reviewSummary && $reviewSummary->getRatingSummary()) {
                $ratingValue = $reviewSummary->getRatingSummary() / 20; // Converte de 0-100 para 0-5
                $reviewCount = $reviewSummary->getReviewsCount();
                
                if ($reviewCount > 0) {
                    $schema['aggregateRating'] = [
                        '@type' => 'AggregateRating',
                        'ratingValue' => number_format($ratingValue, 1),
                        'reviewCount' => $reviewCount,
                        'bestRating' => '5',
                        'worstRating' => '1'
                    ];
                }
            }
        } catch (\Exception $e) {
            // Silencioso se não houver reviews
        }

        // GTIN/EAN se disponível
        if ($gtin = $product->getData('gtin')) {
            $schema['gtin'] = $gtin;
        } elseif ($ean = $product->getData('ean')) {
            $schema['gtin13'] = $ean;
        }

        // Condição (sempre novo para e-commerce)
        $schema['itemCondition'] = 'https://schema.org/NewCondition';

        return $schema;
    }

    /**
     * Get schema JSON
     *
     * @return string
     */
    public function getSchemaJson()
    {
        $data = $this->getProductSchemaData();
        if (empty($data)) {
            return '';
        }

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
