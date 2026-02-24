/**
 * AWA Motos — Vertical Menu Toggle Controller
 *
 * Manages the sidebar vertical-menu lifecycle:
 *  - Desktop >= 992 px : click-to-toggle dropdown (CSS-driven via .menu-open / .active)
 *  - Mobile  <  992 px : animated drawer + overlay + submenu accordions
 *
 * The native Rokanthemes VerticalMenu jQuery plugin is still initialised for
 * its flyout-positioning logic (hover on desktop).  AWA does NOT duplicate
 * that behaviour; it only adds: open/close of the category list,
 * expand/collapse "Show More", and mobile submenu toggles when the
 * Rokanthemes widget is absent.
 *
 * @module js/vertical-menu-init
 */
define([
    'jquery',
    'rokanthemes/verticalmenu'
], function ($) {
    'use strict';

    /* ================================================================ */
    /*  Helpers (shared across instances)                               */
    /* ================================================================ */

    /**
     * Initialise the Rokanthemes VerticalMenu plugin (idempotent).
     * @param  {jQuery} $menus
     * @return {boolean} true when at least one widget is active
     */
    function initRokanWidget($menus) {
        if (!$.isFunction($.fn.VerticalMenu)) {
            return false;
        }

        var ok = false;

        $menus.each(function () {
            var $m = $(this);

            if (!$m.data('awaRokanInit')) {
                $m.VerticalMenu();
                $m.data('awaRokanInit', 1);
            }

            ok = true;
        });

        return ok;
    }

    /** Trailing-edge debounce. */
    function debounce(fn, ms) {
        var t;

        return function () {
            var ctx  = this;
            var args = arguments;

            clearTimeout(t);
            t = setTimeout(function () { fn.apply(ctx, args); }, ms || 120);
        };
    }

    /** Remove empty CMS placeholder <li> nodes the block renderer may inject. */
    function pruneEmptyBlocks($list) {
        if (!$list || !$list.length) {
            return;
        }

        var has = 'img[src],picture source[srcset],video source[src],iframe[src],a[href],.block,.cms-block';

        $list.find('> li.vertical-menu-custom-block, > li.vertical-bg-img').each(function () {
            var $li = $(this);

            if (!$.trim($li.text()).length && !$li.find(has).length) {
                $li.remove();
            }
        });
    }

    /* ================================================================ */
    /*  Component (called once per x-magento-init match)                */
    /* ================================================================ */

    return function (config, element) {
        var $nav        = $(element);
        var $title      = $nav.find('.title-category-dropdown');
        var $list       = $nav.find('.togge-menu');
        var $expandLink = $nav.find('.vm-toggle-categories');
        var $items      = $nav.find('.ui-menu-item.level0');

        /* ---- config ------------------------------------------------ */
        var safeUid = ($nav.attr('id') || $title.attr('aria-controls') || 'avm-' + Math.random().toString(36).slice(2))
                          .replace(/[^a-zA-Z0-9_-]/g, '');
        var overlaySelector   = (config && config.overlaySelector) || '.shadow_bkg_show';
        var desktopBreakpoint = parseInt(config && config.desktopBreakpoint, 10) || 992;
        var limitItemShow     = parseInt($list.attr('data-limit-show'), 10)
                                || parseInt(config && config.limitShow, 10) || 0;
        var NS = '.awaVM-' + safeUid;

        /* ---- guard: never double-init ------------------------------ */
        if (!$nav.length || $nav.data('awaVMInit')) {
            return;
        }

        $nav.data('awaVMInit', 1);
        $nav.attr('data-awa-verticalmenu-owner', 'vertical-menu-init');

        /* ---- Rokanthemes flyout widget ------------------------------ */
        var rokanActive = initRokanWidget(
            $nav.filter('.verticalmenu').add($nav.find('.verticalmenu'))
        );

        pruneEmptyBlocks($list);

        /* ---- viewport ---------------------------------------------- */
        var mql = window.matchMedia
            ? window.matchMedia('(min-width: ' + desktopBreakpoint + 'px)')
            : null;

        function isDesktop() {
            return mql ? mql.matches : window.innerWidth >= desktopBreakpoint;
        }

        /* ============================================================ */
        /*  Open / Close                                                */
        /* ============================================================ */

        function openMenu() {
            $list.addClass('menu-open');
            $title.addClass('active').attr('aria-expanded', 'true');

            if (isDesktop()) {
                /* CSS !important drives display via .menu-open / .active+ul.
                   No jQuery .show() needed — it cannot override !important. */
                $('body').removeClass('background_shadow_show');
            } else {
                $list.stop(true, true).fadeIn(200);
                $('body').addClass('background_shadow_show');
            }
        }

        function closeMenu() {
            $list.removeClass('menu-open');
            $title.removeClass('active').attr('aria-expanded', 'false');

            if (!isDesktop()) {
                $list.stop(true, true).fadeOut(200);
            }

            $('body').removeClass('background_shadow_show');
        }

        function isOpen() {
            return $list.hasClass('menu-open');
        }

        /* ============================================================ */
        /*  Mobile sub-menu toggles                                     */
        /* ============================================================ */

        function ensureMobileToggles() {
            $nav.find('.ui-menu-item.parent, .ui-menu-item.level0.parent').each(function () {
                var $li = $(this);
                var $t  = $li.children('.open-children-toggle');

                if (!$t.length) {
                    $li.append(
                        '<div class="open-children-toggle" role="button"' +
                        ' aria-label="Expandir subcategorias" aria-expanded="false" tabindex="0"></div>'
                    );
                } else {
                    $t.attr({
                        'role':          'button',
                        'tabindex':      '0',
                        'aria-label':    $t.attr('aria-label') || 'Expandir subcategorias',
                        'aria-expanded': $t.attr('aria-expanded') || 'false'
                    });
                }
            });
        }

        /** Reset accordion & visibility when viewport crosses the breakpoint. */
        function syncOnResize() {
            if (isDesktop()) {
                /* Clean up mobile accordion + stale Rokanthemes "opened" class */
                $nav.find('.ui-menu-item.parent, .ui-menu-item.level0.parent').each(function () {
                    var $p = $(this);

                    $p.removeClass('_active');
                    $p.children('.submenu, ul.level0').removeClass('opened').removeAttr('style');
                    $p.children('.open-children-toggle').attr('aria-expanded', 'false');
                });

                /* Re-sync list visibility to current state */
                if (isOpen()) {
                    $title.addClass('active').attr('aria-expanded', 'true');
                } else {
                    $title.removeClass('active').attr('aria-expanded', 'false');
                }

                $('body').removeClass('background_shadow_show');
            } else {
                /* Entering mobile → collapse */
                $list.removeClass('menu-open').hide();
                $title.removeClass('active').attr('aria-expanded', 'false');
                $('body').removeClass('background_shadow_show');
            }
        }

        /* ============================================================ */
        /*  Event binding                                               */
        /* ============================================================ */

        /* ---- title click (main toggle) ----------------------------- */
        $title.on('click' + NS, function (e) {
            e.preventDefault();
            isOpen() ? closeMenu() : openMenu();
        });

        $title.on('keydown' + NS, function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $title.trigger('click' + NS);
            }
        });

        /* ---- mobile submenu accordion (only when Rokanthemes absent) */
        if (!rokanActive) {
            $nav.on('click' + NS, '.open-children-toggle', function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (isDesktop()) {
                    return;
                }

                var $t = $(this);
                var $p = $t.parent();
                var expanding = !$p.hasClass('_active');

                $p.toggleClass('_active');
                $t.attr('aria-expanded', expanding ? 'true' : 'false');
                $p.children('.submenu, ul.level0').stop(true, true).slideToggle(200);
            });

            $nav.on('keydown' + NS, '.open-children-toggle', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).trigger('click');
                }
            });
        }

        /* ---- "Show more / Show less" -------------------------------- */
        (function initExpandLink() {
            if (limitItemShow <= 0) {
                $expandLink.closest('.expand-category-link').hide();
                return;
            }

            var threshold = limitItemShow + 1; /* +1 = the <li> of the link itself */

            if ($items.length <= threshold) {
                $expandLink.closest('.expand-category-link').hide();
                return;
            }

            $items.each(function (i) {
                if (i >= threshold - 1) {
                    $(this).addClass('orther-link').hide();
                }
            });

            $expandLink.closest('.expand-category-link').show();

            $expandLink.on('click' + NS, function (e) {
                e.preventDefault();

                var $a        = $(this);
                var $hidden   = $nav.find('.ui-menu-item.level0.orther-link');
                var expanding = !$a.hasClass('expanding');

                $a.toggleClass('expanding', expanding)
                   .closest('.expand-category-link').toggleClass('expanding', expanding);

                if ($a.data('show-text') && $a.data('hide-text')) {
                    $a.find('span').text(expanding ? $a.data('hide-text') : $a.data('show-text'));
                }

                $a.attr('aria-expanded', expanding ? 'true' : 'false');
                $hidden.stop(true, true)[expanding ? 'fadeIn' : 'fadeOut'](180);
            });
        })();

        /* ---- overlay click → close (mobile) ------------------------ */
        $(overlaySelector).on('click' + NS, function () {
            if (!isDesktop()) {
                closeMenu();
            }
        });

        /* ---- resize ------------------------------------------------ */
        $(window).on('resize' + NS, debounce(function () {
            ensureMobileToggles();
            syncOnResize();
        }, 120));

        /* ---- cleanup on DOM removal -------------------------------- */
        $nav.on('remove' + NS, function () {
            $(window).off(NS);
            $(overlaySelector).off(NS);
        });

        /* ============================================================ */
        /*  Boot                                                        */
        /* ============================================================ */

        ensureMobileToggles();
        syncOnResize();
    };
});
