'use strict';

define([
    'jquery',
    'rokanthemes/owl',
    'rokanthemes/timecircles'
], function ($) {
    function getCarouselItems(instance) {
        if (instance.$owlItems && instance.$owlItems.length) {
            return instance.$owlItems;
        }

        if (instance.$items && instance.$items.length) {
            return instance.$items;
        }

        if (instance.$stage && typeof instance.$stage.children === 'function') {
            return instance.$stage.children();
        }

        return $();
    }

    function addFirstActiveClass(instance) {
        var $items = getCarouselItems(instance);
        var index = typeof instance.currentItem === 'number' ? instance.currentItem : instance._current;

        if ($items.length) {
            $items.removeClass('first-active');
        }

        if (typeof index === 'number' && $items.length) {
            $items.eq(index).addClass('first-active');
        }
    }

    function initCarousel($carousel, options) {
        if (!$carousel.length || typeof $carousel.owlCarousel !== 'function') {
            return;
        }

        if ($carousel.data('owl-initialized')) {
            return;
        }

        var defaults = {
            lazyLoad: true,
            navigation: false,
            pagination: true
        };

        var config = $.extend(true, {}, defaults, options || {});
        var userAfterAction = config.afterAction;

        config.navigation = !!config.navigation;
        config.pagination = !!config.pagination;

        config.afterAction = function (el) {
            if (typeof userAfterAction === 'function') {
                userAfterAction.call(this, el);
            }

            addFirstActiveClass(this);
        };

        $carousel.owlCarousel(config);
        $carousel.data('owl-initialized', true);
    }

    function initCountdown($elements, timerConfig) {
        if (!$elements.length || typeof $.fn.TimeCircles !== 'function') {
            return;
        }

        var defaults = {
            fg_width: 0.01,
            bg_width: 1.2,
            text_size: 0.07,
            circle_bg_color: '#ffffff',
            time: {}
        };

        var config = $.extend(true, {}, defaults, timerConfig || {});

        $elements.each(function () {
            var $target = $(this);
            var api = $target.data('TimeCircles');

            if (api && typeof api.destroy === 'function') {
                api.destroy();
            }

            $target.TimeCircles(config);
        });
    }

    return function initSuperDeals(config, element) {
        var $context = $(element);
        var carouselSelector = config.carouselSelector || '.hot-deal-slide';
        var countdownSelector = config.countdownSelector || '.super-deal-countdown';
        var $carousel = $context.find(carouselSelector);
        var $countdowns = $context.find(countdownSelector);

        initCarousel($carousel, config.owl);
        initCountdown($countdowns, config.timer);
    };
});
