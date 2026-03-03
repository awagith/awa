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

namespace Mirasvit\SearchReport\Cron;


use Magento\Framework\App\ResourceConnection;
use Mirasvit\SearchReport\Api\Data\LogInterface;

class CleanupCron
{
    private $resource;

    public function __construct(
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
    }

    public function execute()
    {
        $this->resource->getConnection()
            ->delete(
                $this->resource->getTableName(LogInterface::TABLE_NAME),
                'created_at < DATE(NOW()-INTERVAL 365 DAY)'
            );
    }
}