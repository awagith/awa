define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        var $root = $(element),
            windowName = config.windowName || 'ShareWindow',
            windowFeatures = config.windowFeatures || 'width=600,height=600';

        $root.on('click', '[data-role="print-link"]', function (event) {
            event.preventDefault();
            window.print();
        });

        $root.on('click', '[data-role="share-link"]', function (event) {
            var $link = $(this),
                shareUrl = $link.attr('href');

            if (!shareUrl) {
                return;
            }

            event.preventDefault();
            window.open(shareUrl, windowName, windowFeatures);
        });
    };
});
