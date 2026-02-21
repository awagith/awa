define(['jquery'], function ($) {
    'use strict';

    return function (config, element) {
        var $root = $(element);

        $root.on('change', '.js-reorder-toggle-all', function () {
            var orderId = $(this).data('order-id');
            var checked = $(this).is(':checked');

            $root.find('.js-reorder-item-check[data-order-id="' + orderId + '"]').prop('checked', checked);
        });
    };
});
