'use strict';

define(['jquery'], function ($) {
    return function initNotificationMarquee(config, element) {
        var $marquee = $(element);

        if ($marquee.data('notificationMarqueeBound')) {
            return;
        }

        $marquee.data('notificationMarqueeBound', true);

        $marquee.on('mouseenter.notificationMarquee', function () {
            if (typeof element.stop === 'function') {
                element.stop();
            }
        });

        $marquee.on('mouseleave.notificationMarquee', function () {
            if (typeof element.start === 'function') {
                element.start();
            }
        });
    };
});
