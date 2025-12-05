define([
    'jquery',
    'rokanthemes/owl'
], function ($) {
    'use strict';

    return function (config, element) {
        $(element).find('.owl').owlCarousel({
            lazyLoad: true,
            autoPlay: false,
            items: 5,
            itemsDesktop: [1366, 4],
            itemsDesktopSmall: [1199, 3],
            itemsTablet: [991, 2],
            itemsMobile: [560, 1],
            slideSpeed: 500,
            paginationSpeed: 500,
            rewindSpeed: 500,
            navigation: true,
            stopOnHover: true,
            pagination: false,
            scrollPerPage: true
        });
    };
});
