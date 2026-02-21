define([
    'jquery',
    'mage/translate',
    'mage/cookies',
    'Magento_Ui/js/modal/alert'
], function ($, $t, _cookies, alertModal) {
    'use strict';

    function showAlert(message) {
        alertModal({
            title: $t('B2B'),
            content: message
        });
    }

    function getFormKey() {
        if (window.FORM_KEY) {
            return window.FORM_KEY;
        }

        if ($.mage && $.mage.cookies) {
            return $.mage.cookies.get('form_key') || '';
        }

        return '';
    }

    return function (config, element) {
        var $root = $(element);
        var actionUrl = config.actionUrl || '';
        var messages = config.messages || {};

        if (!actionUrl) {
            return;
        }

        $root.on('click', '[data-action="approve"], [data-action="reject"]', function () {
            var $button = $(this);
            var approvalId = parseInt($button.data('approval-id'), 10) || 0;
            var action = $button.data('action') || '';
            var comment = '';

            if (!approvalId || !action) {
                return;
            }

            if (action === 'reject') {
                comment = window.prompt(messages.rejectReasonPrompt || $t('Motivo da rejeição:'), '');
                if (comment === null) {
                    return;
                }
                comment = $.trim(comment);
                if (!comment) {
                    showAlert(messages.rejectReasonPrompt || $t('Motivo da rejeição:'));
                    return;
                }
            }

            $button.prop('disabled', true).addClass('is-loading');

            $.ajax({
                url: actionUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    approval_id: approvalId,
                    action: action,
                    comment: comment,
                    reason: comment,
                    form_key: getFormKey()
                }
            }).done(function (response) {
                if (response && response.message) {
                    showAlert(response.message);
                }

                if (response && response.success) {
                    window.location.reload();
                }
            }).fail(function () {
                showAlert(messages.processError || $t('Erro ao processar. Tente novamente.'));
            }).always(function () {
                $button.prop('disabled', false).removeClass('is-loading');
            });
        });
    };
});
