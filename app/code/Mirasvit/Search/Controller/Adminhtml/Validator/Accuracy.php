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

namespace Mirasvit\Search\Controller\Adminhtml\Validator;

use Magento\Backend\App\Action\Context;
use Magento\Elasticsearch\Model\Adapter\FieldMapperInterface;
use Magento\Elasticsearch\SearchAdapter\ConnectionManager;
use Magento\Elasticsearch\SearchAdapter\SearchIndexNameResolver;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManager;
use Mirasvit\Search\Api\Data\IndexInterface;
use Mirasvit\Search\Controller\Adminhtml\AbstractValidator;
use Mirasvit\Search\Model\ConfigProvider;
use Mirasvit\Search\Repository\IndexRepository;
use Mirasvit\Search\Service\QueryService;
use Mirasvit\SearchElastic\Plugin\PutScoreBoostBeforeAddDocsPlugin as ScoreBoostProcessor;
use Mirasvit\SearchElastic\SearchAdapter\QueryBuilder;

class Accuracy extends AbstractValidator
{
    private $indexRepository;

    private $connectionManager;

    private $indexNameResolver;

    private $storeManager;

    private $queryBuilder;

    private $queryService;

    private $fieldMapper;

    private $resultJsonFactory;

    public function __construct(
        IndexRepository         $indexRepository,
        ConnectionManager       $connectionManager,
        SearchIndexNameResolver $indexNameResolver,
        StoreManager            $storeManager,
        QueryBuilder            $queryBuilder,
        QueryService            $queryService,
        FieldMapperInterface    $fieldMapper,
        JsonFactory             $resultJsonFactory,
        Context                 $context
    ) {
        $this->indexRepository   = $indexRepository;
        $this->connectionManager = $connectionManager;
        $this->indexNameResolver = $indexNameResolver;
        $this->storeManager      = $storeManager;
        $this->queryBuilder      = $queryBuilder;
        $this->queryService      = $queryService;
        $this->fieldMapper       = $fieldMapper;
        $this->resultJsonFactory = $resultJsonFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $searchTerm = (string)$this->getRequest()->getParam('searchTerm');

        $indexes = $this->indexRepository->getCollection()
            ->addFieldToFilter(IndexInterface::IS_ACTIVE, true);

        $html = '';

        foreach ($this->storeManager->getStores() as $store) {
            if (!$store->getIsActive()) {
                continue;
            }

            $storeId = (int)$store->getId();

            foreach ($indexes as $index) {
                $html .= '<h3>' . $index->getIdentifier() . ' / store: ' . $storeId . '</h3>';

                $logicQuery  = $this->queryService->build($searchTerm);
                $searchQuery = $this->searchQuery($index, $storeId, $searchTerm, true);

                $html .= '<table>';
                $html .= '<tr>';
                $html .= '<td><strong>ES Query</strong><pre>' . json_encode($searchQuery, JSON_PRETTY_PRINT) . '</pre></td>';
                $html .= '<td><strong>Abstract Query</strong><pre>' . json_encode($logicQuery, JSON_PRETTY_PRINT) . '</pre></td>';
                $html .= '</tr>';
                $html .= '</table><br>';

                $html .= '<table>';
                $html .= '<tr>';
                $html .= "<th>#</th>";
                $html .= "<th>Score</th>";
                $html .= "<th>ID</th>";

                foreach ($index->getAttributes() as $attribute => $weight) {
                    if ($weight <= 1) {
                        continue;
                    }
                    $html .= "<th>$attribute<small>weight: $weight</small></th>";
                }
                $html .= "<th>Boosting<small>original score | multiply | plus</small></th>";
                $html .= '</tr>';


                $originalScore = [];
                foreach ($this->getResults($index, $storeId, $searchTerm, false) as $item) {
                    if (!$item) {
                        continue;
                    }

                    $originalScore[(int)$item['_id']] = (float)$item['_score'];
                }

                foreach ($this->getResults($index, $storeId, $searchTerm, true) as $idx => $item) {
                    if (!$item) {
                        continue;
                    }
                    $html   .= '<tr>';
                    $id     = $item['_id'];
                    $score  = number_format((float)$item['_score'], 2);
                    $source = $item['_source'];

                    $scoreSum      = $source['mst_score_sum'] ?? '-';
                    $scoreMultiply = $source['mst_score_multiply'] ?? '-';

                    $pos  = $idx + 1;
                    $html .= "<td>$pos</td>";
                    $html .= "<td class='score'><div style='min-width: 100px;text-align: right;'>$score</div></td>";
                    $html .= "<td class='id'>$id</td>";

                    foreach ($index->getAttributes() as $attribute => $weight) {
                        if ($weight <= 1) {
                            continue;
                        }

                        $value = $source[$attribute] ?? '-';
                        if (isset($source[$attribute . '_value'])) {
                            $value = $source[$attribute . '_value'];
                        }

                        if (!is_scalar($value)) {
                            $value = json_encode($value, JSON_PRETTY_PRINT);
                        } else {
                            $value = $this->removeNonUTF8(htmlspecialchars((string)$value));
                        }

                        if (strlen($value) > 1000) {
                            $value = substr($value, 0, 1000) . '...';
                        }

                        $html .= "<td class='$attribute'>$value</td>";
                    }

                    $original = isset($originalScore[$id]) ? number_format((float)$originalScore[$id], 2) : '-';

                    if (abs(floatval($scoreMultiply)) > 1) {
                        $scoreMultiply = '<strong>' . $scoreMultiply . '</strong>';
                    }

                    if (abs(floatval($scoreSum)) > 1) {
                        $scoreSum = '<strong>' . $scoreSum . '</strong>';
                    }

                    $html .= "<td class='id'><div style='display: flex; gap: 5px'><div style='min-width: 100px;text-align: right;'>$original</div> | <div style='min-width: 50px;text-align: right;'>*$scoreMultiply</div> | <div style='min-width: 50px;text-align: right;'>+$scoreSum</div></td>";

                    $html .= '</tr>';
                }

                $html .= '</table>';

                $html .= '<br><br>';
            }
        }

        $response = $this->resultJsonFactory->create();

        return $response->setData([
            'html' => $html,
        ]);
    }

    private function getEsResponse(IndexInterface $index, int $storeId, string $searchTerm, bool $scriptScore): ?array
    {
        /** @var \Magento\Elasticsearch7\Model\Client\Elasticsearch $connection */
        $connection = $this->connectionManager->getConnection();

        if (!$connection->indexExists($this->getIndexName($index, $storeId))) {
            return null;
        }

        return $connection->query($this->searchQuery($index, $storeId, $searchTerm, $scriptScore));
    }

    private function getResults(IndexInterface $index, int $storeId, string $searchTerm, bool $scriptScore): array
    {
        $response = $this->getEsResponse($index, $storeId, $searchTerm, $scriptScore);
        if (!$response) {
            return [null];
        }

        $result = [];

        if (isset($response['hits']['hits'])) {
            foreach ($response['hits']['hits'] as $hit) {
                $result[] = $hit;
            }
        }

        return $result;
    }

    private function getIndexName(IndexInterface $index, int $storeId): string
    {
        return $this->indexNameResolver->getIndexName(
            $storeId,
            $index->getIdentifier()
        );
    }

    private function searchQuery(IndexInterface $index, int $storeId, string $searchTerm, bool $scriptScore): array
    {
        $fields = [
            '_misc' => 0,
        ];

        foreach ($index->getAttributes() as $attr => $weight) {
            $field          = $this->fieldMapper->getFieldName($attr, ['type' => FieldMapperInterface::TYPE_QUERY]);
            $fields[$field] = $weight;
        }

        $query = $this->queryBuilder->build([], $searchTerm, $fields);

        $esQuery = [
            'index' => $this->getIndexName($index, $storeId),
            'body'  => [
                'from'          => 0,
                'size'          => 10000,
                'stored_fields' => [
                    '_id',
                    '_source',
                ],
                'sort'          => [
                    ['_score' => ['order' => 'desc']],
                ],
                'query'         => [
                    'script_score' => [
                        'query'  => $query,
                        'script' => [
                            'source' => "_score",
                        ],
                    ],
                ],
            ],
        ];

        if ($index->getIdentifier() == 'catalogsearch_fulltext') {
            $esQuery['body']['query']['script_score']['query']['bool']['must'] = [
                [
                    'terms' => [
                        'visibility' => [3, 4],
                    ],
                ],
                $esQuery['body']['query']['script_score']['query']['bool']['must'][0],
            ];
        }

        if ($scriptScore) {
            $esQuery['body']['query']['script_score']['script']['source'] = ConfigProvider::DEFAULT_SCORE . " + _score * doc['" . ConfigProvider::MULTIPLY_ATTRIBUTE . "'].value + doc['" . ConfigProvider::SUM_ATTRIBUTE . "'].value";
        }

        return $esQuery;
    }

    private function removeNonUTF8(string $input): string
    {
        $input = preg_replace('/[^(\x20-\x7F)]*/', '', $input);
        $input = preg_replace(
            '/[\x00-\x08\x10\x0B\x0C\x0E-\x19\x7F]' .
            '|[\x00-\x7F][\x80-\xBF]+' .
            '|([\xC0\xC1]|[\xF0-\xFF])[\x80-\xBF]*' .
            '|[\xC2-\xDF]((?![\x80-\xBF])|[\x80-\xBF]{2,})' .
            '|[\xE0-\xEF](([\x80-\xBF](?![\x80-\xBF]))|(?![\x80-\xBF]{2})|[\x80-\xBF]{3,})/S',
            '',
            $input
        );

        $input = preg_replace('/\xE0[\x80-\x9F][\x80-\xBF]|\xED[\xA0-\xBF][\x80-\xBF]/S', '', $input);

        return $input;
    }

    public function _processUrlKeys(): bool
    {
        return true;
    }
}