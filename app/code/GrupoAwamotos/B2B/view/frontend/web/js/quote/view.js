define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/confirm'
], function ($, $t, confirmModal) {
    'use strict';

    return function (config, element) {
        var $root = $(element);
        var messages = config.messages || {};

        $root.on('submit', 'form[data-confirm]', function (event) {
            var $form = $(this);
            var message = $form.data('confirm') || messages.rejectConfirm || $t('Confirmar ação?');

            if ($form.data('confirmed') === true) {
                $form.data('confirmed', false);
                return;
            }

            event.preventDefault();
            confirmModal({
                title: $t('Confirmar'),
                content: message,
                actions: {
                    confirm: function () {
                        $form.data('confirmed', true);
                        $form.trigger('submit');
                    }
                }
            });
        });
    };
});
