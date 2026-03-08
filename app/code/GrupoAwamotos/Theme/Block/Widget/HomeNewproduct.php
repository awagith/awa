<?php

declare(strict_types=1);

namespace GrupoAwamotos\Theme\Block\Widget;

use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Rokanthemes\Newproduct\Block\Widget\Newproduct;

class HomeNewproduct extends Newproduct
{
    private const DATA_MAP = [
        'title' => ['title'],
        'qty' => ['qty', 'limit'],
        'items_show' => ['items_show', 'row'],
        'next_back' => ['next_back', 'navigation'],
        'nav_ctrl' => ['nav_ctrl', 'pagination'],
        'itemsDefault' => ['itemsDefault', 'default', 'desktop'],
        'itemsDesktop' => ['itemsDesktop', 'desktop'],
        'itemsDesktopSmall' => ['itemsDesktopSmall', 'desktop_small'],
        'itemsTablet' => ['itemsTablet', 'tablet'],
        'itemsMobile' => ['itemsMobile', 'mobile'],
    ];

    private ?Collection $products = null;

    /**
     * @param string $att
     * @return mixed
     */
    public function getConfig($att)
    {
        foreach (self::DATA_MAP[$att] ?? [$att] as $key) {
            $value = $this->getData($key);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return parent::getConfig($att);
    }

    /**
     * @return Collection
     */
    public function getProducts()
    {
        if ($this->products === null) {
            /** @var Collection $collection */
            $collection = parent::getProducts();
            $this->products = $collection;
        }

        return $this->products;
    }

    public function hasRenderableProducts(): bool
    {
        return (int) $this->getProducts()->getSize() > 0;
    }

    public function getShelfRowCount(): int
    {
        return max(1, (int) $this->getConfig('items_show'));
    }

    public function getDefaultItems(): int
    {
        return max(1, (int) $this->getConfig('itemsDefault'));
    }

    public function getItemsDesktop(): int
    {
        return max(1, (int) $this->getConfig('itemsDesktop'));
    }

    public function getItemsDesktopSmall(): int
    {
        return max(1, (int) $this->getConfig('itemsDesktopSmall'));
    }

    public function getItemsTablet(): int
    {
        return max(1, (int) $this->getConfig('itemsTablet'));
    }

    public function getItemsMobile(): int
    {
        return max(1, (int) $this->getConfig('itemsMobile'));
    }

    public function isNavigationEnabled(): bool
    {
        return $this->toBool($this->getConfig('next_back'), true);
    }

    public function isPaginationEnabled(): bool
    {
        return $this->toBool($this->getConfig('nav_ctrl'), false);
    }

    public function getCarouselDomId(): string
    {
        $domId = (string) ($this->getData('dom_id') ?: 'awa-home-newproduct');

        return preg_replace('/[^a-zA-Z0-9_-]+/', '-', $domId) ?: 'awa-home-newproduct';
    }

    public function getCarouselVariant(): string
    {
        return trim((string) ($this->getData('carousel_variant') ?: 'awa-home-merch-shelf--launches'));
    }

    private function toBool(mixed $value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        return !in_array($value, [0, '0', false, 'false', 'no'], true);
    }
}
