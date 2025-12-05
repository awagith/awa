'use strict';

define(['jquery'], function ($) {
    function activateTab($root, $tab) {
        var targetId = $tab.attr('rel');
        var $contentPanels = $root.find('.tab_content');
        var $target;

        if (!targetId) {
            return;
        }

        $target = $root.find('#' + targetId.replace(/^#/, ''));

        if (!$target.length) {
            return;
        }

        $tab.closest('ul').find('li').removeClass('active');
        $tab.addClass('active');

        $contentPanels.hide().removeClass('animate1');
        $target.stop(true, true).fadeIn().addClass('animate1');
    }

    return function initTopHomeTabs(config, element) {
        var $root = $(element);
        var $tabs = $root.find('ul.tabs li');

        if ($root.data('topHomeTabsBound') || !$tabs.length) {
            return;
        }

        $root.data('topHomeTabsBound', true);

        $tabs.on('click.topHomeTabs', function (event) {
            event.preventDefault();
            activateTab($root, $(this));
        });

        $tabs.filter('.active').first().each(function () {
            activateTab($root, $(this));
        });
    };
});
