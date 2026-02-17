<?php
//ducdevphp@gmail.com
namespace Rokanthemes\Testimonials\Helper;

class Image extends \Magento\Framework\App\Helper\AbstractHelper
{
	
		protected $_filesystem ;
		protected $_imageFactory;
		protected $_storeManager;
	public function __construct(            
        \Magento\Framework\Filesystem $filesystem,         
        \Magento\Framework\Image\AdapterFactory $imageFactory,
		\Magento\Store\Model\StoreManagerInterface $storeManagerInterface
        ) {         
        $this->_filesystem = $filesystem;               
        $this->_imageFactory = $imageFactory;
			$this->_storeManager = $storeManagerInterface;
        }

    public function resize($image, $width = null, $height = null, $no = false)
    {
        $absolutePath = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath('').$image;
		if(file_exists($absolutePath)){
			if($no){
				$resizedURL = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).$image;
				return $resizedURL;
			}
        $imageResized = $this->_filesystem->getDirectoryRead(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath('resized/'.$width.'/').$image;         
        $imageResize = $this->_imageFactory->create();         
        $imageResize->open($absolutePath);
        $imageResize->constrainOnly(TRUE);         
        $imageResize->keepTransparency(TRUE);         
        $imageResize->keepFrame(FALSE);         
        $imageResize->keepAspectRatio(FALSE);         
        $imageResize->resize($width,$height);               
        $destination = $imageResized ;        
        $imageResize->save($destination);         
        $resizedURL = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'resized/'.$width.'/'.$image;
        return $resizedURL;
		}
		return false;
  } 
}
