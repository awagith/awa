<?php
/**
 * Block para renderizar oportunidades de churn em emails
 */
namespace GrupoAwamotos\RexisML\Block\Email;

use Magento\Framework\View\Element\Template;

class ChurnOpportunities extends Template
{
    protected $opportunities;

    /**
     * Set opportunities data
     *
     * @param array $opportunities
     * @return $this
     */
    public function setOpportunities($opportunities)
    {
        $this->opportunities = $opportunities;
        return $this;
    }

    /**
     * Get opportunities data
     *
     * @return array
     */
    public function getOpportunities()
    {
        return $this->opportunities ?: [];
    }
}
