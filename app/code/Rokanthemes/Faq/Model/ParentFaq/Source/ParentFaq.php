<?php

namespace Rokanthemes\Faq\Model\ParentFaq\Source;

use Magento\Framework\Model\Context;

class ParentFaq implements \Magento\Framework\Option\ArrayInterface
{
    protected $ParentFaq;
	protected $request;
	protected $objectManager;
    protected $_resource;

    public function __construct(
		\Magento\Framework\App\Request\Http $request,
		\Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\App\ResourceConnection $resource,
        \Rokanthemes\Faq\Model\RokanFaqFactory $ParentFaq
    ) {
        $this->ParentFaq = $ParentFaq;
		$this->request = $request;
		$this->objectManager = $objectManager;
        $this->_resource = $resource;
    }
	
    public function toOptionArray()
    {
		$patternId = $this->request->getParam('id');
		$array = [];
		$array[] = ['value' => '0', 'label' => __('Root')]; 
		$data_query = $this->getDataParentId(0);
		if(count($data_query) > 0){
			if($patternId){
				foreach ($data_query as $item) {
					if($patternId != $item['entity_id']){
						$array[] = ['value' => $item['entity_id'], 'label' => ''.str_repeat('-', 3).''.$item['question'].'']; 
						$data_query_lv2 = $this->getDataParentId($item['entity_id']);
						if(count($data_query_lv2) > 0){
							foreach ($data_query_lv2 as $item_lv2) {
								if($patternId != $item_lv2['entity_id']){
									$array[] = ['value' => $item_lv2['entity_id'], 'label' => ''.str_repeat('-', 6).''.$item_lv2['question'].'']; 
									$data_query_lv3 = $this->getDataParentId($item_lv2['entity_id']);
									if(count($data_query_lv3) > 0){
										foreach ($data_query_lv3 as $item_lv3) {
											if($patternId != $item_lv3['entity_id']){
												$array[] = ['value' => $item_lv3['entity_id'], 'label' => ''.str_repeat('-', 9).''.$item_lv3['question'].'']; 
											}
										}
									}
								}
							}
						}
					}
				}
			}else{
				foreach ($data_query as $item) {
					$array[] = ['value' => $item['entity_id'], 'label' => ''.str_repeat('-', 3).''.$item['question'].'']; 
					$data_query_lv2 = $this->getDataParentId($item['entity_id']);
					if(count($data_query_lv2) > 0){
						foreach ($data_query_lv2 as $item_lv2) {
							$array[] = ['value' => $item_lv2['entity_id'], 'label' => ''.str_repeat('-', 6).''.$item_lv2['question'].'']; 
							$data_query_lv3 = $this->getDataParentId($item_lv2['entity_id']);
							if(count($data_query_lv3) > 0){
								foreach ($data_query_lv3 as $item_lv3) {
									$array[] = ['value' => $item_lv3['entity_id'], 'label' => ''.str_repeat('-', 9).''.$item_lv3['question'].'']; 
								}
							}
						}
					}
				}
			}
		}
        return $array;
    }
	
	public function getDataParentId($parent_id) 
    {
        $adapter  = $this->_resource->getConnection();
		$tableName = $this->_resource->getTableName("rokan_faq");
        $sql = "SELECT * FROM ".$tableName." WHERE status='1' AND parent_id='$parent_id'";
        $data_query = $adapter->fetchAll($sql);
        return $data_query;
    }
}