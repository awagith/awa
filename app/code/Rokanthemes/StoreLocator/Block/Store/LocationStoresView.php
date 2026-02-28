<?php
/**
 * Copyright © 2019 Rokanthemes. All rights reserved. 
 */

namespace Rokanthemes\StoreLocator\Block\Store;

use \Rokanthemes\StoreLocator\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;
use \Magento\Framework\Json\Helper\Data as DataHelper;
use \Rokanthemes\StoreLocator\Helper\Config as ConfigHelper;
use \Rokanthemes\StoreLocator\Model\ResourceModel\Store\Collection as StoreCollection;
use \Rokanthemes\StoreLocator\Model\Store;
use \Rokanthemes\StoreLocator\Model\StoreFactory;

class LocationStoresView extends \Magento\Framework\View\Element\Template
{

    private $storeCollectionFactory;
    private $dataHelper;
    private $configHelper;
	private $_jsonEncoder;
    private $storeFactory;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
			\Magento\Framework\Json\EncoderInterface $jsonEncoder,
	        StoreCollectionFactory $storeCollectionFactory,
	        DataHelper $dataHelper, 
	        ConfigHelper $configHelper,
	        array $data = [],
            ?StoreFactory $storeFactory = null
	    ) {
	        $this->storeCollectionFactory = $storeCollectionFactory;
	        $this->dataHelper = $dataHelper;
			$this->_jsonEncoder = $jsonEncoder;
	        $this->configHelper = $configHelper;
            $this->storeFactory = $storeFactory ?: \Magento\Framework\App\ObjectManager::getInstance()
                ->get(StoreFactory::class);
	        parent::__construct($context, $data);
	    }
		
	    public function getStoreViewLocator()
	    {
			$id = (int) $this->getRequest()->getParam('key');
	        $locations = $this->storeFactory->create()->load($id);
	        return $locations;
	    }
	public function getTimeStoreLocator($id)
    {
		$locations = $this->storeFactory->create()->load((int) $id);
        $time = $this->decodeStoreTime((string) $locations->getTimeStore());
		$weekday = strtolower(date('l'));
		$weekday_time = $weekday . '_time';
		$weekday_time_today = [];
		$weekday_time_today['today'] = ($time !== null && isset($time->$weekday_time) && (int) $time->$weekday_time === 1) ? 1 : 0;
		$weekday_time_today['time_today'] = ($time !== null && isset($time->$weekday) && is_object($time->$weekday)) ? $time->$weekday : null;
		return $weekday_time_today;
    }
	public function getAllTimeStoreLocator($id)
    {
		$time_arr = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
        $locations = $this->storeFactory->create()->load((int) $id);
		$time = $this->decodeStoreTime((string) $locations->getTimeStore());
		$weekday = date("l");
		$weekday = strtolower($weekday);
		$html = '';
		foreach($time_arr as $arr){
			$weekday_time = $arr.'_time';
			if($weekday == $arr){
				$html .=   '<div class="active"><span>'.$arr.'</span> <span>';
			}else{
				$html .=   '<div><span>'.$arr.'</span> <span>';
			}
			if ($time === null || !isset($time->$weekday_time) || (int) $time->$weekday_time !== 1) {
				$html .= ''.__('Closed').'</span></div>';
                continue;
			}

            $timeRange = $this->formatDaySchedule(
                isset($time->$arr) && is_object($time->$arr) ? $time->$arr : null
            );
            $html .= ($timeRange === null ? (string) __('Closed') : $timeRange) . '</span></div>';
		}
		return $html;
	}
	public function getApiKey()
    {
        $googleApiKey = $this->configHelper->getGoogleApiKeyFrontend(); 
        return $googleApiKey;
    }
	public function getString()
    {
        return '?' . http_build_query($this->getRequest()->getParams());
    }
	public function getJsonLocations()
    {
		$id = (int) $this->getRequest()->getParam('key');
		$locations_model = $this->storeCollectionFactory->create();
		$locationsArray = [];
	        foreach($locations_model as $location) {
			if($location->getId() == $id){
				$location->load($location->getId());
				$locationsArray[] = $location;
			}   
        }
        $locations = $locationsArray;
        $locationArray = [];
        $locationArray['items'] = [];
        foreach ($locations as $location) { 
            $locationArray['items'][] = $location->getData();
        }
        $locationArray['totalRecords'] = count($locationArray['items']);
        $store = $this->_storeManager->getStore(true)->getId();
        $locationArray['currentStoreId'] = $store;

        return $this->_jsonEncoder->encode($locationArray);
    }
	public function getBaloonTemplate()
    {
		$mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $baloon = '<h2><div class="locator-title">{{name}}</div></h2>  
                    <div class="store">
						<div class="image">
							<img src="'.$mediaUrl.'{{image_store}}" />
						</div>
						<div class="info">
							<p>Cidade: {{city}}</p>
							<p>CEP: {{zip}}</p>
							<p>País: {{country}}</p>
							<p>Endereço: {{address}}</p>
						</div>
					</div>	
					<div>
						Descrição: {{des}} 
					</div>
					';

        $store_url = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $store_url =  $store_url . 'rokanthemes/storeLocator/';

        $baloon = str_replace(
            '{{photo}}',
            '<img src="' . $store_url . '{{photo}}">',
            $baloon
        );

	        return $this->_jsonEncoder->encode(array("baloon" => $baloon));
    }

    /**
     * @param string $rawValue
     * @return object|null
     */
    private function decodeStoreTime(string $rawValue)
    {
        if (trim($rawValue) === '') {
            return null;
        }

        $decoded = json_decode($rawValue);
        return is_object($decoded) ? $decoded : null;
    }

    /**
     * @param object|null $daySchedule
     * @return string|null
     */
    private function formatDaySchedule($daySchedule)
    {
        if (!is_object($daySchedule) || !isset($daySchedule->from, $daySchedule->to)) {
            return null;
        }

        if (!is_object($daySchedule->from) || !is_object($daySchedule->to)) {
            return null;
        }

        if (!isset($daySchedule->from->hours, $daySchedule->from->minutes, $daySchedule->to->hours, $daySchedule->to->minutes)) {
            return null;
        }

        return sprintf(
            '%02d : %02d AM - %02d : %02d PM',
            (int) $daySchedule->from->hours,
            (int) $daySchedule->from->minutes,
            (int) $daySchedule->to->hours,
            (int) $daySchedule->to->minutes
        );
    }
}
