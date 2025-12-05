define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        var $source = $(element),
            $link = $source.find('.b2b-header-link').first(),
            targets = (config && config.targets) || [],
            method = (config && config.method) || 'prepend';

        if (!$link.length || !targets.length) {
            $source.remove();
            return;
        }

        var inserted = targets.some(function (selector) {
            var $target = $(selector).first();

            if (!$target.length) {
                return false;
            }

            var $clonedLink = $link.clone(true, true);

            if (method === 'append') {
                $target.append($clonedLink);
            } else {
                $target.prepend($clonedLink);
            }

            $source.remove();

            return true;
        });

        if (!inserted) {
            $source.remove();
        }
    };
});