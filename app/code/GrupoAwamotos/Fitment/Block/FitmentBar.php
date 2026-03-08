<?php
declare(strict_types=1);

namespace GrupoAwamotos\Fitment\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\Registry;

/**
 * Block for PLP Fitment Bar
 * Provides endpoint URLs and initial active fitment state from session/URL params.
 */
class FitmentBar extends Template
{
    protected $_template = 'GrupoAwamotos_Fitment::fitment-bar.phtml';

    public function __construct(
        Template\Context $context,
        private readonly Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get URL of the current category (for "Clear" button link)
     */
    public function getCurrentCategoryUrl(): string
    {
        $category = $this->registry->registry('current_category');
        if ($category) {
            return (string) $category->getUrl();
        }
        return $this->getUrl('catalog/category/view');
    }

    /**
     * Get URL for brands AJAX endpoint
     */
    public function getBrandsUrl(): string
    {
        return $this->getUrl('fitment/ajax/brands');
    }

    /**
     * Get URL for models AJAX endpoint
     */
    public function getModelsUrl(): string
    {
        return $this->getUrl('fitment/ajax/models');
    }

    /**
     * Get URL for years AJAX endpoint
     */
    public function getYearsUrl(): string
    {
        return $this->getUrl('fitment/ajax/years');
    }

    /**
     * Get currently selected brand from URL param (persists bar state on results page)
     */
    public function getActiveMarca(): string
    {
        return (string) $this->getRequest()->getParam('marca_moto', '');
    }

    /**
     * Get currently selected model from URL param
     */
    public function getActiveModelo(): string
    {
        return (string) $this->getRequest()->getParam('modelo_moto', '');
    }

    /**
     * Get currently selected year from URL param
     */
    public function getActiveAno(): string
    {
        return (string) $this->getRequest()->getParam('ano_moto', '');
    }

    /**
     * Whether a fitment filter is currently active
     */
    public function hasFitmentActive(): bool
    {
        return $this->getActiveMarca() !== '';
    }

    /**
     * Get the fitment results URL (PLP/search filtered)
     */
    public function getFitmentResultsUrl(): string
    {
        return $this->getUrl('fitment/fitment/index');
    }
}
