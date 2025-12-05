define([
    'jquery',
    'rokanthemes/timecircles',
    'rokanthemes/owl'
], function ($) {
    'use strict';

    return function (config, element) {
        var $root = $(element),
            slideSelector = config.slideSelector || '.hot-deal-slide',
            countdownSelector = config.countdownSelector || '.super-deal-countdown',
            progressSelector = config.progressSelector || '.ruler-sold',
            countdownColors = config.countdownColors || {},
            labels = config.countdownLabels || {};

        $root.find(slideSelector).owlCarousel({
            lazyLoad: true,
            items: 6,
            itemsDesktop: [1366, 5],
            itemsDesktopSmall: [1199, 4],
            itemsTablet: [991, 3],
            itemsMobile: [680, 2],
            navigation: true,
            pagination: false,
            afterAction: function () {
                this.$owlItems.removeClass('first-active');
                this.$owlItems.eq(this.currentItem).addClass('first-active');
            }
        });

        $root.find(countdownSelector).each(function () {
            var $countdown = $(this);

            if (typeof $countdown.TimeCircles !== 'function') {
                return;
            }

            $countdown.TimeCircles({
                fg_width: 0.01,
                bg_width: 1.2,
                text_size: 0.07,
                circle_bg_color: countdownColors.background || '#ffffff',
                time: {
                    Days: {
                        show: true,
                        text: labels.days || 'Days',
                        color: countdownColors.days || '#f9bc02'
                    },
                    Hours: {
                        show: true,
                        text: labels.hours || 'Hours',
                        color: countdownColors.hours || '#f9bc02'
                    },
                    Minutes: {
                        show: true,
                        text: labels.minutes || 'Mins',
                        color: countdownColors.minutes || '#f9bc02'
                    },
                    Seconds: {
                        show: true,
                        text: labels.seconds || 'Secs',
                        color: countdownColors.seconds || '#f9bc02'
                    }
                }
            });
        });

        $root.find(progressSelector).each(function () {
            var $progress = $(this),
                value = parseFloat($progress.data('progress'));

            if (isNaN(value)) {
                return;
            }

            value = Math.min(100, Math.max(0, value));

            $progress
                .find('.ruler-sold-count')
                .css('width', value + '%');
        });
    };
});