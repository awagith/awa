/**
 * Quick Order Modal Widget
 * Used in the header Quick Order modal/panel.
 * Calls Validate endpoint (step 1) then Add endpoint (step 2).
 *
 * @module awaQuickOrderModal
 */
define([
    'jquery',
    'mage/translate',
    'mage/cookies',
    'Magento_Customer/js/customer-data'
], function ($, $t, _cookies, customerData) {
    'use strict';

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
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

    /**
     * Parse textarea content into [{sku, qty}] array
     */
    function parseLines(text) {
        var lines = text.split('\n');
        var items = [];
        var seen = {};

        $.each(lines, function (_, line) {
            line = $.trim(line);
            if (!line) {
                return;
            }

            var parts = line.split(',');
            var sku = $.trim(parts[0] || '').replace(/^["']|["']$/g, '');
            if (!sku) {
                return;
            }

            var qty = parseInt($.trim(parts[1] || ''), 10);
            if (isNaN(qty) || qty < 1) {
                qty = 1;
            }

            var key = sku.toLowerCase();
            if (!seen[key]) {
                seen[key] = true;
                items.push({ sku: sku, qty: qty });
            }
        });

        return items;
    }

    function statusBadge(status, inStock) {
        if (status === 'found' && inStock) {
            return '<span class="qo-modal-badge qo-modal-badge--ok">' + $t('Disponível') + '</span>';
        }
        if (status === 'found') {
            return '<span class="qo-modal-badge qo-modal-badge--warn">' + $t('Sem estoque') + '</span>';
        }
        if (status === 'not_found') {
            return '<span class="qo-modal-badge qo-modal-badge--err">' + $t('SKU não encontrado') + '</span>';
        }
        if (status === 'unavailable') {
            return '<span class="qo-modal-badge qo-modal-badge--warn">' + $t('Indisponível') + '</span>';
        }
        return '<span class="qo-modal-badge qo-modal-badge--err">' + $t('Erro') + '</span>';
    }

    return function (config, element) {
        var $root       = $(element);
        var $textarea   = $root.find('#awa-quick-order-textarea');
        var $btnValidate = $root.find('#awa-btn-validate-skus');
        var $btnAdd     = $root.find('#awa-btn-add-all-tocart');
        var $results    = $root.find('#awa-quick-order-results-container');
        var $tbody      = $root.find('#awa-quick-order-tbody');
        var $tfoot      = $root.find('#awa-quick-order-tfoot');
        var $summary    = $root.find('#awa-qo-summary');

        var validateUrl = config.validateUrl || '';
        var addUrl      = config.addUrl || '';

        // Validated items cache (those with status 'found' + in_stock)
        var validatedItems = [];

        $btnValidate.on('click', function () {
            var items = parseLines($textarea.val() || '');

            if (!items.length) {
                return;
            }

            $btnValidate.prop('disabled', true).find('span').text($t('Validando...'));
            $tbody.empty();
            $tfoot.hide();
            $results.show();
            validatedItems = [];

            $.ajax({
                url: validateUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: getFormKey(),
                    items: items
                }
            }).done(function (response) {
                var responseItems = (response && response.items) ? response.items : [];
                var foundCount = 0;

                $.each(responseItems, function (_, item) {
                    var canAdd = item.status === 'found' && item.in_stock;
                    if (canAdd) {
                        foundCount++;
                        validatedItems.push({ sku: item.sku, qty: item.qty });
                    }

                    $tbody.append(
                        '<tr class="' + (canAdd ? '' : 'qo-modal-row--issue') + '">' +
                        '<td><code>' + escapeHtml(item.sku) + '</code></td>' +
                        '<td>' + escapeHtml(item.name || '—') + '</td>' +
                        '<td>' + escapeHtml(item.qty) + '</td>' +
                        '<td>' + escapeHtml(item.price || '—') + '</td>' +
                        '<td>' + statusBadge(item.status, item.in_stock) + '</td>' +
                        '</tr>'
                    );
                });

                var total = responseItems.length;
                var issues = total - foundCount;
                $summary.html(
                    foundCount + ' ' + $t('disponíve') + (foundCount === 1 ? 'l' : 'is') +
                    (issues > 0 ? ', <span style="color:#b73337">' + issues + ' ' + $t('com problema') + '</span>' : '')
                );
                $tfoot.show();
                $btnAdd.prop('disabled', foundCount === 0);
            }).fail(function () {
                $tbody.append('<tr><td colspan="5" style="color:#b73337;padding:12px;">' +
                    $t('Erro ao validar. Tente novamente.') + '</td></tr>');
            }).always(function () {
                $btnValidate.prop('disabled', false).find('span').text($t('1. Validar SKUs'));
            });
        });

        $btnAdd.on('click', function () {
            if (!validatedItems.length) {
                return;
            }

            $btnAdd.prop('disabled', true).find('span').text($t('Adicionando...'));

            $.ajax({
                url: addUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    form_key: getFormKey(),
                    items: validatedItems
                }
            }).done(function (response) {
                if (response && response.success) {
                    customerData.reload(['cart'], true);
                    $btnAdd.find('span').text($t('✓ Adicionado!'));
                    $textarea.val('');
                    setTimeout(function () {
                        $results.hide();
                        $tbody.empty();
                        $btnAdd.prop('disabled', true).find('span').text($t('2. Adicionar ao Carrinho'));
                        validatedItems = [];
                    }, 2000);
                } else {
                    $btnAdd.find('span').text($t('2. Adicionar ao Carrinho'));
                    $btnAdd.prop('disabled', false);
                }
            }).fail(function () {
                $btnAdd.prop('disabled', false).find('span').text($t('2. Adicionar ao Carrinho'));
            });
        });
    };
});
