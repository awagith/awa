/**
 * B2B Quick Add to Shopping List Widget
 *
 * Handles the "Salvar na Lista" button on PLP product cards.
 * POSTs to b2b/shoppinglist/quickadd and shows a brief toast feedback.
 */
define([
    'jquery',
    'mage/translate',
    'Magento_Customer/js/customer-data'
], function ($, $t, customerData) {
    'use strict';

    var TOAST_DURATION = 3000;

    /**
     * Show a non-blocking toast message near the triggered button.
     *
     * @param {jQuery} $btn
     * @param {string} message
     * @param {boolean} isSuccess
     */
    function showToast($btn, message, isSuccess) {
        var $toast = $btn.closest('.item-product').find('.awa-sl-toast');

        if (!$toast.length) {
            $toast = $('<div class="awa-sl-toast" aria-live="polite" aria-atomic="true" role="status"></div>');
            $btn.closest('.item-product').append($toast);
        }

        $toast
            .text(message)
            .removeClass('awa-sl-toast--success awa-sl-toast--error')
            .addClass(isSuccess ? 'awa-sl-toast--success' : 'awa-sl-toast--error')
            .addClass('awa-sl-toast--visible');

        clearTimeout($toast.data('toastTimeout'));
        $toast.data('toastTimeout', setTimeout(function () {
            $toast.removeClass('awa-sl-toast--visible');
        }, TOAST_DURATION));
    }

    return function (config) {
        var addUrl = config.addUrl;
        var formKey = config.formKey || window.FORM_KEY;

        $(document).on('click.awaShoppingList', '[data-action="awa-save-to-list"]', function (event) {
            event.preventDefault();

            var $btn = $(this);
            var productId = parseInt($btn.data('product-id'), 10);
            var qty = parseFloat($btn.closest('form').find('[name="qty"]').val() || 1);

            if (!productId || $btn.hasClass('awa-sl-loading')) {
                return;
            }

            $btn.addClass('awa-sl-loading').attr('aria-busy', 'true');

            $.ajax({
                url: addUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    product_id: productId,
                    qty: qty,
                    form_key: formKey
                }
            }).done(function (response) {
                if (response && response.success) {
                    $btn.addClass('awa-sl-saved');
                    showToast($btn, response.message || $t('Salvo na lista!'), true);
                } else if (response && response.error_code === 'login_required') {
                    showToast($btn, $t('Faça login para salvar produtos.'), false);
                } else {
                    showToast($btn, (response && response.message) || $t('Erro ao salvar. Tente novamente.'), false);
                }
            }).fail(function () {
                showToast($btn, $t('Erro de conexão. Tente novamente.'), false);
            }).always(function () {
                $btn.removeClass('awa-sl-loading').attr('aria-busy', 'false');
            });
        });
    };
});
