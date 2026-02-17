define([
    'jquery',
    'rokanthemes/custommenu'
], function ($) {
    'use strict';

    return function (config, element) {
        var $scope = $(element);
        var $menus = $scope.filter('.custommenu').add($scope.find('.custommenu'));

        if (!$menus.length) {
            $menus = $('.custommenu');
        }

        $menus.each(function () {
            var $menu = $(this);

            if ($menu.data('awaCustomMenuInit')) {
                return;
            }

            if (typeof $menu.CustomMenu !== 'function') {
                return;
            }

            $menu.data('awaCustomMenuInit', 1);
            $menu.CustomMenu();
        });
    };
});
