define([
    'jquery',
    'rokanthemes/owl'
], function ($) {
    'use strict';

    return function (config, element) {
        var $container = $(element),
            $carousel = $container.find('.owl');

        if (!$carousel.length) {
            return;
        }

        $carousel.owlCarousel({
            lazyLoad: true,
            items: config.items || 1,
            margin: typeof config.margin !== 'undefined' ? config.margin : 30,
            itemsDesktop: config.itemsDesktop || [1199, 1],
            itemsDesktopSmall: config.itemsDesktopSmall || [991, 1],
            itemsTablet: config.itemsTablet || [768, 1],
            itemsMobile: config.itemsMobile || [480, 1],
            slideSpeed: config.slideSpeed || 500,
            paginationSpeed: config.paginationSpeed || 500,
            rewindSpeed: config.rewindSpeed || 500,
            addClassActive: true,
            navigation: !!config.navigation,
            stopOnHover: true,
            pagination: !!config.pagination,
            scrollPerPage: true
        });
    };
});