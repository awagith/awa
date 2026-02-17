<?php

namespace Rokanthemes\SearchSuiteAutocomplete\Model\Search;

use \Rokanthemes\SearchSuiteAutocomplete\Helper\Data as HelperData;
use \Magento\Search\Helper\Data as SearchHelper;
use \Magento\Search\Model\AutocompleteInterface;
use \Magento\Framework\App\RequestInterface;
use \Rokanthemes\SearchSuiteAutocomplete\Model\Source\AutocompleteFields;

/**
 * Suggested model. Return suggested data used in search autocomplete
 */
class Suggested implements \Rokanthemes\SearchSuiteAutocomplete\Model\SearchInterface
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
     * @var \Magento\Search\Model\AutocompleteInterface;
     */
    protected $autocomplete;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * Suggested constructor.
     *
     * @param HelperData $helperData
     * @param SearchHelper $searchHelper
     * @param AutocompleteInterface $autocomplete
     * @param RequestInterface|null $request
     */
    public function __construct(
        HelperData $helperData,
        SearchHelper $searchHelper,
        AutocompleteInterface $autocomplete,
        ?RequestInterface $request = null
    ) {
        $this->helperData   = $helperData;
        $this->searchHelper = $searchHelper;
        $this->autocomplete = $autocomplete;
        $this->request      = $request ?: \Magento\Framework\App\ObjectManager::getInstance()->get(RequestInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseData()
    {
        $responseData['code'] = AutocompleteFields::SUGGEST;
        $responseData['data'] = [];

        if (!$this->canAddToResult()) {
            return $responseData;
        }

        $suggestResultNumber = $this->helperData->getSuggestedResultNumber();
        $categoryId = $this->getCategoryIdFilter();

        $autocompleteData = $this->autocomplete->getItems();
        $autocompleteData = array_slice($autocompleteData, 0, $suggestResultNumber);
        foreach ($autocompleteData as $item) {
            $item                   = $item->toArray();
            $item['url']            = $this->buildResultUrl($item['title'], $categoryId);
            $responseData['data'][] = $item;
        }

        return $responseData;
    }

    /**
     * {@inheritdoc}
     */
    public function canAddToResult()
    {
        return in_array(AutocompleteFields::SUGGEST, $this->helperData->getAutocompleteFieldsAsArray());
    }

    /**
     * @param string $queryText
     * @param int $categoryId
     * @return string
     */
    private function buildResultUrl($queryText, $categoryId)
    {
        $url = $this->searchHelper->getResultUrl($queryText);
        if ($categoryId > 0) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . 'cat=' . $categoryId;
        }

        return $url;
    }

    /**
     * @return int
     */
    private function getCategoryIdFilter()
    {
        return (int)$this->request->getParam('cat');
    }
}
