define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        var $loader = $(element),
            delay = config && config.delay ? config.delay : 500,
            fallbackTimeout = config && config.fallbackTimeout ? config.fallbackTimeout : 1000,
            hidden = false;

        if (!$loader.length) {
            return;
        }

        function hideLoader() {
            if (hidden) {
                return;
            }

            hidden = true;

            $loader.delay(delay).fadeOut('slow');
        }

        $(window).on('load.beforebodyloader', hideLoader);

        setTimeout(hideLoader, fallbackTimeout);
    };
});