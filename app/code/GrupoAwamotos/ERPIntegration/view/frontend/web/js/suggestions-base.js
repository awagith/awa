define([
    'jquery',
    'mage/cookies'
], function ($) {
    'use strict';

    return function (config, element) {
        var $container = $(element);
        var addBySkuUrl = String(config.addBySkuUrl || '');
        var checkoutCartUrl = String(config.checkoutCartUrl || '');

        if (!$container.length || !addBySkuUrl) {
            return;
        }

        function getFormKey() {
            return $.mage && $.mage.cookies ? $.mage.cookies.get('form_key') : '';
        }

        function formatPrice(value) {
            return 'R$ ' + Number(value || 0).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function addToCart(sku, qty) {
            return new Promise(function (resolve, reject) {
                $.ajax({
                    url: addBySkuUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        sku: sku,
                        qty: qty,
                        form_key: getFormKey()
                    }
                }).done(function (response) {
                    if (response && response.success) {
                        resolve(response);
                        return;
                    }
                    reject(response && response.message ? response.message : 'Error');
                }).fail(function () {
                    reject('Network error');
                });
            });
        }

        function updateTotals() {
            var subtotal = 0;
            var itemCount = 0;

            $container.find('.erp-item-select:checked').each(function () {
                var $item = $(this).closest('.erp-cart-item');
                var qty = parseInt($item.find('.erp-qty-input').val(), 10) || 1;
                var price = parseFloat($(this).data('price')) || 0;
                subtotal += qty * price;
                itemCount++;
            });

            $container.find('.erp-subtotal-value').text(formatPrice(subtotal));
            $container.find('.erp-cart-items').text(itemCount + ' produtos');
            $container.find('.erp-cart-total').text(formatPrice(subtotal));

            var freeShippingThreshold = 1500;
            var remaining = freeShippingThreshold - subtotal;

            if (remaining <= 0) {
                $container.find('.erp-shipping-info').hide();
                $container.find('.erp-free-shipping').show();
                $container.find('.erp-total-value').text(formatPrice(subtotal));
            } else {
                $container.find('.erp-free-shipping').hide();
                $container.find('.erp-shipping-info').show().find('span').text('Falta ' + formatPrice(remaining) + ' para frete gratis');
                $container.find('.erp-total-value').text(formatPrice(subtotal + 50));
            }
        }

        $container.on('change input', '.erp-qty-input', function () {
            var $input = $(this);
            var $item = $input.closest('.erp-cart-item');
            var qty = parseInt($input.val(), 10) || 1;
            var price = parseFloat($item.find('.erp-item-select').data('price')) || 0;
            var lineTotal = qty * price;

            $item.find('.erp-line-total').text(formatPrice(lineTotal));
            $item.find('.erp-item-select').data('qty', qty);
            updateTotals();
        });

        $container.on('change', '.erp-item-select', function () {
            updateTotals();
        });

        $('#erp-select-all').on('click', function () {
            var $checkboxes = $container.find('.erp-item-select');
            var allChecked = $checkboxes.length && $checkboxes.filter(':checked').length === $checkboxes.length;

            $checkboxes.prop('checked', !allChecked);
            $(this).text(allChecked ? 'Selecionar Todos' : 'Desmarcar Todos');
            updateTotals();
        });

        $('#erp-add-all-to-cart').on('click', function () {
            var $btn = $(this);
            var items = [];

            $container.find('.erp-item-select:checked').each(function () {
                var $item = $(this).closest('.erp-cart-item');
                items.push({
                    sku: $(this).val(),
                    qty: parseInt($item.find('.erp-qty-input').val(), 10) || 1
                });
            });

            if (!items.length) {
                window.alert('Selecione pelo menos um produto para adicionar ao carrinho.');
                return;
            }

            $btn.prop('disabled', true).text('Adicionando...');

            Promise.all(items.map(function (item) {
                return addToCart(item.sku, item.qty);
            })).then(function () {
                $btn.text('Adicionado');
                if (checkoutCartUrl) {
                    window.setTimeout(function () {
                        window.location.href = checkoutCartUrl;
                    }, 1000);
                }
            }).catch(function () {
                window.alert('Erro ao adicionar produtos. Tente novamente.');
                $btn.prop('disabled', false).text('Adicionar Selecionados ao Carrinho');
            });
        });

        updateTotals();
    };
});
