/**
 * AWA Motos — Vertical Menu Init (Mueller Pattern)
 * Desktop: hover with delay + overflow detection
 * Mobile:  off-canvas drawer + drill-down navigation
 */
define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        var $nav        = $(element);
        var $trigger    = $nav.find('[data-role="awa-vertical-menu-trigger"]');
        var $menuList   = $nav.find('.navigation__list').first();
        var desktopBP   = config.desktopBreakpoint || 992;
        var limitShow   = config.limitShow || 0;
        var hoverDelay  = 150;
        var hoverTimer  = null;
        var $overlay    = null;

        if ($nav.data('awaVMInit')) {
            return;
        }
        $nav.data('awaVMInit', 1);

        function isDesktop() {
            return window.innerWidth >= desktopBP;
        }

        // ── Overlay (lazy-created, appended to body) ──

        function getOverlay() {
            if (!$overlay || !$overlay.length) {
                $overlay = $('<div class="awa-vm-overlay"></div>');
                $('body').append($overlay);
                $overlay.on('click.awaVM', closeDrawer);
            }
            return $overlay;
        }

        // ── Desktop: Hover mega menu ──

        function initDesktopHover() {
            $nav.find('.navigation__item--parent')
                .off('mouseenter.awaVM mouseleave.awaVM')
                .on('mouseenter.awaVM', function () {
                    var $item = $(this);
                    clearTimeout(hoverTimer);

                    // Clear siblings immediately
                    $item.siblings('.is-hovered')
                         .removeClass('is-hovered')
                         .children('.navigation__submenu')
                         .css('display', '')
                         .removeClass('mega-menu--flip');

                    hoverTimer = setTimeout(function () {
                        if (!isDesktop()) {
                            return;
                        }
                        $item.addClass('is-hovered');
                        var $sub = $item.children('.navigation__submenu');
                        if ($sub.length) {
                            $sub.css('display', 'block');
                            checkOverflow($sub);
                        }
                    }, hoverDelay);
                })
                .on('mouseleave.awaVM', function () {
                    var $item = $(this);
                    clearTimeout(hoverTimer);
                    hoverTimer = setTimeout(function () {
                        $item.removeClass('is-hovered');
                        $item.children('.navigation__submenu')
                             .css('display', '')
                             .removeClass('mega-menu--flip');
                    }, hoverDelay);
                });
        }

        function checkOverflow($sub) {
            if (!$sub.length || !$nav.length) {
                return;
            }
            var navRect   = $nav[0].getBoundingClientRect();
            var subWidth  = $sub.outerWidth();
            var winWidth  = window.innerWidth;

            if (navRect.right + subWidth > winWidth) {
                $sub.addClass('mega-menu--flip');
            } else {
                $sub.removeClass('mega-menu--flip');
            }
        }

        // ── Mobile: Drawer open/close ──

        function openDrawer() {
            $menuList.addClass('is-active');
            getOverlay().addClass('is-active');
            $('body').addClass('awa-vm-open');
            $trigger.attr('aria-expanded', 'true');
        }

        function closeDrawer() {
            $menuList.removeClass('is-active');
            // Close all open sub-panels
            $nav.find('.navigation__submenu.is-active').removeClass('is-active');
            if ($overlay) {
                $overlay.removeClass('is-active');
            }
            $('body').removeClass('awa-vm-open');
            $trigger.attr('aria-expanded', 'false');
        }

        function initMobileDrawer() {
            // Inject close button at top of drawer (once)
            if (!$menuList.children('.awa-vm-close').length) {
                var $closeBtn = $(
                    '<button class="awa-vm-close" type="button" aria-label="Fechar menu">' +
                        '<span>Categorias</span>' +
                        '<span class="awa-vm-close__icon">&times;</span>' +
                    '</button>'
                );
                $menuList.prepend($closeBtn);
                $closeBtn.on('click.awaVM', closeDrawer);
            }
        }

        // ── Mobile: Drill-down back buttons ──

        function initBackButtons() {
            $nav.find('.navigation__submenu').each(function () {
                var $sub = $(this);
                if ($sub.children('.btn-back-menu').length) {
                    return;
                }
                var title = $sub.attr('data-menu-title') || 'Voltar';
                // Sanitize title via text node (XSS-safe)
                var safeTitle = $('<span>').text(title).html();
                var $back = $('<div class="btn-back-menu">' + safeTitle + '</div>');
                $sub.prepend($back);

                $back.on('click.awaVM', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $sub.removeClass('is-active');
                });
            });
        }

        // ── Mobile: Drill-down forward ──

        function initMobileDrillDown() {
            // Toggle button clicks
            $nav.find('.navigation__toggle')
                .off('click.awaVM')
                .on('click.awaVM', function (e) {
                    if (isDesktop()) {
                        return;
                    }
                    e.preventDefault();
                    e.stopPropagation();
                    var $sub = $(this).closest('.navigation__item')
                                      .children('.navigation__submenu');
                    if ($sub.length) {
                        $sub.addClass('is-active');
                    }
                });

            // Parent link clicks on mobile → drill down instead of navigate
            $nav.find('.navigation__item--parent > a')
                .off('click.awaVM')
                .on('click.awaVM', function (e) {
                    if (isDesktop()) {
                        return;
                    }
                    e.preventDefault();
                    var $sub = $(this).closest('.navigation__item')
                                      .children('.navigation__submenu');
                    if ($sub.length) {
                        $sub.addClass('is-active');
                    }
                });
        }

        // ── Trigger (header) click ──

        $trigger.off('click.awaVM keyup.awaVM')
            .on('click.awaVM', function (e) {
                e.preventDefault();
                if (isDesktop()) {
                    $menuList.slideToggle(200);
                } else {
                    openDrawer();
                }
            })
            .on('keyup.awaVM', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).trigger('click.awaVM');
                }
            });

        // ── Show More / Show Less ──

        if (limitShow > 0) {
            var $items = $menuList.children('.navigation__item');
            if ($items.length > limitShow) {
                $items.slice(limitShow).hide();
                var $expandLink = $nav.find('.expand-category-link a');
                $nav.find('.expand-category-link').show();

                $expandLink.off('click.awaVM').on('click.awaVM', function (e) {
                    e.preventDefault();
                    var $link = $(this);
                    if ($link.hasClass('expanded')) {
                        $items.slice(limitShow).slideUp(200);
                        $link.removeClass('expanded');
                        $link.attr('aria-expanded', 'false');
                        $link.find('span').text($link.data('show-text') || 'Show More');
                    } else {
                        $items.slice(limitShow).slideDown(200);
                        $link.addClass('expanded');
                        $link.attr('aria-expanded', 'true');
                        $link.find('span').text($link.data('hide-text') || 'Show Less');
                    }
                });
            } else {
                $nav.find('.expand-category-link').hide();
            }
        }

        // ── Init + resize handler ──

        function init() {
            if (isDesktop()) {
                initDesktopHover();
            } else {
                initMobileDrawer();
                initBackButtons();
                initMobileDrillDown();
            }
        }

        var resizeTimer;
        $(window).off('resize.awaVM').on('resize.awaVM', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function () {
                if (isDesktop()) {
                    // Leaving mobile → clean up
                    closeDrawer();
                    $nav.find('.is-hovered').removeClass('is-hovered');
                    $nav.find('.navigation__submenu')
                        .css('display', '')
                        .removeClass('mega-menu--flip');
                    initDesktopHover();
                } else {
                    // Leaving desktop → clean up
                    $nav.find('.is-hovered').removeClass('is-hovered');
                    $nav.find('.navigation__submenu')
                        .css('display', '')
                        .removeClass('mega-menu--flip');
                    initMobileDrawer();
                    initBackButtons();
                    initMobileDrillDown();
                }
            }, 200);
        });

        init();
    };
});
