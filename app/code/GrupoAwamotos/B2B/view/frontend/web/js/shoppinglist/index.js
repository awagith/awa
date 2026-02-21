define(['jquery'], function ($) {
    'use strict';

    return function (config, element) {
        var $root = $(element);
        var $createButton = $root.find('#btn-create-list');
        var $cancelButton = $root.find('#btn-cancel-create');
        var $createForm = $root.find('#create-list-form');
        var $nameInput = $root.find('#list-name');

        $createButton.on('click', function () {
            $createForm.stop(true, true).slideDown(200);
            $createButton.hide();
            $nameInput.trigger('focus');
        });

        $cancelButton.on('click', function () {
            $createForm.stop(true, true).slideUp(200);
            $createButton.show();
        });

        $root.on('click', '[data-confirm]', function (event) {
            var message = $(this).data('confirm');

            if (message && !window.confirm(message)) {
                event.preventDefault();
            }
        });
    };
});
