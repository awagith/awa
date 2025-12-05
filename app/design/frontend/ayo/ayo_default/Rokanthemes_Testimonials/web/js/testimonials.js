define([
    'jquery',
    'rokanthemes/owl'
], function ($) {
    'use strict';

    return function (config, element) {
        $(element).find('.wrap-item').owlCarousel({
            lazyLoad: true,
            items: 1,
            itemsCustom: [
                [0, 1],
                [480, 1],
                [768, 1],
                [992, 1],
                [1200, 1]
            ],
            pagination: false,
            navigation: false,
            navigationText: ['<i class="fa fa-angle-left"></i>', '<i class="fa fa-angle-right"></i>']
        });
    };
});
