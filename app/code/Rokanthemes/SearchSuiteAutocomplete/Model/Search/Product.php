<?php

namespace Rokanthemes\SearchSuiteAutocomplete\Model\Search;

use \Rokanthemes\SearchSuiteAutocomplete\Helper\Data as HelperData;
use \Magento\Search\Helper\Data as SearchHelper;
use \Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use \Magento\Framework\ObjectManagerInterface as ObjectManager;
use \Magento\Framework\App\RequestInterface;
use \Magento\Search\Model\QueryFactory;
use \Rokanthemes\SearchSuiteAutocomplete\Model\Source\AutocompleteFields;
use \Rokanthemes\SearchSuiteAutocomplete\Model\Source\ProductFields;

/**
 * Product model. Return product data used in search autocomplete
 */
class Product implements \Rokanthemes\SearchSuiteAutocomplete\Model\SearchInterface
{
    /**
     * @var \Rokanthemes\SearchSuiteAutocomplete\Helper\Data
     */
    protected $helperData;

    /**
     * @var \Magento\Search\Helper\Data
     */
    protected $searchHelper;

    /**
     * @var \Magento\Catalog\Model\Layer\Resolver
     */
    protected $layerResolver;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Search\Model\QueryFactory
     */
    private $queryFactory;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * Product constructor.
     *
     * @param HelperData $helperData
     * @param SearchHelper $searchHelper
     * @param LayerResolver $layerResolver
     * @param ObjectManager $objectManager
     * @param QueryFactory $queryFactory
     * @param RequestInterface|null $request
     */
    public function __construct(
        HelperData $helperData,
        SearchHelper $searchHelper,
        LayerResolver $layerResolver,
        ObjectManager $objectManager,
        QueryFactory $queryFactory,
        ?RequestInterface $request = null
    ) {
        $this->helperData    = $helperData;
        $this->searchHelper  = $searchHelper;
        $this->layerResolver = $layerResolver;
        $this->objectManager = $objectManager;
        $this->queryFactory  = $queryFactory;
        $this->request       = $request ?: $this->objectManager->get(RequestInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseData()
    {
        $responseData['code'] = AutocompleteFields::PRODUCT;
        $responseData['data'] = [];

        if (!$this->canAddToResult()) {
            return $responseData;
        }

        $query                 = $this->queryFactory->get();
        $queryText             = $query->getQueryText();
        $productResultFields   = $this->helperData->getProductResultFieldsAsArray();
        $productResultFields[] = ProductFields::URL;

        $productCollection = $this->getProductCollection($queryText);

        foreach ($productCollection as $product) {
            $responseData['data'][] = array_intersect_key(
                $this->getProductData($product),
                array_flip($productResultFields)
            );
        }

        $categoryId = $this->getCategoryIdFilter();
        $responseData['size'] = $productCollection->getSize();
        $responseData['url']  = '';
        if ($productCollection->getSize() > 0) {
            $resultUrl = $this->searchHelper->getResultUrl($queryText);
            if ($categoryId > 0) {
                $resultUrl .= (strpos($resultUrl, '?') === false ? '?' : '&') . 'cat=' . $categoryId;
            }
            $responseData['url'] = $resultUrl;
        }

        $query->saveNumResults($responseData['size']);
        $query->saveIncrementalPopularity();

        return $responseData;
    }

    /**
     * Retrive product collection by query text
     *
     * @param string $queryText
     * @return mixed
     */
    protected function getProductCollection($queryText)
    {
        $productResultNumber = $this->helperData->getProductResultNumber();

        $this->layerResolver->create(LayerResolver::CATALOG_LAYER_SEARCH);

        $productCollection = $this->layerResolver->get()
                                                 ->getProductCollection()
                                                 ->addAttributeToSelect(
                                                     [ProductFields::DESCRIPTION, ProductFields::SHORT_DESCRIPTION]
                                                 )
                                                 ->setPageSize($productResultNumber)
                                                 ->addAttributeToSort('relevance')
                                                 ->setOrder('relevance')
                                                 ->addSearchFilter($queryText);

        $categoryId = $this->getCategoryIdFilter();
        if ($categoryId > 0) {
            $productCollection->addCategoriesFilter(['in' => [$categoryId]]);
        }

        return $productCollection;
    }

    /**
     * Read category filter from request.
     *
     * @return int
     */
    private function getCategoryIdFilter()
    {
        return (int)$this->request->getParam('cat');
    }

    /**
     * Retrieve all product data
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return array
     */
    protected function getProductData($product)
    {
        /** @var \Rokanthemes\SearchSuiteAutocomplete\Block\Autocomplete\ProductAgregator $productAgregator */
        $productAgregator = $this->objectManager->create(
            'Rokanthemes\SearchSuiteAutocomplete\Block\Autocomplete\ProductAgregator'
        )
                                                ->setProduct($product);

        $data = [
            ProductFields::NAME              => $productAgregator->getName(),
            ProductFields::SKU               => $productAgregator->getSku(),
            ProductFields::IMAGE             => $productAgregator->getSmallImage(),
            ProductFields::REVIEWS_RATING    => $productAgregator->getReviewsRating(),
            ProductFields::SHORT_DESCRIPTION => $productAgregator->getShortDescription(),
            ProductFields::DESCRIPTION       => $productAgregator->getDescription(),
            ProductFields::PRICE             => $productAgregator->getPrice(),
            ProductFields::URL               => $productAgregator->getUrl()
        ];

        if ($product->getData('is_salable')) {
            $data[ProductFields::ADD_TO_CART] = $productAgregator->getAddToCartData();
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function canAddToResult()
    {
        return in_array(AutocompleteFields::PRODUCT, $this->helperData->getAutocompleteFieldsAsArray());
    }
}
