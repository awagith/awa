/**
 * AWA Motos — Recently Viewed Products
 * Persiste até 4 produtos visitados no localStorage e renderiza no bloco widget.
 */
define(['jquery', 'mage/url', 'Magento_Customer/js/customer-data'], function ($, urlBuilder, customerData) {
    'use strict';

    var STORAGE_KEY = 'awa_recently_viewed';
    var MAX_ITEMS = 4;

    /**
     * Retrieve list from localStorage
     * @return {Array}
     */
    function getItems() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
        } catch (e) {
            return [];
        }
    }

    /**
     * Save list to localStorage
     * @param {Array} items
     */
    function saveItems(items) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
        } catch (e) {
            // Quota exceeded or private mode — fail silently
        }
    }

    /**
     * Track a product page view
     * @param {Object} product {id, name, url, imageUrl, price}
     */
    function trackProduct(product) {
        if (!product || !product.id) {
            return;
        }

        var items = getItems();

        // Remove if already exists (move to front)
        items = items.filter(function (item) {
            return item.id !== product.id;
        });

        // Prepend current product
        items.unshift(product);

        // Keep only MAX_ITEMS
        items = items.slice(0, MAX_ITEMS);

        saveItems(items);
    }

    /**
     * Render recently viewed block
     * @param {jQuery} $container
     */
    function renderBlock($container) {
        var items = getItems();

        if (!items.length) {
            $container.hide();
            return;
        }

        var html = '<div class="awa-recently-viewed">'
            + '<h3 class="awa-recently-viewed__title">Vistos recentemente</h3>'
            + '<div class="awa-recently-viewed__grid">';

        items.forEach(function (item) {
            html += '<div class="awa-recently-viewed__item">'
                + '<a href="' + $('<div>').text(item.url).html() + '" class="awa-recently-viewed__link">'
                + '<div class="awa-recently-viewed__image-wrap">'
                + '<img src="' + $('<div>').text(item.imageUrl || '').html() + '" alt="' + $('<div>').text(item.name).html() + '" loading="lazy" class="awa-recently-viewed__image"/>'
                + '</div>'
                + '<div class="awa-recently-viewed__info">'
                + '<span class="awa-recently-viewed__name">' + $('<div>').text(item.name).html() + '</span>'
                + (item.price ? '<span class="awa-recently-viewed__price">' + $('<div>').text(item.price).html() + '</span>' : '')
                + '</div>'
                + '</a>'
                + '</div>';
        });

        html += '</div></div>';

        $container.html(html).show();
    }

    return {
        /**
         * Initialize on PDP — call with product data
         * @param {Object} config
         */
        initTracker: function (config) {
            if (config && config.product) {
                trackProduct(config.product);
            }
        },

        /**
         * Initialize the widget block renderer
         * @param {Object} config
         * @param {HTMLElement} element
         */
        initWidget: function (config, element) {
            var $container = $(element);
            renderBlock($container);
        },

        /**
         * Expose getItems for external use
         */
        getItems: getItems
    };
});
