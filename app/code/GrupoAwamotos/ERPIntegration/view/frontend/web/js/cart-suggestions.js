define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'mage/cookies'
], function ($, customerData, alertModal, $t) {
    'use strict';

    function showAlert(message) {
        alertModal({
            title: $t('Atenção'),
            content: message,
            buttons: [{
                text: $t('OK'),
                class: 'action primary',
                click: function() { this.closeModal(); }
            }]
        });
    }

    return function (config, element) {
        var $root = $(element);
        var addBySkuUrl = config.addBySkuUrl || '';
        var reloadDelay = Number(config.reloadDelay || 1200);
        var mode = config.mode || 'main';

        if (!$root.length || !addBySkuUrl) {
            return;
        }

        function getFormKey() {
            return $.mage && $.mage.cookies ? $.mage.cookies.get('form_key') : null;
        }

        function reloadCartData() {
            customerData.reload(['cart'], true);
        }

        function addBySku($button, sku, qty) {
            return $.ajax({
                url: addBySkuUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    sku: sku,
                    qty: qty,
                    form_key: getFormKey()
                }
            }).done(function (response) {
                if (!response || !response.success) {
                    var message = response && response.message ? response.message : $t('Erro ao adicionar produto');
                    showAlert(message);
                    return;
                }

                reloadCartData();

                if (mode === 'sidebar') {
                    $button.text('OK').addClass('added');
                } else {
                    $button.addClass('erp-added');
                    $button.find('.btn-text').text('OK').show();
                    $button.find('.btn-loading').hide();
                }

                window.setTimeout(function () {
                    $button.prop('disabled', false).removeClass('erp-added');

                    if (mode === 'sidebar') {
                        $button.text('+');
                    } else {
                        $button.find('.btn-text').show();
                        $button.find('.btn-loading').hide();
                    }
                }, reloadDelay);
            }).fail(function () {
                showAlert($t('Erro de conexão. Tente novamente.'));
            });
        }

        $root.on('click', '.erp-widget-add-btn', function () {
            var $button = $(this);
            var sku = String($button.data('sku') || '');
            var qty = Number($button.data('qty') || 1);

            if (!sku) {
                return;
            }

            $button.prop('disabled', true);
            $button.find('.btn-text').hide();
            $button.find('.btn-loading').show();

            addBySku($button, sku, qty).always(function () {
                if (!$button.hasClass('erp-added')) {
                    $button.prop('disabled', false);
                    $button.find('.btn-text').show();
                    $button.find('.btn-loading').hide();
                }
            });
        });

        $root.on('click', '.erp-sidebar-add', function () {
            var $button = $(this);
            var sku = String($button.data('sku') || '');
            var qty = Number($button.data('qty') || 1);

            if (!sku) {
                return;
            }

            $button.prop('disabled', true).text('...');

            addBySku($button, sku, qty).always(function () {
                if (!$button.hasClass('added')) {
                    $button.prop('disabled', false).text('+');
                }
            });
        });
    };
});
