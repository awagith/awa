define(['jquery'], function ($) {
    'use strict';
    function applyLazy() {
        try {
            $('img.product-image-photo, .product-item-photo img, .product.media img').each(function () {
                var $img = $(this);
                if (!$img.attr('loading')) {
                    $img.attr('loading', 'lazy');
                }
                if (!$img.attr('decoding')) {
                    $img.attr('decoding', 'async');
                }
            });
        } catch (e) {
            // silencioso
        }
    }
    $(document).ready(applyLazy);
    $(document).on('ajaxComplete contentUpdated', applyLazy);
});
