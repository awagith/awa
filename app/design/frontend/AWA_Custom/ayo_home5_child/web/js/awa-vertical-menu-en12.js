/**
 * DEPRECATED — Fase 3 Step 4 (consolidação de menus)
 * Motivo: Mueller JS é a fonte única de verdade para o menu vertical.
 * Este arquivo NÃO é inicializado pelo sidemenu.phtml (Mueller JS é usado).
 * Mantido no repositório por referência; código inativo (dead code).
 * Original: 95 linhas — EN12 vertical menu JS (toggle, limitShow, slideUp/Down)
 * Depreciado em: 2026-03-10 | Branch: feat/fase3-header-navigation
 */
define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        var settings = $.extend({
            limitShow: 11,
            overlaySelector: '.shadow_bkg_show'
        }, config || {});
        var $nav = $(element);
        var $title = $nav.find('.title-category-dropdown').first();
        var $toggleMenu = $nav.find('.togge-menu.list-category-dropdown').first();
        var $items = $toggleMenu.children('.ui-menu-item.level0');
        var $expandContainer = $toggleMenu.children('.expand-category-link').first();
        var $expandLink = $expandContainer.find('a').first();
        var $overlay = $(settings.overlaySelector).first();
        var limitItemShow = parseInt(settings.limitShow, 10) || 11;

        if (!$nav.length || !$title.length || !$toggleMenu.length || $nav.data('awaVerticalMenuEn12Init')) {
            return;
        }

        $nav.data('awaVerticalMenuEn12Init', 1);

        function initRokanthemesVerticalMenu() {
            if ($.isFunction($.fn.VerticalMenu)) {
                $nav.VerticalMenu();
                return;
            }

            require(['Rokanthemes_VerticalMenu/js/verticalmenu'], function () {
                if ($.isFunction($.fn.VerticalMenu)) {
                    $nav.VerticalMenu();
                }
            });
        }

        function closeMenu() {
            $toggleMenu.stop(true, true).slideUp('slow');
            $title.removeClass('active');
            $('body').removeClass('background_shadow_show');
        }

        function toggleMenu() {
            $toggleMenu.stop(true, true).slideToggle('slow');
            $title.toggleClass('active');
            $('body').toggleClass('background_shadow_show');
        }

        function applyLimitShow() {
            if (limitItemShow < 1 || !$expandContainer.length) {
                return;
            }

            if ($items.length > limitItemShow) {
                $items.each(function (index) {
                    var $item = $(this);
                    if (index >= (limitItemShow - 1)) {
                        $item.addClass('orther-link').hide();
                    }
                });

                $expandContainer.show();
                $expandLink.off('click.awaEn12Expand').on('click.awaEn12Expand', function (event) {
                    event.preventDefault();
                    $(this).toggleClass('expanding');
                    $toggleMenu.children('.ui-menu-item.level0.orther-link').stop(true, true).slideToggle('slow');
                });
            } else {
                $expandContainer.hide();
            }
        }

        initRokanthemesVerticalMenu();
        $toggleMenu.hide();
        $title.off('click.awaEn12').on('click.awaEn12', function (event) {
            event.preventDefault();
            toggleMenu();
        });

        $overlay.off('click.awaEn12').on('click.awaEn12', function (event) {
            event.preventDefault();
            closeMenu();
        });

        applyLimitShow();
    };
});
