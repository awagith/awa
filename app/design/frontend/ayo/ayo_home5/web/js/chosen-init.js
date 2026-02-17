define([
    'jquery',
    'rokanthemes/choose'
], function ($) {
    'use strict';

    return function (config, element) {
        var $element = $(element);
        var selector = config && config.selector ? config.selector : '#choose_category';
        var options = (config && config.options) || {};
        var $targets;

        if ($element.is('select')) {
            $targets = $element;
        } else {
            $targets = $element.find(selector);
            if (!$targets.length && selector) {
                $targets = $(selector);
            }
        }

        $targets.each(function () {
            var $select = $(this);

            if (typeof $select.chosen !== 'function') {
                return;
            }

            if ($select.data('chosen') || $select.data('awaChosenInit')) {
                return;
            }

            $select.data('awaChosenInit', 1);
            $select.chosen(options);
        });
    };
});
