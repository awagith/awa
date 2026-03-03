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



namespace Mirasvit\Search\Service;

use Magento\Search\Model\ResourceModel\SynonymGroup as SynonymGroupResourceModel;
use Magento\Search\Model\SynonymAnalyzer;
use Magento\Search\Model\SynonymGroupFactory;
use Magento\Search\Model\SynonymGroupRepository;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface as Logger;

class SynonymService
{
    private $resourceModel;

    private $synonymRepository;

    private $synonymFactory;

    private $synonymAnalyzer;

    private $cloudService;

    private $storeManager;

    private $logger;

    public function __construct(
        SynonymGroupResourceModel $resourceModel,
        SynonymGroupRepository    $synonymRepository,
        SynonymGroupFactory       $synonymFactory,
        SynonymAnalyzer           $synonymAnalyzer,
        CloudService              $cloudService,
        StoreManagerInterface     $storeManager,
        Logger                    $logger
    ) {
        $this->resourceModel     = $resourceModel;
        $this->synonymRepository = $synonymRepository;
        $this->synonymFactory    = $synonymFactory;
        $this->synonymAnalyzer   = $synonymAnalyzer;
        $this->cloudService      = $cloudService;
        $this->storeManager      = $storeManager;
        $this->logger            = $logger;
    }

    public function getSynonyms(array $terms, int $storeId): array
    {
        $result = [];
        foreach ($terms as $term) {
            $term     = trim($term);
            $synonyms = $this->synonymAnalyzer->getSynonymsForPhrase($term);

            if (empty($synonyms)) {
                continue;
            }

            foreach (explode(' ', $term) as $key => $word) {
                if (!isset($synonyms[$key])) {
                    continue;
                }

                if (count($synonyms[$key]) > 1) {
                    if (array_search($word, $synonyms[$key]) !== false) {
                        unset($synonyms[$key][array_search($word, $synonyms[$key])]);
                    }

                    $tmp = [$word => $synonyms[$key]];
                } else {
                    $tmp = [$word => ''];
                }

                $result = array_merge($result, $tmp);
            }
        }

        $result = array_filter($result);

        return $result;
    }

    public function import(string $file, array $storeIds): \Generator
    {
        $result = [
            'synonyms' => 0,
            'total'    => 0,
            'errors'   => 0,
            'message'  => '',
        ];

        if (file_exists($file)) {
            $content = file_get_contents($file);
        } else {
            $file    = explode('/', $file);
            $file    = end($file);
            $content = $this->cloudService->get('search', 'synonym', $file);
        }

        if (!$content) {
            $result['errors']++;
            $result['message'] = __("The file is empty or doesn't exists.");

            yield $result;
        } else {
            if (strlen($content) > 10000 && php_sapi_name() != "cli") {
                $result['errors']++;
                $result['message'] = __('File is too large. Please use CLI interface (bin/magento mirasvit:search:synonym --file EN.csv --store 1)');

                yield $result;
            } else {
                $synonyms = [];

                foreach (explode(PHP_EOL, $content) as $line) {
                    if ($line) {
                        $synonyms[] = str_getcsv($line);
                    }
                }

                if (!is_array($storeIds)) {
                    $storeIds = [$storeIds];
                }

                foreach ($storeIds as $storeId) {
                    try {
                        $websiteId       = (int)$this->storeManager->getStore($storeId)->getWebsiteId();
                        $result['total'] = count($synonyms);

                        foreach ($synonyms as $group) {
                            if (!is_array($group) || count($group) <= 1) {
                                $result['errors']++;
                                $this->logger->info(!is_array($group)? 'group is not array' : 'count of group is ' . count($group) . ', group: ' . implode(',', $group));

                                yield $result;
                            } else {
                                try {
                                    $words        = array_map('trim', $group);
                                    $synonymGroup = $this->prepareString(implode(',', $words));

                                    if ($term = $this->validateSynonyms($synonymGroup)) {
                                        $result['errors']++;
                                        $result['message'] = __('The term %1 can\'t use only special symbols.', $term);
                                        $this->logger->info(__('The term %1 can\'t use only special symbols.', $term));

                                        yield $result;
                                    } else {
                                        $model = $this->synonymFactory->create()
                                            ->setSynonymGroup($synonymGroup)
                                            ->setStoreId((int)$storeId)
                                            ->setWebsiteId($websiteId);

                                        $processed = $this->synonymRepository->save($model);

                                        if ($stored = $processed->getSynonymGroup()) {
                                            $matchingSynonymGroups = $this->getMatchingSynonymGroups($processed);

                                            if (!empty($matchingSynonymGroups)) {
                                                $mergedSynonyms = $this->mergeWithExisting($processed, array_keys($matchingSynonymGroups));
                                                $mergedString   = implode(',', $mergedSynonyms);

                                                $processed->setSynonymGroup($mergedString)
                                                    ->save();
                                            } else {
                                                $unified       = array_unique(explode(',', $this->prepareString($stored)));
                                                $unifiedString = implode(',', $unified);

                                                if ($unifiedString !== $stored) {
                                                    $processed->setSynonymGroup($unifiedString)
                                                        ->save();
                                                }
                                            }
                                        }

                                        $result['synonyms']++;
                                    }
                                } catch (\Exception $e) {
                                    $result['errors']++;
                                    $result['message'] = $e->getMessage();
                                    $this->logger->info($e->getMessage());
                                }

                                yield $result;
                            }
                        }

                    } catch (\Exception $e) {
                        $result['errors']++;
                        $result['message'] = $e->getMessage();
                        $this->logger->info($e->getMessage());

                        yield $result;
                    }
                }
            }
        }
    }

    public function validateSynonyms(string $synonyms): string
    {
        $words = explode(',', $synonyms ?? '');

        foreach ($words as $word) {
            if ($word) {
                $matches = null;
                preg_match('/[\p{L}\p{N}]/', $word, $matches);

                if (!$matches) {
                    return $word;
                }
            }
        }

        return '';
    }

    public function prepareString($string): string
    {
        return preg_replace('/[\x00-\x1F\x80-\xFF]/', '', strtolower($string));
    }

    public function getMatchingSynonymGroups($synonymGroup): array
    {
        $synonymGroupsInScope  = $this->resourceModel->getByScope(
            $synonymGroup->getWebsiteId(),
            $synonymGroup->getStoreId()
        );
        $matchingSynonymGroups = [];

        foreach ($synonymGroupsInScope as $synonymGroupInScope) {
            if (array_intersect(
                    explode(',', $synonymGroup->getSynonymGroup() ?? ''),
                    explode(',', $this->prepareString($synonymGroupInScope['synonyms'] ?? ''))
                )
                && ($synonymGroupInScope['group_id'] !== $synonymGroup->getGroupId())
            ) {
                $matchingSynonymGroups[$synonymGroupInScope['group_id']] = $synonymGroupInScope['synonyms'];
            }
        }

        return $matchingSynonymGroups;
    }

    public function mergeWithExisting($synonymGroupToMerge, $matchingGroupIds): array
    {
        $mergedSynonyms = [];

        foreach ($matchingGroupIds as $groupId) {
            $synonymGroupModel = $this->synonymFactory->create();
            $synonymGroupModel->load($groupId);

            $mergedSynonyms[] = explode(',', $this->prepareString($synonymGroupModel->getSynonymGroup() ?? ''));
            $synonymGroupModel->delete();
        }

        $mergedSynonyms[] = explode(',', $synonymGroupToMerge->getSynonymGroup() ?? '');

        return array_unique(array_merge([], ...$mergedSynonyms));
    }
}
