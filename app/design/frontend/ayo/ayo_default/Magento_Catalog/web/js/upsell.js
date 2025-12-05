define([
    'jquery',
    'rokanthemes/owl'
], function ($) {
    'use strict';

    return function (config, element) {
        $(element).owlCarousel({
            lazyLoad: true,
            items: 6,
            itemsDesktop: [1366, 5],
            itemsDesktopSmall: [1199, 4],
            itemsTablet: [991, 3],
            itemsMobile: [680, 2],
            navigation: true,
            afterAction: function(el){
                this.$owlItems.removeClass('first-active');
                this.$owlItems.eq(this.currentItem).addClass('first-active');
            }
        });
    };
});
