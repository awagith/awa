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

namespace Mirasvit\Search\Index\Mirasvit\Blog\Post;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection;
use Mirasvit\Search\Model\Index\AbstractIndex;

class Index extends AbstractIndex
{
    public function getName(): string
    {
        return 'Mirasvit / Blog MX';
    }

    public function getIdentifier(): string
    {
        return 'mirasvit_blog_post';
    }

    public function getPrimaryKey(): string
    {
        return 'post_id';
    }

    public function buildSearchCollection(): Collection
    {
        /** @var \Mirasvit\BlogMx\Model\ResourceModel\Post\CollectionFactory $collection */
        $collectionFactory = ObjectManager::getInstance()
            ->create('\Mirasvit\BlogMx\Model\ResourceModel\Post\CollectionFactory');

        $collection = $collectionFactory->create()->addVisibilityFilter();

        $orderBy   = $this->getIndex()->getProperty('sort_by');
        $orderExpr = null;

        switch ($orderBy) {
            case 'created_at|asc':
                $orderExpr = new \Zend_Db_Expr('created_at asc');
                break;
            case 'created_at|desc':
                $orderExpr = new \Zend_Db_Expr('created_at desc');
                break;
        }

        $this->context->getSearcher()->joinMatches($collection, 'post_id', [], $orderExpr);

        return $collection;
    }

    public function getIndexableDocuments($storeId, $entityIds = null, $lastEntityId = null, $limit = 100): array
    {
        $collectionFactory = $this->context->getObjectManager()
            ->create('Mirasvit\BlogMx\Model\ResourceModel\Post\CollectionFactory');

        /** @var \Mirasvit\BlogMx\Model\ResourceModel\Post\Collection $collection */
        $collection = $collectionFactory->create();
        $collection->addVisibilityFilter();
        $collection->addStoreFilter((int)$storeId);

        if ($entityIds) {
            $collection->addFieldToFilter('post_id', ['in' => $entityIds]);
        }

        $collection->addFieldToFilter('post_id', ['gt' => $lastEntityId])
            ->setPageSize($limit)
            ->setOrder('post_id', 'ASC');

        return $collection->toArray()['items'];
    }

    public function getAttributes(): array
    {
        return [
            'name'             => __('Name'),
            'content'          => __('Content'),
            'short_content'    => __('Short Content'),
            'meta_title'       => __('Meta Title'),
            'meta_keywords'    => __('Meta Keywords'),
            'meta_description' => __('Meta Description'),
        ];
    }

    public function getSortingOptions(): array
    {
        return [
            'relevance'       => 'Relevance',
            'created_at|asc'  => __('Created At / Jan-Feb'),
            'created_at|desc' => __('Created At / Feb-Jan'),
        ];
    }
}
