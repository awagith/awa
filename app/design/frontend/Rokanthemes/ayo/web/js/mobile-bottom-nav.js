/**
 * Mobile Bottom Navigation - JavaScript Widget
 */
define([
    'jquery',
    'jquery-ui-modules/widget'
], function ($) {
    'use strict';

    $.widget('mage.mobileBottomNav', {
        _create: function () {
            this._bind();
        },

        _bind: function () {
            var $nav = $('[data-role="mobile-bottom-navigation"]');
            var $searchToggle = this.element;

            // Handle search toggle
            $searchToggle.on('click', function (e) {
                e.preventDefault();
                
                // Toggle search form in header
                var $searchForm = $('.block-search');
                if ($searchForm.length) {
                    $searchForm.find('input[name="q"]').focus();
                    
                    // Scroll to top
                    $('html, body').animate({
                        scrollTop: 0
                    }, 300);
                }
            });

            // Mark active link based on current page
            this._markActivePage($nav);
        },

        _markActivePage: function ($nav) {
            var currentPath = window.location.pathname;
            
            $nav.find('.bottom-nav-item').each(function () {
                var $link = $(this);
                var href = $link.attr('href');
                
                if (href && currentPath.indexOf(href.replace(window.location.origin, '')) === 0) {
                    $link.addClass('active');
                }
            });

            // Special case for homepage
            if (currentPath === '/' || currentPath === '/index.php') {
                $nav.find('.bottom-nav-item').first().addClass('active');
            }
        }
    });

    return $.mage.mobileBottomNav;
});
