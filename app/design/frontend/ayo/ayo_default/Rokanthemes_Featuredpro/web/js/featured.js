define([
    'jquery',
    'rokanthemes/owl'
], function ($) {
    'use strict';

    return function (config, element) {
        $(element).find('.owl').owlCarousel({
            lazyLoad: true,
            autoPlay: false,
            items: 4,
            itemsDesktop: [1199, 4],
            itemsDesktopSmall: [980, 3],
            itemsTablet: [768, 2],
            itemsMobile: [479, 1],
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
