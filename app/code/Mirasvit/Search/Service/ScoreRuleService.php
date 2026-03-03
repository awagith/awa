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

namespace Mirasvit\Search\Service;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Search\Request;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Core\Service\SerializeService;
use Mirasvit\Search\Api\Data\ScoreRuleInterface;
use Mirasvit\Search\Model\ScoreRule\Indexer\ScoreRuleIndexer;
use Mirasvit\Search\Repository\ScoreRuleRepository;

class ScoreRuleService
{
    private $resource;

    private $storeManager;

    private $scoreRuleRepository;

    public function __construct(
        ResourceConnection    $resource,
        StoreManagerInterface $storeManager,
        ScoreRuleRepository   $scoreRuleRepository
    ) {
        $this->resource            = $resource;
        $this->storeManager        = $storeManager;
        $this->scoreRuleRepository = $scoreRuleRepository;
    }

    public function checkConflicts(): string
    {
        $connection = $this->resource->getConnection();

        $select = $connection->select()->from(['index' => $this->getIndexTable()], [
            'product_id',
        ])->group('store_id')
            ->group('product_id')
            ->having('COUNT(*) > 1')
            ->limit(10);

        $result = $connection->fetchAll($select);
        if (count($result) == 0) {
            return '';
        }

        return (string)__('There are products that match a few boost rules. Please adjust your rules to avoid overlap.');
    }

    private function calculate(float $score, string $action): float
    {
        $result = $score;
        if (preg_match('/([\+\-\*\/])(?:\s*)(\d+)/', $action, $matches) !== false) {
            $operator = $matches[1];

            switch ($operator) {
                case '+':
                    $result = $score + $matches[2];
                    break;
                case '-':
                    $result = $score - $matches[2];
                    break;
                case '*':
                    $result = $score * $matches[2];
                    break;
                case '/':
                    $result = $score / $matches[2];
                    break;
            }
        }

        return (float)$result;
    }

    private function getIndexTable(): string
    {
        return $this->resource->getTableName(ScoreRuleInterface::INDEX_TABLE_NAME);
    }
}
