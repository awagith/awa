define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        var $root = $(element);
        var ajaxUrl = String(config.ajaxUrl || '');
        var rootMargin = String(config.rootMargin || '250px 0px');
        var fallbackDelay = Number(config.fallbackDelay || 300);
        var loaded = false;

        if (!$root.length || !ajaxUrl) {
            return;
        }

        function loadSuggestions() {
            if (loaded) {
                return;
            }
            loaded = true;

            $.get(ajaxUrl)
                .done(function (html) {
                    if (html && html.trim().length) {
                        $root.replaceWith(html);
                    } else {
                        $root.remove();
                    }
                })
                .fail(function () {
                    $root.remove();
                });
        }

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                if (entries[0] && entries[0].isIntersecting) {
                    observer.disconnect();
                    loadSuggestions();
                }
            }, { rootMargin: rootMargin });

            observer.observe($root.get(0));
            return;
        }

        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(loadSuggestions, { timeout: 1200 });
            return;
        }

        window.setTimeout(loadSuggestions, fallbackDelay);
    };
});
