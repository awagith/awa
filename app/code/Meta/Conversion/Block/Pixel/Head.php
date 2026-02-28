<?php

declare(strict_types=1);

namespace Meta\Conversion\Block\Pixel;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Category;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Meta\BusinessExtension\Api\SystemConfigInterface;

/**
 * Block for Meta Pixel head script
 */
class Head extends Template
{
    public function __construct(
        Context $context,
        private readonly SystemConfigInterface $config,
        private readonly Registry $registry,
        private readonly CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Check if the pixel is active
     */
    public function isActive(): bool
    {
        $storeId = $this->getCurrentStoreId();

        return $this->config->isActive($storeId) && $this->getPixelId() !== null;
    }

    /**
     * Get the Pixel ID
     */
    public function getPixelId(): ?string
    {
        return $this->config->getPixelId($this->getCurrentStoreId());
    }

    /**
     * Get current product if on a product page
     */
    public function getCurrentProduct(): ?ProductInterface
    {
        return $this->registry->registry('current_product');
    }

    /**
     * Get current category if on a category page
     */
    public function getCurrentCategory(): ?Category
    {
        return $this->registry->registry('current_category');
    }

    /**
     * Get cart content IDs for tracking
     *
     * @return string[] SKU list
     */
    public function getCartContentIds(): array
    {
        try {
            $quote = $this->checkoutSession->getQuote();
            $ids = [];
            foreach ($quote->getAllVisibleItems() as $item) {
                $sku = trim((string) $item->getSku());
                if ($sku !== '') {
                    $ids[] = $sku;
                }
            }
            return array_values(array_unique($ids));
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Get cart total value
     */
    public function getCartValue(): float
    {
        try {
            return (float) $this->checkoutSession->getQuote()->getGrandTotal();
        } catch (\Throwable) {
            return 0.0;
        }
    }

    public function getCurrencyCode(): string
    {
        try {
            $store = $this->_storeManager->getStore($this->getCurrentStoreId());

            return (string) ($store->getCurrentCurrencyCode() ?: $store->getBaseCurrencyCode() ?: 'BRL');
        } catch (\Throwable) {
            return 'BRL';
        }
    }

    private function getCurrentStoreId(): ?int
    {
        try {
            $product = $this->getCurrentProduct();
            if ($product !== null && $product->getStoreId() !== null) {
                return (int) $product->getStoreId();
            }

            $category = $this->getCurrentCategory();
            if ($category !== null && $category->getStoreId() !== null) {
                return (int) $category->getStoreId();
            }

            return (int) $this->_storeManager->getStore()->getId();
        } catch (\Throwable) {
            return null;
        }
    }
}
