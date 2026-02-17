define([
    'jquery',
    'rokanthemes/owl'
], function ($) {
    'use strict';

    function applyReduceMotion(sliderConfig) {
        try {
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                if (Object.prototype.hasOwnProperty.call(sliderConfig, 'autoPlay')) {
                    sliderConfig.autoPlay = false;
                }
                if (Object.prototype.hasOwnProperty.call(sliderConfig, 'autoplay')) {
                    sliderConfig.autoplay = false;
                }
            }
        } catch (error) {
            // Keep existing config when matchMedia is unavailable.
        }
    }

    return function (config, element) {
        var $root = $(element);
        var $owl = $root.find('.owl');
        var sliderConfig = $.extend(true, {}, (config && config.owl) || {});
        var labels = (config && config.labels) || {};

        if (!$owl.length || typeof $owl.owlCarousel !== 'function' || $root.data('awaSlideBannerInit')) {
            return;
        }

        $root.data('awaSlideBannerInit', 1);
        applyReduceMotion(sliderConfig);

        function applyState() {
            var $items = $owl.find('.owl-item');

            if (!$items.length) {
                return;
            }

            $items.each(function () {
                var $item = $(this);
                var isActive = $item.hasClass('active');
                var $links = $item.find('a, button');

                $item.attr('aria-hidden', isActive ? 'false' : 'true');
                if ($links.length) {
                    $links.attr('tabindex', isActive ? '0' : '-1');
                }
            });

            $root.find('.owl-prev').attr({
                'aria-label': labels.prev || 'Slide anterior',
                'role': 'button',
                'tabindex': '0'
            });
            $root.find('.owl-next').attr({
                'aria-label': labels.next || 'Próximo slide',
                'role': 'button',
                'tabindex': '0'
            });

            $root.find('.owl-pagination').attr({
                'role': 'tablist',
                'aria-label': labels.pagination || 'Indicadores de slides'
            });

            $root.find('.owl-page').each(function (index) {
                var $page = $(this);
                var isSelected = $page.hasClass('active');

                $page.attr({
                    'role': 'tab',
                    'aria-label': (labels.goTo || 'Ir para slide') + ' ' + (index + 1),
                    'aria-selected': isSelected ? 'true' : 'false',
                    'tabindex': isSelected ? '0' : '-1'
                });
            });
        }

        $owl.on('initialized.owl.carousel refreshed.owl.carousel changed.owl.carousel', function () {
            applyState();
        });

        $root.on('keydown', '.owl-prev, .owl-next, .owl-page', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                $(this).trigger('click');
            }
        });

        $owl.owlCarousel(sliderConfig);
        applyState();
    };
});
