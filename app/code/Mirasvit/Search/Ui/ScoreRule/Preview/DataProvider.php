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

namespace Mirasvit\Search\Ui\ScoreRule\Preview;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DataProvider extends ProductDataProvider
{
    private $productCollectionFactory;

    private $context;

    private $scoreRuleModifier;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Modifier\ScoreRuleModifier $scoreRuleModifier,
        ProductCollectionFactory $productCollectionFactory,
        ContextInterface $context,
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        array $addFieldStrategies = [],
        array $addFilterStrategies = [],
        array $meta = [],
        array $data = []
    ) {
        $this->scoreRuleModifier        = $scoreRuleModifier;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->context                  = $context;

        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $productCollectionFactory,
            $addFieldStrategies,
            $addFilterStrategies,
            $meta,
            $data
        );
    }

    public function getData(): array
    {
        $usedFactorIds = $this->scoreRuleModifier->modifyCollection($this->getCollection());
        $data = parent::getData();

        return $data;
    }

    /**
     * @return \Magento\Eav\Model\Entity\Collection\AbstractCollection|\Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    public function getCollection()
    {
        /** @var \Magento\Eav\Model\Entity\Collection\AbstractCollection $collection */
        $collection = parent::getCollection();
        $collection->addFieldToSelect('status');

        return $collection;
    }
}
