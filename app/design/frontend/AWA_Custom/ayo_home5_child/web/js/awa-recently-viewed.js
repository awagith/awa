/**
 * AWA Motos — Recently Viewed Products
 * Persiste até 4 produtos visitados no localStorage e renderiza no bloco widget.
 *
 * Uso via x-magento-init:
 *   Track: {"*": {"AWA_Custom/js/recently-viewed": {"action":"track","product":{...}}}}
 *   Widget: {"#container": {"AWA_Custom/js/recently-viewed": {"action":"widget","excludeId":123}}}
 */
define(['jquery'], function ($) {
    'use strict';

    var STORAGE_KEY = 'awa_recently_viewed';
    var MAX_ITEMS   = 4;

    function getItems() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
        } catch (e) {
            return [];
        }
    }

    function saveItems(items) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
        } catch (e) { /* quota exceeded or private mode */ }
    }

    function trackProduct(product) {
        if (!product || !product.id) {
            return;
        }
        var items = getItems().filter(function (item) {
            return item.id !== product.id;
        });
        items.unshift(product);
        saveItems(items.slice(0, MAX_ITEMS));
    }

    function escapeHtml(str) {
        return $('<div>').text(String(str || '')).html();
    }

    function renderWidget($container, config) {
        var excludeId = config.excludeId || 0;
        var maxItems  = config.maxItems || MAX_ITEMS;
        var items = getItems().filter(function (item) {
            return item.id !== excludeId;
        }).slice(0, maxItems);

        if (!items.length) {
            $container.hide();
            return;
        }

        var $grid = $container.find('#awa-rv-grid');
        if (!$grid.length) {
            $grid = $container.find('.awa-recently-viewed__grid');
        }

        var html = '';
        items.forEach(function (item) {
            html += '<div class="awa-recently-viewed__item">'
                + '<a href="' + escapeHtml(item.url) + '" class="awa-recently-viewed__link">'
                + '<div class="awa-recently-viewed__image-wrap">'
                + (item.imageUrl
                    ? '<img src="' + escapeHtml(item.imageUrl) + '" alt="' + escapeHtml(item.name) + '" loading="lazy" class="awa-recently-viewed__image"/>'
                    : '<div class="awa-recently-viewed__image-placeholder"></div>')
                + '</div>'
                + '<div class="awa-recently-viewed__info">'
                + '<span class="awa-recently-viewed__name">' + escapeHtml(item.name) + '</span>'
                + (item.sku ? '<span class="awa-recently-viewed__sku">SKU: ' + escapeHtml(item.sku) + '</span>' : '')
                + (item.price ? '<span class="awa-recently-viewed__price">' + escapeHtml(item.price) + '</span>' : '')
                + '</div>'
                + '</a>'
                + '</div>';
        });

        $grid.html(html);
        $container.show();
    }

    /**
     * Magento component entry point.
     * Called by x-magento-init as: component(config, element)
     * For "*" selector, element is undefined.
     */
    return function (config, element) {
        var action = config.action || 'widget';

        if (action === 'track') {
            trackProduct(config.product || null);
            return;
        }

        if (action === 'widget' && element) {
            $(function () {
                renderWidget($(element), config);
            });
            return;
        }

        // Legacy API: initTracker / initWidget
        return {
            initTracker: function (cfg) {
                trackProduct((cfg || {}).product || null);
            },
            initWidget: function (cfg, el) {
                $(function () { renderWidget($(el), cfg || {}); });
            },
            getItems: getItems
        };
    };
});

