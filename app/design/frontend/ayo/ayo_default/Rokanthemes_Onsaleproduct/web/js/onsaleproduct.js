define([
    'jquery',
    'rokanthemes/owl'
], function ($) {
    'use strict';

    return function (config, element) {
        $(element).find('.owl_onsale').owlCarousel({
            lazyLoad: true,
            autoPlay: false,
            items: 4,
            itemsDesktop: [1366, 3],
            itemsDesktopSmall: [1199, 2],
            itemsTablet: [768, 2],
            itemsMobile: [480, 1],
            slideSpeed: 500,
            paginationSpeed: 500,
            rewindSpeed: 500,
            navigation: true,
            stopOnHover: true,
            pagination: false,
            scrollPerPage: true,
            afterAction: function (el) {
                this.$owlItems.removeClass('first-active');
                this.$owlItems.eq(this.currentItem).addClass('first-active');
            }
        });
    };
});
