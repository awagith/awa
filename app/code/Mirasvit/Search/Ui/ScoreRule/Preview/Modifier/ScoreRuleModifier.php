<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-search-ultimate
 * @version   2.2.70
 * @copyright Copyright (C) 2024 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Search\Ui\ScoreRule\Preview\Modifier;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Elasticsearch\SearchAdapter\ConnectionManager;
use Magento\Elasticsearch\SearchAdapter\SearchIndexNameResolver;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Search\Api\Data\IndexInterface;
use Mirasvit\Search\Api\Data\ScoreRuleInterface;
use Mirasvit\Search\Model\ConfigProvider;
use Mirasvit\Search\Repository\IndexRepository;
use Mirasvit\Search\Repository\ScoreRuleRepository;

class ScoreRuleModifier
{
    private $request;

    private $connectionManager;

    private $catalogProductVisibility;

    private $indexNameResolver;

    private $storeManager;

    private $indexRepository;

    private $scoreRuleRepository;

    public function __construct(
        RequestInterface        $request,
        ConnectionManager       $connectionManager,
        Visibility              $catalogProductVisibility,
        SearchIndexNameResolver $indexNameResolver,
        StoreManagerInterface   $storeManager,
        IndexRepository         $indexRepository,
        ScoreRuleRepository     $scoreRuleRepository
    ) {
        $this->request                  = $request;
        $this->connectionManager        = $connectionManager;
        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->indexNameResolver        = $indexNameResolver;
        $this->storeManager             = $storeManager;
        $this->indexRepository          = $indexRepository;
        $this->scoreRuleRepository      = $scoreRuleRepository;
    }

    public function modifyCollection(AbstractCollection $collection): array
    {
        $srData = $this->request->getParam('scoreRule');

        if (!isset($srData[ScoreRuleInterface::ID])) {
            return [];
        }

        if (!$id = (int)$srData[ScoreRuleInterface::ID]) {
            return [];
        }

        $scoreRule = $this->scoreRuleRepository->get($id);
        $storeId   = $this->getStoreId();
        $ids       = [];

        if ($scoreRule->getStoreIds() && in_array($storeId, $scoreRule->getStoreIds())) {
            $ids = $scoreRule->getRule()->getMatchingProductIds([]);
            $ids = array_unique($ids);
        }

        $collection->addAttributeToFilter('entity_id', ['in' => $ids]);
        $collection->setVisibility($this->catalogProductVisibility->getVisibleInSiteIds());
        $collection->addStoreFilter($storeId);
        $scoreFactors = $scoreRule->getRule()->getScoreFactors($scoreRule, $ids, $storeId);

        $index = $this->indexRepository->getCollection()
            ->addFieldToFilter(IndexInterface::IS_ACTIVE, true)
            ->addFieldToFilter(IndexInterface::IDENTIFIER, 'catalogsearch_fulltext')
            ->getFirstItem();

        foreach ($collection as $product) {
            $score = strstr($scoreFactors[$product->getId()], '.', true) ? : $scoreFactors[$product->getId()];
            $product->setRuleScore($score);
            $product->setFactors($this->getFactors($index, $storeId, (int)$product->getId()));
        }

        return [$id];
    }

    private function getFactors(IndexInterface $index, int $storeId, int $entityId): string
    {
        foreach ($this->getSearchResults($index, $storeId, (int)$entityId, false) as $item) {
            $originalScore[(int)$item['_id']] = $item['_score'];
        }
        foreach ($this->getSearchResults($index, $storeId, (int)$entityId, true) as $idx => $result) {
            $score         = number_format((float)$result['_score'], 2);
            $scoreSum      = $result['_source']['mst_score_sum'] ?? '-';
            $scoreMultiply = $result['_source']['mst_score_multiply'] ?? '-';
            $original      = isset($originalScore[(int)$result['_id']]) ? number_format((float)$originalScore[(int)$result['_id']], 2) : '-';

            return $original . ' | ' . $score . ' | +' . $scoreSum . ' | *' . $scoreMultiply;
        }

        return '';
    }

    private function getStoreId(): int
    {
        if (($filters = $this->request->getParam('filters')) && isset($filters['store_id'])) {
            return (int)$filters['store_id'];
        }

        return (int)$this->storeManager->getDefaultStoreView()->getId();
    }

    private function getSearchResults(IndexInterface $index, int $storeId, int $entityId, bool $scriptScore): array
    {
        $result = [];

        try {
            $connection = $this->connectionManager->getConnection();
        } catch (\Exception $e) {
            return [];
        }

        if (!$connection->indexExists($this->getIndexName($index, $storeId))) {
            return [];
        }

        $response = $connection->query($this->entityQuery($index, $storeId, $entityId, $scriptScore));

        if (isset($response['hits']['hits'])) {
            foreach ($response['hits']['hits'] as $hit) {
                $result[] = $hit;
            }
        }

        return $result;
    }

    private function entityQuery(IndexInterface $index, int $storeId, int $entityId, bool $scriptScore): array
    {
        $esQuery = [
            'index' => $this->getIndexName($index, $storeId),
            'body'  => [
                'from'          => 0,
                'size'          => 10,
                'stored_fields' => [
                    '_id',
                    '_source',
                ],
                'sort'          => [
                    ['_score' => ['order' => 'desc']],
                ],
                'query'         => [
                    'script_score' => [
                        'query'  => [
                            'terms' => [
                                '_id' => [$entityId],
                            ],
                        ],
                        'script' => [
                            'source' => "_score",
                        ],
                    ],
                ],
            ],
        ];

        if ($scriptScore) {
            $esQuery['body']['query']['script_score']['script']['source'] = ConfigProvider::DEFAULT_SCORE . " + _score * doc['" . ConfigProvider::MULTIPLY_ATTRIBUTE . "'].value + doc['" . ConfigProvider::SUM_ATTRIBUTE . "'].value";
        }

        return $esQuery;
    }

    private function getIndexName(IndexInterface $index, int $storeId): string
    {
        return $this->indexNameResolver->getIndexName(
            $storeId,
            $index->getIdentifier()
        );
    }
}

