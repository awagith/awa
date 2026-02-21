define([
    'jquery',
    'rokanthemes/verticalmenu'
], function ($) {
    'use strict';

    function initRokanVerticalMenuWidget($menus) {
            var initialized = false;

        if (!$.isFunction($.fn.VerticalMenu)) {
                return initialized;
        }

        $menus.each(function () {
            var $menu = $(this);

            if ($menu.data('awaRokanVerticalMenuInit')) {
                return;
            }

            $menu.VerticalMenu();
            $menu.data('awaRokanVerticalMenuInit', 1);
            initialized = true;
        });

        return initialized;
    }

    function debounce(fn, wait) {
        var timer = null;

        return function () {
            var args = arguments;
            var context = this;

            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(context, args);
            }, wait || 120);
        };
    }

    function cleanupAyoVerticalArtifacts($menuList) {
        if (!$menuList || !$menuList.length) {
            return;
        }

        $menuList.find('> li.vertical-menu-custom-block').each(function () {
            var $block = $(this);
            var hasRenderableContent = $.trim($block.text()).length > 0 ||
                $block.find('img[src], picture source[srcset], video source[src], iframe[src], a[href], .block, .cms-block').length > 0;

            if (!hasRenderableContent) {
                $block.remove();
            }
        });

        $menuList.find('> li.vertical-bg-img').each(function () {
            var $promoBlock = $(this);
            var hasPromoContent = $.trim($promoBlock.text()).length > 0 ||
                $promoBlock.find('img[src], picture source[srcset], video source[src], iframe[src], a[href]').length > 0;

            if (!hasPromoContent) {
                $promoBlock.remove();
            }
        });
    }

    return function (config, element) {
        var $nav = $(element);
        var $toggleMenu = $nav.find('.togge-menu');
        var $title = $nav.find('.title-category-dropdown');
        var $expandLink = $nav.find('.vm-toggle-categories');
        var $items = $nav.find('.ui-menu-item.level0');
        var overlaySelector = (config && config.overlaySelector) || '.shadow_bkg_show';
        var desktopBreakpoint = parseInt(config && config.desktopBreakpoint, 10) || 992;
        var defaultLimit = parseInt(config && config.limitShow, 10) || 0;
        var limitItemShow = parseInt($toggleMenu.attr('data-limit-show'), 10) || defaultLimit;
        var $rootVerticalMenus;
        var rokanWidgetEnabled = false;
        var resizeNamespace = '.awaVerticalMenuResize';

        if (!$nav.length || $nav.data('awaVerticalMenuInit')) {
            return;
        }

        $nav.data('awaVerticalMenuInit', 1);
        $nav.attr('data-awa-verticalmenu-owner', 'vertical-menu-init');

        $rootVerticalMenus = $nav.filter('.verticalmenu').add($nav.find('.verticalmenu'));
        // Keep native Rokanthemes positioning handlers active (desktop flyout alignment).
        rokanWidgetEnabled = initRokanVerticalMenuWidget($rootVerticalMenus);
        cleanupAyoVerticalArtifacts($toggleMenu);

        function isDesktopViewport() {
            if (window.matchMedia) {
                return window.matchMedia('(min-width: ' + desktopBreakpoint + 'px)').matches;
            }

            return window.innerWidth >= desktopBreakpoint;
        }

        function openMenu(withOverlay) {
            if (isDesktopViewport()) {
                $toggleMenu.addClass('menu-open').stop(true, true).show();
                $title.addClass('active');
                $title.attr('aria-expanded', 'true');
                $('body').removeClass('background_shadow_show');
                return;
            }

            $toggleMenu.addClass('menu-open').stop(true, true).fadeIn(200);
            $title.addClass('active');
            $title.attr('aria-expanded', 'true');
            $('body').toggleClass('background_shadow_show', !!withOverlay);
        }

        function closeMenu() {
            if (isDesktopViewport()) {
                $toggleMenu.removeClass('menu-open').stop(true, true).hide();
                $title.removeClass('active');
                $title.attr('aria-expanded', 'false');
                $('body').removeClass('background_shadow_show');
                return;
            }

            $toggleMenu.removeClass('menu-open').stop(true, true).fadeOut(200);
            $title.removeClass('active');
            $title.attr('aria-expanded', 'false');
            $('body').removeClass('background_shadow_show');
        }

        function isMenuOpenState() {
            return $toggleMenu.hasClass('menu-open') || $title.hasClass('active');
        }

        function ensureMobileSubmenuToggles() {
            $nav.find('.ui-menu-item.parent, .ui-menu-item.level0.parent').each(function () {
                var $item = $(this);
                var $toggle = $item.children('.open-children-toggle');

                if (!$toggle.length) {
                    $item.append('<div class="open-children-toggle" role="button" aria-label="Expandir subcategorias" aria-expanded="false" tabindex="0" data-awa-vtoggle="1"></div>');
                    return;
                }

                $toggle
                    .attr('role', 'button')
                    .attr('tabindex', '0')
                    .attr('aria-label', $toggle.attr('aria-label') || 'Expandir subcategorias')
                    .attr('data-awa-vtoggle', '1');

                if (!$toggle.attr('aria-expanded')) {
                    $toggle.attr('aria-expanded', 'false');
                }
            });
        }

        function syncMenuState() {
            if (isDesktopViewport()) {
                $nav.find('.ui-menu-item.parent, .ui-menu-item.level0.parent').each(function () {
                    var $parent = $(this);
                    $parent.removeClass('_active');
                    $parent.children('.open-children-toggle').attr('aria-expanded', 'false');
                    $parent.children('.submenu, ul.level0').stop(true, true).removeAttr('style');
                });

                if (isMenuOpenState()) {
                    $toggleMenu.addClass('menu-open').stop(true, true).show();
                    $title.addClass('active').attr('aria-expanded', 'true');
                } else {
                    $toggleMenu.removeClass('menu-open').stop(true, true).hide();
                    $title.removeClass('active').attr('aria-expanded', 'false');
                }

                $('body').removeClass('background_shadow_show');
                return;
            }

            $toggleMenu.removeClass('menu-open').stop(true, true).hide();
            $title.removeClass('active');
            $title.attr('aria-expanded', 'false');
            $('body').removeClass('background_shadow_show');
        }

        ensureMobileSubmenuToggles();
        $title.attr({
            role: 'button',
            tabindex: '0',
            'aria-expanded': 'false'
        });

        $nav.off('click.awaVerticalMenuToggle').on('click.awaVerticalMenuToggle', '.open-children-toggle', function (event) {
            var $toggle = $(this);
            var $parent = $toggle.parent();
            var isExpanding;

            event.preventDefault();
            event.stopPropagation();

            if (isDesktopViewport()) {
                return;
            }

            if (rokanWidgetEnabled) {
                return;
            }

            isExpanding = !$parent.hasClass('_active');

            $parent.toggleClass('_active');
            $toggle.attr('aria-expanded', isExpanding ? 'true' : 'false');
            $parent.children('.submenu, ul.level0').stop(true, true).slideToggle(200);
        });

        $nav.off('keydown.awaVerticalMenuToggle').on('keydown.awaVerticalMenuToggle', '.open-children-toggle', function (event) {
            if (rokanWidgetEnabled) {
                return;
            }

            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                $(this).trigger('click');
            }
        });

        $title.off('click.awaVerticalMenu').on('click.awaVerticalMenu', function (event) {
            var isOpening;

            event.preventDefault();

            isOpening = !$title.hasClass('active');

            if (isOpening) {
                openMenu(true);
            } else {
                closeMenu();
            }
        });

        $title.off('keydown.awaVerticalMenu').on('keydown.awaVerticalMenu', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                $(this).trigger('click');
            }
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

            $expandLink.closest('.expand-category-link').show();
            $expandLink.show().off('click.awaVerticalMenu').on('click.awaVerticalMenu', function (event) {
                var isExpanding;
                var $link = $(this);

                event.preventDefault();

                isExpanding = !$link.hasClass('expanding');
                $link.toggleClass('expanding');

                /* Toggle text if data attributes are present */
                if ($link.data('show-text') && $link.data('hide-text')) {
                    $link.find('span').text(
                        isExpanding ? $link.data('hide-text') : $link.data('show-text')
                    );
                }

                $link.attr('aria-expanded', isExpanding ? 'true' : 'false');
                $link.find('> span').attr('aria-expanded', isExpanding ? 'true' : 'false');
                $nav.find('.ui-menu-item.level0.orther-link').fadeToggle(200);
            });
        } else {
            $expandLink.closest('.expand-category-link').hide();
            $expandLink.hide();
        }

        $expandLink.attr({
            role: 'button',
            'aria-expanded': 'false',
            'data-awa-expandlink-owner': 'vertical-menu-init'
        });

        $expandLink.find('> span').attr('aria-expanded', 'false');

        $(overlaySelector).off('click.awaVerticalMenu').on('click.awaVerticalMenu', function () {
            if (isDesktopViewport()) {
                return;
            }

            closeMenu();
        });

        $(window).off('resize' + resizeNamespace).on('resize' + resizeNamespace, debounce(function () {
            ensureMobileSubmenuToggles();
            syncMenuState();
        }, 120));

        syncMenuState();
    };
});
