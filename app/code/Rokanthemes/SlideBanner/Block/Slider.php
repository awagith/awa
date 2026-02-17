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
	public function getImageElement($src, $altText = '', $isFirst = false)
	{
		$mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
		if (!$src) {
			return '<img src="" alt="Slide" loading="lazy" decoding="async" width="1920" height="600" />';
		}
		$alt = $altText ? strip_tags($altText) : $this->getSlider()->getSliderTitle();
		$alt = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
		$loading = $isFirst ? 'eager' : 'lazy';
		$priority = $isFirst ? ' fetchpriority="high"' : '';
		return '<img src="' . $mediaUrl . $src . '" alt="' . $alt . '" loading="' . $loading . '" decoding="async" width="1920" height="600"' . $priority . ' />';
	}
	
	public function getImageElementMobile($src, $altText = '', $isFirst = false)
	{
		$mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
		if (!$src) {
			return '<img src="" alt="Slide" loading="lazy" decoding="async" width="768" height="400" />';
		}
		$alt = $altText ? strip_tags($altText) : $this->getSlider()->getSliderTitle();
		$alt = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
		$loading = $isFirst ? 'eager' : 'lazy';
		$priority = $isFirst ? ' fetchpriority="high"' : '';
		return '<img src="' . $mediaUrl . $src . '" alt="' . $alt . '" loading="' . $loading . '" decoding="async" width="768" height="400"' . $priority . ' />';
	}

	/**
	 * Resolve sanitized alt text for a slide.
	 */
	private function resolveAltText($altText)
	{
		$sliderTitle = $this->getSlider() ? $this->getSlider()->getSliderTitle() : 'Slide';
		$raw = $altText ? strip_tags($altText) : $sliderTitle;
		return htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Returns a <picture> element with desktop/mobile sources.
	 * Eliminates duplicated slider HTML for desktop/mobile.
	 */
	public function getPictureElement($desktopSrc, $mobileSrc, $altText = '', $isFirst = false)
	{
		$mediaUrl = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
		$alt = $this->resolveAltText($altText);
		$loading = $isFirst ? 'eager' : 'lazy';
		$priority = $isFirst ? ' fetchpriority="high"' : '';

		$desktopUrl = $desktopSrc ? $mediaUrl . $desktopSrc : '';
		$mobileUrl = $mobileSrc ? $mediaUrl . $mobileSrc : $desktopUrl;

		$source = $desktopUrl
			? '<source media="(min-width: 768px)" srcset="' . $desktopUrl . '" width="1920" height="600" />'
			: '';

		return '<picture>'
			. $source
			. '<img src="' . ($mobileUrl ?: $desktopUrl) . '" alt="' . $alt . '" loading="' . $loading
			. '" decoding="async" width="768" height="400"' . $priority . ' />'
			. '</picture>';
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
		if(is_null($this->_slider)){
			// First try to load by explicit slider_id if set
			$sliderId = $this->getSliderId();
			if($sliderId) {
				$this->_slider = $this->_sliderFactory->create();
				$this->_slider->load($sliderId, 'slider_identifier');
				if($this->_slider->getId() && $this->_slider->getSliderStatus() == 1) {
					return $this->_slider;
				}
			}
			
			// Fallback to original logic
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
		}
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
