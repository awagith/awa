<?php

namespace Rokanthemes\Onsaleproduct\Block\Widget;

use Rokanthemes\Onsaleproduct\Block\Onsaleproduct as OnsaleBlock;
use Magento\Widget\Block\BlockInterface;

class Onsaleproduct extends OnsaleBlock implements BlockInterface
{
    /**
     * @var string
     */
    protected $_template = 'widget/onsaleproduct_list.phtml';
}
