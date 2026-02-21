define([
    'jquery',
    'mage/translate',
    'mage/cookies',
    'Magento_Ui/js/modal/alert'
], function ($, $t, _cookies, alertModal) {
    'use strict';

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showAlert(message) {
        alertModal({
            title: $t('B2B'),
            content: message
        });
    }

    function normalizeSku(sku) {
        return $.trim(sku || '').toLowerCase();
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
        var messages = config.messages || {};
        var addUrl = config.addUrl || '';
        var initialRows = parseInt(config.initialRows, 10) || 5;

        var $root = $(element);
        var $rowsContainer = $root.find('#quickorder-rows');
        var $results = $root.find('#quickorder-results');
        var $success = $root.find('#quickorder-success');
        var $errors = $root.find('#quickorder-errors');
        var $container = $root.find('#quickorder-container');
        var $submitButton = $root.find('#quickorder-submit');
        var $submitButtonText = $submitButton.find('span');

        var rowIndex = 0;

        function buttonText(text) {
            if ($submitButtonText.length) {
                $submitButtonText.text(text);
                return;
            }

            $submitButton.text(text);
        }

        function createRow() {
            rowIndex += 1;

            return [
                '<div class="quickorder-row" data-index="', rowIndex, '">',
                '<input type="text" name="items[', rowIndex, '][sku]"',
                ' placeholder="', escapeHtml(messages.skuPlaceholder || 'Ex: SKU-001'), '" class="sku-input" />',
                '<input type="number" name="items[', rowIndex, '][qty]" value="1" min="1" class="qty-input" />',
                '<button type="button" class="btn-remove" title="',
                escapeHtml(messages.removeRow || $t('Remover')), '">&times;</button>',
                '</div>'
            ].join('');
        }

        function addRow() {
            $rowsContainer.append(createRow());
        }

        function clearStatuses() {
            $rowsContainer.find('.quickorder-row').removeClass('has-error');
        }

        function renderSuccessList(added, message) {
            if (!added || !added.length) {
                $success.empty().hide();
                return;
            }

            var html = '<strong>' + escapeHtml(message || '') + '</strong><ul>';

            $.each(added, function (index, item) {
                html += '<li>' + escapeHtml(item.name || '') +
                    ' (SKU: ' + escapeHtml(item.sku || '') + ') - Qtd: ' + escapeHtml(item.qty || 0) + '</li>';
            });

            html += '</ul>';
            $success.html(html).show();
        }

        function renderErrorList(errorList) {
            if (!errorList || !errorList.length) {
                $errors.empty().hide();
                return;
            }

            var html = '<strong>' + escapeHtml($t('Erros:')) + '</strong><ul>';
            $.each(errorList, function (index, item) {
                html += '<li>SKU: ' + escapeHtml(item.sku || '') + ' - ' + escapeHtml(item.message || '') + '</li>';
            });
            html += '</ul>';
            $errors.html(html).show();
        }

        function collectItems() {
            var aggregated = {};
            var rowMap = {};

            $rowsContainer.find('.quickorder-row').each(function () {
                var $row = $(this);
                var sku = $.trim($row.find('.sku-input').val());
                var qty = parseInt($row.find('.qty-input').val(), 10) || 1;
                var key;

                if (!sku) {
                    return;
                }

                qty = Math.max(1, qty);
                key = normalizeSku(sku);

                if (!aggregated[key]) {
                    aggregated[key] = {
                        sku: sku,
                        qty: 0
                    };
                    rowMap[key] = [];
                }

                aggregated[key].qty += qty;
                rowMap[key].push($row);
            });

            return {
                items: $.map(aggregated, function (item) {
                    return item;
                }),
                rowMap: rowMap
            };
        }

        function markErrorRows(errorsList, rowMap) {
            $.each(errorsList || [], function (index, item) {
                var key = normalizeSku(item.sku || '');
                var rows = rowMap[key] || [];

                $.each(rows, function (_, $row) {
                    $row.addClass('has-error');
                });
            });
        }

        function submitQuickOrder() {
            if (!addUrl) {
                return;
            }

            clearStatuses();
            $results.hide();
            $success.empty();
            $errors.empty();

            var payload = collectItems();
            if (!payload.items.length) {
                showAlert(messages.skuRequired || $t('Informe pelo menos um SKU.'));
                return;
            }

            $submitButton.prop('disabled', true);
            buttonText(messages.processing || $t('Processando...'));
            $container.addClass('quickorder-loading');

            $.ajax({
                url: addUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    items: payload.items,
                    form_key: getFormKey()
                }
            }).done(function (response) {
                $results.show();
                renderSuccessList(response.added || [], response.message || '');
                renderErrorList(response.errors || []);
                markErrorRows(response.errors || [], payload.rowMap);

                if (!response.success && !response.errors.length) {
                    showAlert(response.message || (messages.requestError || $t('Erro ao processar pedido. Tente novamente.')));
                }
            }).fail(function () {
                showAlert(messages.requestError || $t('Erro ao processar pedido. Tente novamente.'));
            }).always(function () {
                $submitButton.prop('disabled', false);
                buttonText(messages.addToCart || $t('Adicionar ao Carrinho'));
                $container.removeClass('quickorder-loading');
            });
        }

        $root.on('click', '#quickorder-add-row', function () {
            addRow();
        });

        $rowsContainer.on('click', '.btn-remove', function () {
            var $allRows = $rowsContainer.find('.quickorder-row');
            if ($allRows.length <= 1) {
                return;
            }

            $(this).closest('.quickorder-row').remove();
        });

        $root.on('click', '#quickorder-submit', function () {
            submitQuickOrder();
        });

        for (var i = 0; i < initialRows; i += 1) {
            addRow();
        }
    };
});
