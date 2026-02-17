<?php
/**
 * Copyright © 2019 The_Blue_Sky. All rights reserved.
 * @Author: Tony Pham
 * @Email: tonypham.web.developer@gmail.com
 */

namespace Rokanthemes\Themeoption\Helper;

class Productimage extends \Magento\Framework\App\Helper\AbstractHelper {
	
	protected $_escaper;
	protected $_productImageHelper;
	
	public function __construct(
		\Magento\Framework\Escaper $_escaper,
		\Magento\Catalog\Helper\Image $productImageHelper,
		\Magento\Framework\App\Helper\Context $context
	) {
		$this->_escaper = $_escaper;
		$this->_productImageHelper = $productImageHelper;
		parent::__construct($context);
	}
	
	public function resizeProductImageSrc($product, $imageId, $width, $height = null)
    {
        $resizedImage = $this->_productImageHelper
		   ->init($product, $imageId)
		   ->constrainOnly(TRUE)
		   ->keepAspectRatio(TRUE)
		   ->keepTransparency(TRUE)
		   ->keepFrame(FALSE)
		   ->resize($width, $height);

		return $resizedImage->getUrl();
    }
}
