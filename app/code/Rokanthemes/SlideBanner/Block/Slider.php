<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Rokanthemes\SlideBanner\Block;

/**
 * Cms block content block
 */
class Slider extends \Magento\Framework\View\Element\Template 
{
    protected $_filterProvider;
	protected $_sliderFactory;
	protected $_bannerFactory;

	protected $_scopeConfig;
	protected $_storeManager;
	protected $_slider;

    /**
     * @param Context $context
     * @param array $data
     */
	
   public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
		\Rokanthemes\SlideBanner\Model\SliderFactory $sliderFactory,
		\Rokanthemes\SlideBanner\Model\SlideFactory $slideFactory,
		\Magento\Cms\Model\Template\FilterProvider $filterProvider,
		\Magento\Store\Model\StoreManagerInterface $storeManager,
		array $data = []
	) {
		parent::__construct($context, $data);
		$this->_sliderFactory = $sliderFactory;
		$this->_bannerFactory = $slideFactory;
		$this->_scopeConfig = $context->getScopeConfig();
		$this->_storeManager = $context->getStoreManager();
		$this->_filterProvider = $filterProvider;
		$this->_storeManager = $storeManager;
	}

    /**
     * Prepare Content HTML
     *
     * @return string
     */
    protected function _beforeToHtml()
    {
        if (!$this->getTemplate()) {
			$this->setTemplate("Rokanthemes_SlideBanner::slider.phtml");
        }
        return parent::_beforeToHtml();
    }

    /**
     * Return identifiers for produced content
     *
     * @return array
     */
	public function getImageElement($src)
	{
		$mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
		return '<img style="display: none;" class="lazyOwl" alt="' . $this->getSlider()->getSliderTitle() . '" src="'.$mediaUrl . $src . '" data-src="'. $mediaUrl . $src . '" />';
	}
	public function getBannerCollection()
	{
		$slider = $this->getSlider();
		if(is_null($slider)){
			return [];
		}
		$sliderId = $slider->getId();
		$collection = $this->_bannerFactory->create()->getCollection();
		$collection->addFieldToFilter('slider_id', $sliderId);
		$collection->addFieldToFilter('slide_status', 1);
		return $collection;
	}
	public function getSlider()
	{
		if(is_null($this->_slider)):
			$all_collections = $this->_sliderFactory->create()->getCollection()->addFieldToFilter('slider_status', 1);
			if(count($all_collections) > 0){
				$store_id = $this->getStoreId();
				foreach ($all_collections as $value) {
					$stores_ids = $value->getStoreIds();
					if($stores_ids && $stores_ids != ''){
						$check_json = json_decode($stores_ids, true);
						if(in_array($store_id, $check_json)){
							$sliderId = $value->getId();
							$this->_slider = $this->_sliderFactory->create();
							$this->_slider->load($sliderId);
							break;
						}
						elseif (isset($check_json[0]) && $check_json[0] == 0) {
							$sliderId = $value->getId();
							$this->_slider = $this->_sliderFactory->create();
							$this->_slider->load($sliderId);
						}
					}
				}
			}
			else{
				$sliderId = $this->getSliderId();
				$this->_slider = $this->_sliderFactory->create();
				$this->_slider->load($sliderId);
			}
		endif;
		return $this->_slider;
	}
	public function getContentText($html)
	{
		$html = $this->_filterProvider->getPageFilter()->filter($html);
        return $html;
	}
	public function getStoreId()
    {
        return $this->_storeManager->getStore()->getId();
    }
}
