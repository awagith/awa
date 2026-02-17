<?php

namespace Rokanthemes\BestsellerProduct\Block\Widget;

use Rokanthemes\BestsellerProduct\Block\Bestseller;
use Magento\Widget\Block\BlockInterface;

class Bestsellerproduct extends Bestseller implements BlockInterface
{
    /**
     * @var string
     */
    protected $_template = 'widget/bestsellerproduct_list.phtml';
}
