/* global define */

define([
    'jquery',
    'rokanthemes/owl'
], function ($) {
    'use strict';

    var INT_KEYS = ['items', 'slideSpeed', 'paginationSpeed', 'rewindSpeed'];
    var BOOL_KEYS = ['lazyLoad', 'navigation', 'pagination', 'autoPlay'];

    function resolveBoolean(value, fallback) {
        if (value === undefined || value === null || value === '') {
            return fallback;
        }
        if (typeof value === 'string') {
            return !(value === 'false' || value === '0');
        }
        return !!value;
    }

    function normalizeOptions(rawOptions) {
        var options = rawOptions || {};
        var i;
        var key;
        var v;

        for (i = 0; i < INT_KEYS.length; i++) {
            key = INT_KEYS[i];
            if (options[key] === undefined) {
                continue;
            }
            v = parseInt(options[key], 10);
            options[key] = v || options[key];
        }

        for (i = 0; i < BOOL_KEYS.length; i++) {
            key = BOOL_KEYS[i];
            if (options[key] === undefined) {
                continue;
            }
            options[key] = resolveBoolean(options[key], options[key]);
        }

        return options;
    }

    function initWhenReady($el, options, attemptsLeft) {
        var remaining = attemptsLeft || 6;

        if (!$el || !$el.length) {
            return;
        }

        if (typeof $el.owlCarousel === 'function') {
            $el.owlCarousel(options);
            return;
        }

        if (remaining <= 0) {
            $el.removeData('awaOwlElementInit');
            return;
        }

        setTimeout(function () {
            initWhenReady($el, options, remaining - 1);
        }, 120);
    }

    /**
     * Magento initializer for Owl Carousel v1.
     *
     * Usage (x-magento-init):
     * {
     *   ".selector": {
     *     "js/rokanthemes-owl-element-init": { ...owlV1Options }
     *   }
     * }
     */
    return function (config, element) {
        var $el = $(element);
        var options = normalizeOptions(config ? $.extend({}, config) : {});

        if (!$el.length || typeof $el.owlCarousel !== 'function') {
            return;
        }

        if ($el.data('owlCarousel') || $el.hasClass('owl-loaded') || $el.data('awaOwlElementInit')) {
            return;
        }

        $el.data('awaOwlElementInit', 1);
        initWhenReady($el, options, 6);
    };
});
