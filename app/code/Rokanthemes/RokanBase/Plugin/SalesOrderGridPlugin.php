<?php

namespace Rokanthemes\RokanBase\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection;

/**
 * Class SalesOrderGridPlugin
 * @package Magento\Braintree\Plugin
 * @author Aidan Threadgold <aidan@gene.co.uk>
 */
class SalesOrderGridPlugin
{
    /**
     * @param Collection $subject
     * @return null
     * @throws LocalizedException
     */
    public function beforeLoad(Collection $subject)
    {
        if (!$subject->isLoaded()) {
            $primaryKey = $subject->getResource()->getIdFieldName();
            $tableName = $subject->getResource()->getTable('sales_order_address');
			$tableName1 = $subject->getResource()->getTable('sales_order_status_history');	
			$tableName2 = $subject->getResource()->getTable('sales_order_item');	
			$subject->getSelect()->joinLeft(
                $tableName2,
                $tableName2 . '.order_id = main_table.' . $primaryKey,
                $tableName2 . '.name' 
            )->group('main_table.' . $primaryKey);
			
			$subject->getSelect()->joinLeft(
                $tableName1,
                $tableName1 . '.parent_id = main_table.' . $primaryKey,
                $tableName1 . '.comment' 
            )->group('main_table.' . $primaryKey);
			
            $subject->getSelect()->joinLeft(
                $tableName,
                $tableName . '.parent_id = main_table.' . $primaryKey,
                $tableName . '.telephone' 
            )->group('main_table.' . $primaryKey);
        }

        return null;
    }
}
