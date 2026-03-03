<?php
declare(strict_types=1);

namespace GrupoAwamotos\Theme\Block\VerticalMenu;

use Magento\Framework\UrlInterface;

class SafeVerticalmenu extends \Rokanthemes\VerticalMenu\Block\Verticalmenu
{
    /**
     * Allow only safe tokens for CSS classes (single token).
     */
    private function sanitizeClassToken(?string $value): string
    {
        $value = (string)$value;
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9_-]+/', '', $value) ?? '';

        return trim($value);
    }

    /**
     * Allow only safe tokens for CSS class lists (space separated).
     */
    private function sanitizeClassList(?string $value): string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        $tokens = preg_split('/\s+/', $value) ?: [];
        $safe = [];

        foreach ($tokens as $token) {
            $token = $this->sanitizeClassToken($token);
            if ($token !== '') {
                $safe[] = $token;
            }
        }

        return implode(' ', array_values(array_unique($safe)));
    }

    /**
     * Allow only safe css sizes (prevents style injection).
     * Examples allowed: 500px, 80%, 20rem, 10em, 50vw
     */
    private function sanitizeCssSize(?string $value): ?string
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^\d{1,4}(px|%|rem|em|vw)$/', $value) === 1) {
            return $value;
        }

        return null;
    }

    private function clampGridColumns(int $value): int
    {
        return max(0, min(12, $value));
    }

    /**
     * @inheritDoc
     */
    public function getSubmenuItemsHtml($children, $level = 1, $max_level = 0, $column_width = 12, $menu_type = 'fullwidth', $columns = null)
    {
        $html = '';

        if (!$max_level || ($max_level && $max_level == 0) || ($max_level && $max_level > 0 && $max_level - 1 >= $level)) {
            $column_class = '';

            if ($level == 1 && $columns && ($menu_type == 'fullwidth' || $menu_type == 'staticwidth')) {
                $columnWidth = $this->clampGridColumns((int)$column_width);
                $columnsSafe = $this->sanitizeClassToken((string)$columns);

                $column_class = 'col-sm-' . $columnWidth . ' ';
                $column_class .= 'mega-columns columns' . $columnsSafe;
            }

            $html = '<ul class="subchildmenu ' . $this->escapeHtmlAttr(trim($column_class)) . '">';

            foreach ($children as $child) {
                $cat_model = $this->getCategoryModel($child->getId());

                $vc_menu_hide_item = $cat_model->getData('vc_menu_hide_item');

                if ($vc_menu_hide_item) {
                    continue;
                }

                $sub_children = $this->getActiveChildCategories($child);

                $vc_menu_cat_label = (string)$cat_model->getData('vc_menu_cat_label');
                $vc_menu_font_icon = (string)$cat_model->getData('vc_menu_font_icon');

                $item_class = 'level' . (int)$level . ' ';
                if (count($sub_children) > 0) {
                    $item_class .= 'parent ';
                }

                $a_class = '';
                if ($level == 1 && ($menu_type == 'fullwidth' || $menu_type == 'staticwidth')) {
                    $a_class = ' class="title-cat-mega-menu"';
                    $item_class .= 'parent-ul-cat-mega-menu';
                }

                /* --- Phase C: product count + category image as data attrs --- */
                $dataAttrs = '';
                if ($level === 1) {
                    try {
                        $productCount = (int) $cat_model->getProductCount();
                    } catch (\Exception $e) {
                        $productCount = 0;
                    }
                    $dataAttrs .= ' data-product-count="' . $productCount . '"';

                    $catImage = (string) $cat_model->getData('image');
                    if ($catImage !== '') {
                        try {
                            $catImageUrl = (string) $cat_model->getImageUrl('image');
                        } catch (\Exception $e) {
                            $store = $this->_storeManager->getStore();
                            $mediaUrl = '';
                            if (is_object($store) && method_exists($store, 'getBaseUrl')) {
                                $mediaUrl = (string) call_user_func(
                                    [$store, 'getBaseUrl'],
                                    \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                                );
                            }
                            $catImageUrl = $mediaUrl . 'catalog/category/' . ltrim($catImage, '/');
                        }
                        if ($catImageUrl !== '') {
                            $dataAttrs .= ' data-cat-image="' . $this->escapeUrl($catImageUrl) . '"';
                        }
                    }
                }

                $html .= '<li class="ui-menu-item ' . $this->escapeHtmlAttr(trim($item_class)) . '"' . $dataAttrs . '>';

                if (count($sub_children) > 0) {
                    $html .= '<div class="open-children-toggle"></div>';
                }

                $childUrl = $this->escapeUrl($this->_categoryHelper->getCategoryUrl($child));
                $childName = $this->escapeHtml($child->getName());
                $childNameAttr = $this->escapeHtmlAttr($child->getName());

                $html .= '<a' . $a_class . ' href="' . $childUrl . '">';

                $iconClass = $this->sanitizeClassList($vc_menu_font_icon);
                if ($iconClass !== '') {
                    $html .= '<em class="' . $this->escapeHtmlAttr('menu-thumb-icon ' . $iconClass) . '"></em>';
                }

                $html .= '<span>' . $childName;

                if ($vc_menu_cat_label !== '' && isset($this->_verticalmenuConfig['cat_labels'][$vc_menu_cat_label])) {
                    $labelKey = $this->sanitizeClassToken($vc_menu_cat_label);
                    $labelText = $this->escapeHtml((string)$this->_verticalmenuConfig['cat_labels'][$vc_menu_cat_label]);
                    $html .= '<span class="cat-label cat-label-' . $this->escapeHtmlAttr($labelKey) . '">' . $labelText . '</span>';
                }

                $html .= '</span></a>';

                if (count($sub_children) > 0) {
                    $html .= $this->getSubmenuItemsHtml($sub_children, $level + 1, $max_level, $column_width, $menu_type);
                }

                $html .= '</li>';
            }

            $html .= '</ul>';
        }

        return $html;
    }

    /**
     * @inheritDoc
     */
    public function getVerticalMenuHtml()
    {
        $html = '';

        $categories = $this->getStoreCategories(true, false, true);

        $this->_verticalmenuConfig = $this->_helper->getConfig('verticalmenu');
        $max_level = $this->_verticalmenuConfig['general']['max_level'] ?? 0;

        $html .= $this->getCustomBlockHtml('before');

        foreach ($categories as $category) {
            if (!$category->getIsActive()) {
                continue;
            }

            $cat_model = $this->getCategoryModel($category->getId());

            $vc_menu_hide_item = $cat_model->getData('vc_menu_hide_item');
            if ($vc_menu_hide_item) {
                continue;
            }

            $children = $this->getActiveChildCategories($category);

            $vc_menu_cat_label = (string)$cat_model->getData('vc_menu_cat_label');
            $vc_menu_font_icon = (string)$cat_model->getData('vc_menu_font_icon');
            $vc_menu_cat_columns = (int)$cat_model->getData('vc_menu_cat_columns');
            $vc_menu_float_type = (string)$cat_model->getData('vc_menu_float_type');

            if (!$vc_menu_cat_columns) {
                $vc_menu_cat_columns = 4;
            }

            $menu_type = (string)$cat_model->getData('vc_menu_type');
            if ($menu_type === '') {
                $menu_type = (string)($this->_verticalmenuConfig['general']['menu_type'] ?? 'fullwidth');
            }

            $custom_style = '';
            if ($menu_type === 'staticwidth') {
                $size = $this->sanitizeCssSize((string)$cat_model->getData('vc_menu_static_width'))
                    ?: '500px';
                $custom_style = ' style="width: ' . $this->escapeHtmlAttr($size) . ';"';
            }

            $item_class = 'level0 ' . $this->sanitizeClassToken($menu_type) . ' ';

            $menu_top_content = (string)$cat_model->getData('vc_menu_block_top_content');
            $menu_left_content = (string)$cat_model->getData('vc_menu_block_left_content');
            $menu_left_width = (int)$cat_model->getData('vc_menu_block_left_width');
            if ($menu_left_content === '' || !$menu_left_width) {
                $menu_left_width = 0;
            }

            $menu_right_content = (string)$cat_model->getData('vc_menu_block_right_content');
            $menu_right_width = (int)$cat_model->getData('vc_menu_block_right_width');
            if ($menu_right_content === '' || !$menu_right_width) {
                $menu_right_width = 0;
            }

            $menu_bottom_content = (string)$cat_model->getData('vc_menu_block_bottom_content');

            $floatType = '';
            if ($vc_menu_float_type !== '') {
                $floatType = 'fl-' . $this->sanitizeClassToken($vc_menu_float_type) . ' ';
            }

            $hasMegaContent = ($menu_type === 'fullwidth' || $menu_type === 'staticwidth')
                && ($menu_top_content !== '' || $menu_left_content !== '' || $menu_right_content !== '' || $menu_bottom_content !== '');

            if (count($children) > 0 || $hasMegaContent) {
                $item_class .= 'parent ';
            }

            $categoryUrl = $this->escapeUrl($this->_categoryHelper->getCategoryUrl($category));
            $categoryName = $this->escapeHtml($category->getName());
            $categoryNameAttr = $this->escapeHtmlAttr($category->getName());

            $html .= '<li class="ui-menu-item ' . $this->escapeHtmlAttr(trim($item_class . $floatType)) . '">';

            if (count($children) > 0) {
                $html .= '<div class="open-children-toggle"></div>';
            }

            $html .= '<a href="' . $categoryUrl . '" class="level-top">';

            $vc_menu_icon_img = $this->_helper->getVerticalIconimageUrl($cat_model);
            if ($vc_menu_icon_img) {
                $iconImgUrl = (string)$cat_model->getImageUrl('vc_menu_icon_img');
                if ($iconImgUrl !== '') {
                    $html .= '<img class="menu-thumb-icon" src="' . $this->escapeUrl($iconImgUrl) . '" alt="' . $categoryNameAttr . '"/>';
                }
            } else {
                $iconClass = $this->sanitizeClassList($vc_menu_font_icon);
                if ($iconClass !== '') {
                    $html .= '<em class="' . $this->escapeHtmlAttr('menu-thumb-icon ' . $iconClass) . '"></em>';
                }
            }

            $html .= '<span>' . $categoryName . '</span>';

            if ($vc_menu_cat_label !== '' && isset($this->_verticalmenuConfig['cat_labels'][$vc_menu_cat_label])) {
                $labelKey = $this->sanitizeClassToken($vc_menu_cat_label);
                $labelText = $this->escapeHtml((string)$this->_verticalmenuConfig['cat_labels'][$vc_menu_cat_label]);
                $html .= '<span class="cat-label cat-label-' . $this->escapeHtmlAttr($labelKey) . '">' . $labelText . '</span>';
            }

            $html .= '</a>';

            if (count($children) > 0 || $hasMegaContent) {
                $html .= '<div class="level0 submenu"' . $custom_style . '>';

                if (($menu_type === 'fullwidth' || $menu_type === 'staticwidth') && $menu_top_content !== '') {
                    $html .= '<div class="menu-top-block">' . $this->getBlockContent($menu_top_content) . '</div>';
                }

                if (count($children) > 0 || (($menu_type === 'fullwidth' || $menu_type === 'staticwidth') && ($menu_left_content !== '' || $menu_right_content !== ''))) {
                    $html .= '<div class="row">';

                    $menu_left_width = $this->clampGridColumns($menu_left_width);
                    $menu_right_width = $this->clampGridColumns($menu_right_width);
                    $centerWidth = $this->clampGridColumns(12 - $menu_left_width - $menu_right_width);

                    if (($menu_type === 'fullwidth' || $menu_type === 'staticwidth') && $menu_left_content !== '' && $menu_left_width > 0) {
                        $html .= '<div class="menu-left-block col-sm-' . $menu_left_width . '">' . $this->getBlockContent($menu_left_content) . '</div>';
                    }

                    $html .= $this->getSubmenuItemsHtml($children, 1, (int)$max_level, $centerWidth, $menu_type, $vc_menu_cat_columns);

                    if (($menu_type === 'fullwidth' || $menu_type === 'staticwidth') && $menu_right_content !== '' && $menu_right_width > 0) {
                        $html .= '<div class="menu-right-block col-sm-' . $menu_right_width . '">' . $this->getBlockContent($menu_right_content) . '</div>';
                    }

                    $html .= '</div>';
                }

                if (($menu_type === 'fullwidth' || $menu_type === 'staticwidth') && $menu_bottom_content !== '') {
                    $html .= '<div class="menu-bottom-block">' . $this->getBlockContent($menu_bottom_content) . '</div>';
                } elseif (($menu_type === 'fullwidth' || $menu_type === 'staticwidth') && count($children) > 0) {
                    $catImage = (string)$cat_model->getData('image');
                    if ($catImage !== '') {
                        try {
                            $catImageUrl = (string)$cat_model->getImageUrl('image');
                        } catch (\Exception $e) {
                            $store = $this->_storeManager->getStore();
                            $mediaUrl = '';
                            if (is_object($store) && method_exists($store, 'getBaseUrl')) {
                                $mediaUrl = (string)call_user_func([$store, 'getBaseUrl'], UrlInterface::URL_TYPE_MEDIA);
                            }

                            $catImageUrl = $mediaUrl . 'catalog/category/' . ltrim($catImage, '/');
                        }

                        $html .= '<div class="menu-bottom-block menu-bottom-block-auto">';
                        $html .= '<a href="' . $categoryUrl . '" class="menu-category-banner">';
                        $html .= '<img loading="lazy" src="' . $this->escapeUrl($catImageUrl) . '" alt="' . $categoryNameAttr . '" />';
                        $html .= '</a></div>';
                    }
                }

                $html .= '</div>';
            }

            $html .= '</li>';
        }

        $html .= $this->getCustomBlockHtml('after');

        return $html;
    }
}
