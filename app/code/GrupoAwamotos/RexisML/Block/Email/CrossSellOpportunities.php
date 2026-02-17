<?php
namespace GrupoAwamotos\RexisML\Block\Email;

use Magento\Framework\View\Element\Template;

class CrossSellOpportunities extends Template
{
    protected $opportunities;

    public function setOpportunities($opportunities)
    {
        $this->opportunities = $opportunities;
        return $this;
    }

    public function getOpportunities()
    {
        return $this->opportunities ?: [];
    }
}
