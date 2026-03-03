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

namespace Mirasvit\SearchElastic\Plugin;

use Magento\Framework\Search\RequestInterface;
use Mirasvit\Search\Model\ConfigProvider;
use Mirasvit\Search\Repository\ScoreRuleRepository;
use Mirasvit\SearchElastic\Plugin\PutScoreBoostBeforeAddDocsPlugin as ScoreBoostProcessor;

/**
 * @see \Magento\Elasticsearch\Elasticsearch5\SearchAdapter\Mapper::buildQuery()
 */
class ElasticsearchAddScriptToSearchQueryPlugin
{
    protected $scoreRuleRepository;

    public function __construct(
        ScoreRuleRepository $scoreRuleRepository
    ) {
        $this->scoreRuleRepository = $scoreRuleRepository;
    }

    public function aroundBuildQuery($subject, callable $proceed, RequestInterface $request)
    {
        $searchQuery = $proceed($request);

        if (($request->getQuery()->getName() == 'quick_search_container' || strpos($request->getQuery()->getName(), 'graphql_product_search') !== false)
            && $this->isSortByRelevance($request)
        ) {
            $searchQuery['body']['query']['script_score']['query']  = $searchQuery['body']['query'];
            $searchQuery['body']['query']['script_score']['script'] = [
                'source' => ConfigProvider::DEFAULT_SCORE . " + _score * doc['" . ConfigProvider::MULTIPLY_ATTRIBUTE . "'].value + doc['" . ConfigProvider::SUM_ATTRIBUTE . "'].value",
            ];

            unset($searchQuery['body']['query']['bool']);
        }

        // change minimum_should_match only for search requests
        if ($request->getQuery()->getName() !== 'catalog_view_container'
            && $request->getQuery()->getName() !== 'advanced_search_container') {
            if (!empty($searchQuery['body']['query']['bool'])
                && isset($searchQuery['body']['query']['bool']['minimum_should_match'])) {
                $searchQuery['body']['query']['bool']['minimum_should_match'] = 0;
            }

            if (!empty($searchQuery['body']['query']['script_score']['query']['bool'])
                && isset($searchQuery['body']['query']['script_score']['query']['bool']['minimum_should_match'])) {
                $searchQuery['body']['query']['script_score']['query']['bool']['minimum_should_match'] = 0;
            }
        }

        return $searchQuery;
    }

    private function isSortByRelevance(RequestInterface $request): bool
    {
        foreach ($request->getSort() as $sort) {
            if ($sort['field'] == 'relevance') {
                return true;
            }
        }

        return false;
    }
}
