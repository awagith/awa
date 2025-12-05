define([
    'jquery',
    'rokanthemes/verticalmenu'
], function ($) {
    'use strict';

    var defaults = {
        limitItemShow: 11,
        autoShowHome: false,
        initiallyOpen: false,
        responsiveBreakpoint: null,
        toggleBodyClass: 'background_shadow_show',
        selectors: {
            toggleTitle: '.title-category-dropdown',
            toggleList: '.togge-menu',
            levelItems: '.ui-menu-item.level0',
            expandLink: '.expand-category-link',
            bodyOverlay: '.shadow_bkg_show'
        }
    };

    function toInteger(value, fallback) {
        var parsed = parseInt(value, 10);

        if (isNaN(parsed)) {
            return fallback;
        }

        return parsed;
    }

    return function (config, element) {
        var options = $.extend(true, {}, defaults, config || {});
        var $container = $(element);
        var $body = $('body');
        var selectors = options.selectors || {};
        var $toggleMenu;
        var $toggleTitle;
        var $expandLink;
        var $levelItems;
        var limit;
        var isHomepage = $body.hasClass('cms-index-index');
        var shouldAutoShowHome = !!options.autoShowHome && isHomepage;
        var responsiveBreakpoint = toInteger(options.responsiveBreakpoint, null);

        if (!$container.length) {
            return;
        }

        function initializeWidget() {
            if (typeof $container.VerticalMenu === 'function') {
                $container.VerticalMenu();
            }
        }

        initializeWidget();

        $toggleTitle = $container.find(selectors.toggleTitle);
        $toggleMenu = $container.find(selectors.toggleList);
        $expandLink = $container.find(selectors.expandLink);
        $levelItems = $container.find(selectors.levelItems);
        limit = toInteger(options.limitItemShow, defaults.limitItemShow);

        function applyBodyClass(isActive) {
            if (!options.toggleBodyClass) {
                return;
            }

            $body.toggleClass(options.toggleBodyClass, !!isActive);
        }

        function showMenu() {
            $toggleMenu.stop(true, true).slideDown('slow');
            $toggleTitle.addClass('active');
            applyBodyClass(true);
        }

        function hideMenu() {
            $toggleMenu.stop(true, true).slideUp('slow');
            $toggleTitle.removeClass('active');
            applyBodyClass(false);
        }

        function responsiveMenu() {
            if (!responsiveBreakpoint || (!shouldAutoShowHome && !options.initiallyOpen)) {
                return;
            }

            initializeWidget();

            if ($(window).width() <= responsiveBreakpoint) {
                hideMenu();
            } else if (shouldAutoShowHome || options.initiallyOpen) {
                $toggleMenu.stop(true, true).show();
                $toggleTitle.addClass('active');
                applyBodyClass(true);
            }
        }

        function toggleMenu() {
            if (!$toggleMenu.length) {
                return;
            }

            if ($toggleMenu.is(':visible')) {
                hideMenu();
            } else {
                showMenu();
            }
        }

        if (shouldAutoShowHome || options.initiallyOpen) {
            $toggleMenu.show();
            $toggleTitle.addClass('active');
            applyBodyClass(true);
        } else {
            $toggleMenu.hide();
            $toggleTitle.removeClass('active');
            applyBodyClass(false);
        }

        $toggleTitle.on('click.verticalMenu', function (event) {
            event.preventDefault();
            toggleMenu();
        });

        if ($levelItems.length && limit && $levelItems.length > limit) {
            $levelItems.each(function (index) {
                if (index >= (limit - 1)) {
                    $(this).addClass('orther-link').hide();
                }
            });

            $expandLink.show().on('click.verticalMenu', function (event) {
                event.preventDefault();
                var $link = $(this);

                $link.toggleClass('expanding');
                $container.find(selectors.levelItems + '.orther-link').slideToggle('slow');
            });
        } else if ($expandLink.length) {
            $expandLink.hide();
        }

        if (options.toggleBodyClass && selectors.bodyOverlay) {
            $body.on('click.verticalMenu', selectors.bodyOverlay, function () {
                hideMenu();
            });
        }

        if (responsiveBreakpoint) {
            responsiveMenu();
            $(window).on('resize.verticalMenu', responsiveMenu);
        }
    };
});
