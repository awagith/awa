define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        var $form = $(element),
            $input = $form.find(config.inputSelector || '#blog_search');

        $form.on('submit.blogSearch', function (event) {
            var value;

            event.preventDefault();

            if (!$input.length) {
                return;
            }

            value = $.trim($input.val());

            if (value) {
                window.location.href = $form.attr('action') + encodeURIComponent(value) + '/';
            }
        });
    };
});
