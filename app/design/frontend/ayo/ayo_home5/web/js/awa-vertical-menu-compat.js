/**
 * AWA Motos — Vertical Menu Compat Layer
 * Wraps vertical-menu-init and adds ARIA state synchronization.
 */
define([
    'jquery',
    'js/vertical-menu-init'
], function ($, baseInit) {
    'use strict';

    function syncAriaState($nav) {
        // Sync ARIA on submenu toggles
        $nav.find('.navigation__toggle').each(function () {
            var $toggle = $(this);
            var $parent = $toggle.closest('.navigation__item');
            var $sub    = $parent.children('.navigation__submenu');
            var isOpen  = $parent.hasClass('is-hovered') || $sub.hasClass('is-active');

            $toggle.attr('aria-expanded', isOpen ? 'true' : 'false');
            if ($sub.length) {
                $sub.attr('aria-hidden', isOpen ? 'false' : 'true');
            }
        });

        // Sync trigger state
        var $trigger = $nav.find('[data-role="awa-vertical-menu-trigger"]');
        var $list    = $nav.find('.navigation__list').first();
        var isOpen   = $list.hasClass('is-active') || $list.is(':visible');
        $trigger.attr('aria-expanded', isOpen ? 'true' : 'false');
    }

    return function (config, element) {
        var $nav = $(element);

        if (!$nav.length || $nav.data('awaVMCompatInit')) {
            return;
        }

        // Initialize base functionality
        baseInit(config, element);

        // Mark as ready
        $nav.attr('data-awa-initialized', 'true').addClass('is-ready');

        // Initial ARIA sync
        syncAriaState($nav);

        // Sync on user interactions
        $nav.on('click.awaVMCompat mouseenter.awaVMCompat mouseleave.awaVMCompat', function () {
            setTimeout(function () {
                syncAriaState($nav);
            }, 200);
        });

        // Sync on resize
        $(window).on('resize.awaVMCompat', function () {
            syncAriaState($nav);
        });

        // MutationObserver for external DOM changes
        if (typeof MutationObserver === 'function') {
            var observer = new MutationObserver(function () {
                syncAriaState($nav);
            });

            observer.observe($nav[0], {
                subtree: true,
                attributes: true,
                attributeFilter: ['class']
            });

            $nav.data('awaVMCompatObserver', observer);
        }

        $nav.data('awaVMCompatInit', 1);
    };
});
