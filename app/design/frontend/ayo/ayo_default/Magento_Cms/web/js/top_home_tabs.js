define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        var $root = $(element);

        $root.find('ul.tabs li').on('click', function () {
            var $tab = $(this),
                $tabsList = $tab.closest('ul'),
                $tabWrapper = $tab.closest('.common-tab-system-fixed'),
                activeTab = $tab.attr('rel');

            $tabsList.find('li').removeClass('active');
            $tab.addClass('active');

            $tabWrapper.find('.tab_content')
                .hide()
                .removeClass('animate1');

            $('#' + activeTab).addClass('animate1').fadeIn();
        });
    };
});