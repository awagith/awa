'use strict';

define([
    'jquery',
    'rokanthemes/owl'
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
        if (typeof $carousel.owlCarousel !== 'function') {
            return;
        }

        if ($carousel.data('owl-initialized')) {
            return;
        }

        var defaults = {
            lazyLoad: true,
            navigation: true,
            pagination: false
        };

        var config = $.extend(true, {}, defaults, options || {});

        config.navigation = !!config.navigation;
        config.pagination = !!config.pagination;

        var userAfterAction = config.afterAction;

        config.afterAction = function (el) {
            if (typeof userAfterAction === 'function') {
                userAfterAction.call(this, el);
            }

            addFirstActiveClass(this);
        };

        $carousel.owlCarousel(config);
        $carousel.data('owl-initialized', true);
    }

    function resolvePanelSelector(raw) {
        if (!raw) {
            return null;
        }

        return raw.charAt(0) === '#' ? raw : '#' + raw;
    }

    function activateTab(config, $context, $tab) {
        if (!$tab || !$tab.length) {
            return;
        }

        var tabAttribute = config.tabAttribute || 'rel';
        var $tabs = $context.find(config.tabsSelector).find('li');
        var $contentPanels = $context.find(config.contentSelector);
        var targetId = $tab.attr(tabAttribute);
        var targetSelector = resolvePanelSelector(targetId);
        var $target;

        $tabs.removeClass('active');
        $tab.addClass('active');

        if ($contentPanels.length) {
            $contentPanels.hide().removeClass('animate1');

            if (targetSelector) {
                $target = $context.find(targetSelector);
            }

            if (!$target || !$target.length) {
                $target = $contentPanels.first();
            }

            if ($target && $target.length) {
                $target.show().addClass('animate1');
            }
        }
    }

    return function initCategoryTab(config, element) {
        var $context = $(element);
        var tabsSelector = config.tabsSelector || 'ul.tabs';
        var contentSelector = config.contentSelector || '.tab_content';
        var carouselSelector = config.carouselSelector || '.owl-carousel';
        var $tabsContainer = $context.find(tabsSelector);

        if (!$tabsContainer.length) {
            return;
        }

        var $tabItems = $tabsContainer.find('li');
        var tabConfig;

        if (!$tabItems.length) {
            return;
        }

        var $carousels = $context.find(carouselSelector);

        if ($carousels.length) {
            $carousels.each(function () {
                initCarousel($(this), config.owl);
            });
        }

        var $initialTab = $tabItems.filter('.active').first();

        tabConfig = {
            tabsSelector: tabsSelector,
            contentSelector: contentSelector,
            tabAttribute: config.tabAttribute
        };

        if (!$initialTab.length) {
            $initialTab = $tabItems.first();
        }

        activateTab(tabConfig, $context, $initialTab);

        $tabItems.off('.categoryTab').on('click.categoryTab', function (event) {
            event.preventDefault();
            activateTab(tabConfig, $context, $(this));
        });

        if (config.refreshOnInit && $carousels.length) {
            $carousels.trigger('refresh.owl.carousel');
        }
    };
});
