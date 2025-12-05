/**
 * Sticky Add to Cart Mobile - JavaScript Widget
 */
define([
    'jquery',
    'jquery-ui-modules/widget'
], function ($) {
    'use strict';

    $.widget('mage.stickyAddToCart', {
        options: {
            offsetTop: 300,
            mainButtonSelector: 'button.action.tocart'
        },

        _create: function () {
            this._bind();
        },

        _bind: function () {
            var self = this;
            var $window = $(window);
            var $stickyBar = this.element;
            var $mainButton = $(this.options.mainButtonSelector);
            var productFormTop = $mainButton.length ? $mainButton.offset().top : 0;

            // Show/hide sticky bar on scroll
            $window.on('scroll', function () {
                var scrollTop = $window.scrollTop();
                
                if (scrollTop > productFormTop + self.options.offsetTop) {
                    $stickyBar.addClass('active');
                } else {
                    $stickyBar.removeClass('active');
                }
            });

            // Click handler - scroll to main form
            $stickyBar.find('[data-role="sticky-add-to-cart"]').on('click', function (e) {
                e.preventDefault();
                
                // Scroll to main add to cart button
                if ($mainButton.length) {
                    $('html, body').animate({
                        scrollTop: $mainButton.offset().top - 100
                    }, 400, function () {
                        // Trigger main button click after scroll
                        $mainButton.trigger('click');
                    });
                }
            });
        }
    });

    return $.mage.stickyAddToCart;
});
