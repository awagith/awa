<?php
namespace Rokanthemes\Brand\Model;

class Sitemap extends \Magento\Sitemap\Model\Sitemap
{
	
	protected function _initSitemapItems()
    {
		
        $helper = $this->_sitemapData;
        $storeId = $this->getStoreId();
		$newLine = [];
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$brand_collection = $objectManager->create('Rokanthemes\Brand\Model\Brand');
		$brand_collections = $brand_collection->getCollection()->addFieldToFilter('status',1)
        ->setOrder('position','ASC');
		$_brandHelper = $objectManager->create('\Rokanthemes\Brand\Helper\Data');
		foreach ($brand_collections as $key=>$value) {
			$route = $_brandHelper->getConfig('general_settings/route');
			$url_prefix = $_brandHelper->getConfig('general_settings/url_prefix');
			$urlPrefix = '';
			if($url_prefix){
				$urlPrefix = $url_prefix.'/';
			}
			$url_suffix = $_brandHelper->getConfig('general_settings/url_suffix');
			$object = new \Magento\Framework\DataObject();
			$object->setId($value->getId());
			$object->setUrl($urlPrefix.$value->getUrlKey().$url_suffix);
			$object->setUpdatedAt($value->getUpdateTime());

			$newLine[$value->getUrlKey()] = $object;
		}
        $this->_sitemapItems[] = new \Magento\Framework\DataObject(
            [
				'changefreq' => $helper->getCategoryChangefreq($storeId),
                'priority' => $helper->getCategoryPriority($storeId),
                'collection' => $newLine
            ]
        );
		parent::_initSitemapItems();
    }
	
	
	public function generateXml()
    {
        $this->_initSitemapItems();
		$storeId = $this->getStoreId();
		$helper = $this->_sitemapData;
        /** @var $item SitemapItemInterface */
        foreach ($this->_sitemapItems as $item) {
			if($item->getChangeFrequency()){
				$xml = $this->_getSitemapRow(
					$item->getUrl(),
					$item->getUpdatedAt(),
					$item->getChangeFrequency(),
					$item->getPriority(),
					$item->getImages()
				);
			}else{
				$xml = $this->_getSitemapRow(
					$item->getUrl(),
					$item->getUpdatedAt(),
					$helper->getCategoryChangefreq($storeId),
					$item->getPriority(),
					$item->getImages()
				);
			}
            

            if ($this->_isSplitRequired($xml) && $this->_sitemapIncrement > 0) {
                $this->_finalizeSitemap();
            }

            if (!$this->_fileSize) {
                $this->_createSitemap();
            }

            $this->_writeSitemapRow($xml);
            // Increase counters
            $this->_lineCount++;
            $this->_fileSize += strlen($xml);
        }

        $this->_finalizeSitemap();

        if ($this->_sitemapIncrement == 1) {
            // In case when only one increment file was created use it as default sitemap
            $path = rtrim(
                $this->getSitemapPath(),
                '/'
            ) . '/' . $this->_getCurrentSitemapFilename(
                $this->_sitemapIncrement
            );
            $destination = rtrim($this->getSitemapPath(), '/') . '/' . $this->getSitemapFilename();

            $this->_directory->renameFile($path, $destination);
        } else {
            // Otherwise create index file with list of generated sitemaps
            $this->_createSitemapIndex();
        }

        $this->setSitemapTime($this->_dateModel->gmtDate('Y-m-d H:i:s'));
        $this->save();

        return $this;
    }
}