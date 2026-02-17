define([
    'jquery',
    'rokanthemes/verticalmenu'
], function ($) {
    'use strict';

    return function (config, element) {
        var $nav = $(element);
        var $toggleMenu = $nav.find('.togge-menu');
        var $title = $nav.find('.title-category-dropdown');
        var $expandLink = $nav.find('.expand-category-link');
        var $items = $nav.find('.ui-menu-item.level0');
        var overlaySelector = (config && config.overlaySelector) || '.shadow_bkg_show';
        var defaultLimit = parseInt(config && config.limitShow, 10) || 0;
        var limitItemShow = parseInt($toggleMenu.attr('data-limit-show'), 10) || defaultLimit;
        var $rootVerticalMenus;

        if (!$nav.length || $nav.data('awaVerticalMenuInit')) {
            return;
        }

        $nav.data('awaVerticalMenuInit', 1);

        $rootVerticalMenus = $nav.filter('.verticalmenu').add($nav.find('.verticalmenu'));
        if (typeof $rootVerticalMenus.VerticalMenu === 'function') {
            $rootVerticalMenus.VerticalMenu();
        }

        $toggleMenu.hide();

        function closeMenu() {
            $toggleMenu.removeClass('menu-open').stop(true, true).fadeOut(200);
            $title.removeClass('active');
            $('body').removeClass('background_shadow_show');
        }

        $title.off('click.awaVerticalMenu').on('click.awaVerticalMenu', function (event) {
            var isOpening;

            event.preventDefault();
            isOpening = !$title.hasClass('active');

            if (isOpening) {
                $toggleMenu.addClass('menu-open').stop(true, true).fadeIn(250);
            } else {
                closeMenu();
            }

            $title.toggleClass('active', isOpening);
            $('body').toggleClass('background_shadow_show', isOpening);
        });

        if (limitItemShow > 0) {
            limitItemShow += 1;
        }

        if (limitItemShow > 0 && $items.length > limitItemShow) {
            $items.each(function (index) {
                if (index >= (limitItemShow - 1)) {
                    $(this).addClass('orther-link').hide();
                }
            });

            $expandLink.show().off('click.awaVerticalMenu').on('click.awaVerticalMenu', function (event) {
                event.preventDefault();
                $(this).toggleClass('expanding');
                $nav.find('.ui-menu-item.level0.orther-link').fadeToggle(200);
            });
        } else {
            $expandLink.hide();
        }

        $(overlaySelector).off('click.awaVerticalMenu').on('click.awaVerticalMenu', function () {
            closeMenu();
        });
    };
});
