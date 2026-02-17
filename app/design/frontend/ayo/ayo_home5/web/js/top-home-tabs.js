define(['jquery'], function ($) {
    'use strict';

    function activateTab($scope, $tab) {
        var targetId = $tab.attr('rel');
        var $tabs = $scope.find('ul.tabs li');
        var $panels = $scope.find('.tab_content');

        $tabs.removeClass('active')
            .attr('aria-selected', 'false')
            .attr('tabindex', '-1');

        $tab.addClass('active')
            .attr('aria-selected', 'true')
            .attr('tabindex', '0');

        $panels.hide()
            .removeClass('animate1')
            .attr('aria-hidden', 'true');

        if (!targetId) {
            return;
        }

        $scope.find('#' + targetId).addClass('animate1')
            .attr('aria-hidden', 'false')
            .fadeIn();
    }

    return function (config, element) {
        var $scope = $(element);
        var $tabs = $scope.find('ul.tabs li');
        var $active = $tabs.filter('.active').first();

        if (!$tabs.length) {
            return;
        }

        if (!$active.length) {
            $active = $tabs.first();
        }

        $scope.off('click.awaTopTabs keydown.awaTopTabs', 'ul.tabs li');
        $scope.on('click.awaTopTabs keydown.awaTopTabs', 'ul.tabs li', function (event) {
            var isKeyboard = event.type === 'keydown';
            var key = event.which || event.keyCode;

            if (isKeyboard && key !== 13 && key !== 32) {
                return;
            }

            event.preventDefault();
            activateTab($scope, $(this));
        });

        activateTab($scope, $active);
    };
});
