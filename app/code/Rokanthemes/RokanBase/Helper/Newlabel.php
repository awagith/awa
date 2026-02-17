<?php
/**
 * @Author: Harry - Hai Le
 * @Email: hailt1911@gmail.com
 * @File Name: Data.php
 * @File Path: 
 * @Date:   2015-04-07 19:26:42
 * @Last Modified by:   zero
 * @Last Modified time: 2015-07-28 08:35:17
 */
namespace Rokanthemes\RokanBase\Helper;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class Newlabel extends \Magento\Framework\Url\Helper\Data
{

    /**
     * @var TimezoneInterface
     */
    protected $localeDate;

    public function __construct(
        TimezoneInterface $localeDate
    ) {
        $this->localeDate = $localeDate;
    }

    public function isProductNew($product)
    {
        $newsFromDate = $product->getNewsFromDate();
        $newsToDate = $product->getNewsToDate();
        if (!$newsFromDate && !$newsToDate) {
            return false;
        }

        return $this->localeDate->isScopeDateInInterval(
            $product->getStore(),
            $newsFromDate,
            $newsToDate
        );
    }
}