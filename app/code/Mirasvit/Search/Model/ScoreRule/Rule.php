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



namespace Mirasvit\Search\Model\ScoreRule;

use Magento\Catalog\Model\ProductFactory as ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\Iterator;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Rule\Model\AbstractModel;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Core\Service\CompatibilityService;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ObjectManager;
use Mirasvit\Search\Api\Data\ScoreRuleInterface;
use Mirasvit\Search\Ui\ScoreRule\Source\ScoreFactorRelatively;

class Rule extends AbstractModel
{
    const FORM_NAME = 'search_scorerule_form';

    /**
     * @var Condition\CombineFactory
     */
    private $conditionCombineFactory;

    /**
     * @var ProductCollectionFactory
     */
    private $productCollectionFactory;

    /**
     * @var ProductFactory
     */
    private $productFactory;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var Iterator
     */
    private $iterator;

    /**
     * @var array
     */
    private $productIds = [];


    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;


    public function __construct(
        Condition\CombineFactory $conditionCombineFactory,
        ProductCollectionFactory $productCollectionFactory,
        ProductFactory $productFactory,
        ResourceConnection $resource,
        Iterator $iterator,
        StoreManagerInterface $storeManager,
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        TimezoneInterface $localeDate
    ) {
        $this->conditionCombineFactory    = $conditionCombineFactory;
        $this->productCollectionFactory   = $productCollectionFactory;
        $this->productFactory             = $productFactory;
        $this->resource                   = $resource;
        $this->iterator                   = $iterator;
        $this->storeManager               = $storeManager;

        parent::__construct($context, $registry, $formFactory, $localeDate);
    }

    /**
     * {@inheritdoc}
     */
    public function getActionsInstance()
    {
        return $this->postConditonCombineFactory->create();
    }

    /**
     * @return \Magento\Rule\Model\Condition\Combine|Condition\Combine
     */
    public function getConditionsInstance()
    {
        return $this->conditionCombineFactory->create();
    }

    /**
     * Get array of product ids which are matched by rule
     *
     * @param array $ids
     *
     * @return array
     */
    public function getMatchingProductIds(array $ids, ?int $storeId = null)
    {
        $productCollection = $this->productCollectionFactory->create();

        if (empty($storeId)) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $productCollection->addStoreFilter($storeId);

        if (count($ids)) {
            $productCollection->addFieldToFilter('entity_id', $ids);
        }

        $this->getConditions()->collectValidatedAttributes($productCollection);

        $this->iterator->walk(
            $productCollection->getSelect(),
            [[$this, 'callbackValidateProduct']],
            [
                'attributes' => $this->getCollectedAttributes(),
                'product'    => $this->productFactory->create(),
            ]
        );

        return $this->productIds;
    }

    /**
     * Callback function for product matching
     *
     * @param array $args
     *
     * @return void
     */
    public function callbackValidateProduct($args)
    {
        $product = clone $args['product'];
        $product->setData($args['row']);

        if ($product->getTypeId() == Configurable::TYPE_CODE) {
            $product->setData('product', $product->load($product->getId()));
        } else {
            $product->setData('product', $product);
        }

        if ($this->getConditions()->validate($product)) {
            $this->productIds[] = $product->getId();
        }
    }

    public function getScoreFactors(ScoreRuleInterface $scoreRule, array $productIds, int $storeId): array
    {
        [$sign, $factor, $relatively] = explode('|', $scoreRule->getScoreFactor());

        $result = [];

        if ($relatively === ScoreFactorRelatively::RELATIVELY_POPULARITY) {
            $connection = $this->resource->getConnection();

            $select = $connection->select()
                ->from(
                    ['oi' => $this->resource->getTableName('sales_order_item')],
                    ['product_id', 'cnt' => new \Zend_Db_Expr('count(*)')]
                )->joinLeft(
                    ['super' => $this->resource->getTableName('catalog_product_super_link')],
                    'super.product_id = oi.product_id',
                    ['super.parent_id']
                )
                ->joinLeft(
                    ['link' => $this->resource->getTableName('catalog_product_link')],
                    'link.linked_product_id = oi.product_id AND link.link_type_id=3',
                    ['link_id' => 'link.product_id']
                )
                ->group('product_id');

            $rows = $connection->fetchAll($select);

            foreach ($rows as $row) {
                if (!isset($result[$row['product_id']])) {
                    $result[$row['product_id']] = 0;
                }
                $result[$row['product_id']] += $row['cnt'];

                if ($row['parent_id'] > 0) {
                    if (!isset($result[$row['parent_id']])) {
                        $result[$row['parent_id']] = 0;
                    }

                    $result[$row['parent_id']] += $row['cnt'];
                }

                if ($row['parent_id'] > 0) {
                    if (!isset($result[$row['parent_id']])) {
                        $result[$row['parent_id']] = 0;
                    }

                    $result[$row['parent_id']] += $row['cnt'];
                }

                if ($row['link_id'] > 0) {
                    if (!isset($result[$row['link_id']])) {
                        $result[$row['link_id']] = 0;
                    }

                    $result[$row['link_id']] += $row['cnt'];
                }
            }

            $max = 0;
            foreach ($result as $v) {
                if ($v > $max) {
                    $max = $v;
                }
            }
            foreach ($result as $key => $value) {
                $result[$key] = $sign . (($value / $max) + 1) * $factor;
            }
            foreach ($productIds as $productId) {
                if (!isset($result[$productId])) {
                    $result[$productId] = '+0';
                }
            }
        } elseif ($relatively == ScoreFactorRelatively::RELATIVELY_RATING) {
            foreach ($productIds as $productId) {
                $result[$productId] = '+0';
            }

            $connection = $this->resource->getConnection();

            $select = $connection->select()->from(
                $this->resource->getTableName('review_entity_summary'),
                ['product_id' => 'entity_pk_value', 'rating' => new \Zend_Db_Expr('max(rating_summary)')]
            )->where('store_id=?', $storeId)->group('product_id');

            $rows = $connection->fetchPairs($select);
            $max  = 100;

            foreach ($rows as $productId => $rating) {
                $result[$productId] = $sign . ($rating / $max) * $factor;
            }
        } else {
            foreach ($productIds as $productId) {
                $result[$productId] = $sign . $factor;
            }
        }

        return $result;
    }
}
