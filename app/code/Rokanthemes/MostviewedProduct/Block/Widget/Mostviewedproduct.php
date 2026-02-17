<?php

namespace Rokanthemes\MostviewedProduct\Block\Widget;

use Rokanthemes\MostviewedProduct\Block\Mostviewed;
use Magento\Widget\Block\BlockInterface;

class Mostviewedproduct extends Mostviewed implements BlockInterface
{
    /**
     * @var string
     */
    protected $_template = 'widget/mostviewedproduct_list.phtml';
}
