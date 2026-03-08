/**
 * AWA Motos — Compare Bar
 * Barra flutuante de comparação de produtos, sincronizada com a sidebar de comparação do Magento.
 * Exibe até 4 produtos na barra inferior e controla o link de comparação.
 */
define(['jquery', 'Magento_Catalog/js/product/list/toolbar'], function ($) {
    'use strict';

    var STORAGE_KEY  = 'mage-compare-' + window.MAGE_STORE_ID || 'mage-compare';
    var BAR_SELECTOR = '.awa-compare-bar';
    var MAX_COMPARE  = 4;

    /**
     * Read compare items from Magento's compareProducts cookie/localStorage
     * Fall back to reading DOM (compare sidebar)
     * @return {Array<{id, name}>}
     */
    function readCompareItems() {
        var items = [];

        // Try reading from compare sidebar widgets
        $('.block-compare .product-item .product-item-name a').each(function () {
            items.push({
                id: $(this).closest('[data-product-id]').data('productId') || '',
                name: $(this).text().trim()
            });
        });

        return items.slice(0, MAX_COMPARE);
    }

    /**
     * Render the compare bar
     * @param {Array} items
     */
    function renderBar(items) {
        var $bar = $(BAR_SELECTOR);

        if (!$bar.length) {
            return;
        }

        var $itemsContainer = $bar.find('.awa-compare-bar__items');
        var $compareBtn    = $bar.find('.awa-compare-bar__compare-btn');

        $itemsContainer.empty();

        if (!items.length) {
            $bar.removeClass('is-visible');
            return;
        }

        items.forEach(function (item) {
            $itemsContainer.append(
                '<div class="awa-compare-bar__item">'
                + '<span class="awa-compare-bar__item-name">' + $('<span>').text(item.name).html() + '</span>'
                + '<button class="awa-compare-bar__item-remove" aria-label="Remover ' + $('<span>').text(item.name).html() + ' da comparação" data-remove-id="' + item.id + '">✕</button>'
                + '</div>'
            );
        });

        // Disable compare if less than 2 items
        if (items.length < 2) {
            $compareBtn.attr('disabled', true).addClass('is-disabled');
        } else {
            $compareBtn.removeAttr('disabled').removeClass('is-disabled');
        }

        $bar.addClass('is-visible');
    }

    /**
     * Sync bar state with Magento compare widget
     */
    function syncBar() {
        var items = readCompareItems();
        renderBar(items);
    }

    return function (config, element) {
        var $bar = $(element);

        // Initial render
        syncBar();

        // Watch for Magento compare list changes (Ajax)
        $(document).on('ajax:updateCompare', function () {
            setTimeout(syncBar, 300);
        });

        // Observe mutations in compare sidebar
        var sidebarTarget = document.querySelector('.block-compare .block-content');

        if (sidebarTarget && window.MutationObserver) {
            var observer = new MutationObserver(function () {
                setTimeout(syncBar, 100);
            });

            observer.observe(sidebarTarget, { childList: true, subtree: true });
        }

        // Clear button in bar
        $bar.on('click', '.awa-compare-bar__clear-btn', function (e) {
            e.preventDefault();

            // Trigger Magento's native clear compare action
            var $clearLink = $('.block-compare .action.clear');

            if ($clearLink.length) {
                $clearLink[0].click();
            }

            $bar.removeClass('is-visible');
        });

        // Remove individual item
        $bar.on('click', '.awa-compare-bar__item-remove', function () {
            var productId = $(this).data('removeId');

            if (productId) {
                // Trigger native remove
                var $removeLink = $('.block-compare [data-post*="' + productId + '"]');

                if ($removeLink.length) {
                    $removeLink[0].click();
                } else {
                    // Fallback: re-read and re-render
                    setTimeout(syncBar, 300);
                }
            }
        });

        // Compare button
        $bar.on('click', '.awa-compare-bar__compare-btn', function (e) {
            var $btn = $(this);

            if ($btn.hasClass('is-disabled') || $btn.attr('disabled')) {
                e.preventDefault();
                return;
            }

            // Navigate to compare page
            window.location.href = config.compareUrl || '/catalog/product_compare/index/';
        });
    };
});
