define([
    'jquery',
    'mage/translate',
    'mage/cookies',
    'mage/url',
    'Magento_Ui/js/modal/alert'
], function ($, $t, _cookies, urlBuilder, alertModal) {
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

    /* ─── SKU Autocomplete ──────────────────────────────────────────────────── */

    var searchUrl = urlBuilder.build('b2b/quickorder/search');

    /**
     * Debounce helper
     */
    function debounce(fn, delay) {
        var timer;
        return function () {
            var ctx = this, args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(ctx, args); }, delay);
        };
    }

    /**
     * Render one autocomplete dropdown row
     */
    function renderSuggestion(product) {
        var stockBadge = product.in_stock
            ? '<span class="qo-ac-stock qo-ac-stock--ok">' + escapeHtml(product.stock) + ' un.</span>'
            : '<span class="qo-ac-stock qo-ac-stock--out">' + $t('Indisponível') + '</span>';

        return '<li class="qo-ac-item" role="option" tabindex="-1"' +
            ' data-sku="' + escapeHtml(product.sku) + '"' +
            ' data-name="' + escapeHtml(product.name) + '"' +
            ' data-price="' + escapeHtml(product.price) + '">' +
            '<span class="qo-ac-sku">' + escapeHtml(product.sku) + '</span>' +
            '<span class="qo-ac-name">' + escapeHtml(product.name) + '</span>' +
            '<span class="qo-ac-price">' + escapeHtml(product.price) + '</span>' +
            stockBadge +
            '</li>';
    }

    /**
     * Close all open autocomplete dropdowns
     */
    function closeAllDropdowns() {
        $('.qo-ac-dropdown').removeClass('qo-ac-dropdown--open').empty();
    }

    /**
     * Bind autocomplete behavior on a single SKU input
     */
    function bindAutocomplete($input) {
        var $row = $input.closest('.quickorder-row');

        // Create dropdown if it doesn't exist
        if (!$row.find('.qo-ac-dropdown').length) {
            $row.append('<ul class="qo-ac-dropdown" role="listbox" aria-label="' +
                $t('Sugestões de SKU') + '"></ul>');
        }

        var $dropdown = $row.find('.qo-ac-dropdown');
        var activeXhr = null;

        var doSearch = debounce(function () {
            var query = $.trim($input.val());

            if (query.length < 2) {
                $dropdown.removeClass('qo-ac-dropdown--open').empty();
                return;
            }

            if (activeXhr) {
                activeXhr.abort();
            }

            activeXhr = $.ajax({
                url: searchUrl,
                type: 'GET',
                dataType: 'json',
                data: { q: query }
            }).done(function (response) {
                var products = (response && response.products) ? response.products : [];
                $dropdown.empty();

                if (!products.length) {
                    $dropdown.append('<li class="qo-ac-empty">' + $t('Nenhum produto encontrado') + '</li>');
                    $dropdown.addClass('qo-ac-dropdown--open');
                    return;
                }

                $.each(products, function (_, product) {
                    $dropdown.append(renderSuggestion(product));
                });

                $dropdown.addClass('qo-ac-dropdown--open');
            }).fail(function (xhr) {
                if (xhr.statusText !== 'abort') {
                    $dropdown.removeClass('qo-ac-dropdown--open').empty();
                }
            }).always(function () {
                activeXhr = null;
            });
        }, 300);

        $input.on('input.qoAc', doSearch);

        // Keyboard navigation inside dropdown
        $input.on('keydown.qoAc', function (e) {
            var $items = $dropdown.find('.qo-ac-item');
            var $focused = $dropdown.find('.qo-ac-item:focus, .qo-ac-item.qo-ac-item--active');

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!$focused.length) {
                    $items.first().addClass('qo-ac-item--active').focus();
                } else {
                    var $next = $focused.next('.qo-ac-item');
                    $focused.removeClass('qo-ac-item--active');
                    ($next.length ? $next : $items.first()).addClass('qo-ac-item--active').focus();
                }
            } else if (e.key === 'Escape') {
                $dropdown.removeClass('qo-ac-dropdown--open').empty();
                $input.focus();
            }
        });

        // Select suggestion by click or Enter
        $dropdown.on('click', '.qo-ac-item', function () {
            var $li = $(this);
            $input.val($li.data('sku'));
            $dropdown.removeClass('qo-ac-dropdown--open').empty();
            // Move focus to qty field
            $row.find('.qty-input').focus();
        });

        $dropdown.on('keydown', '.qo-ac-item', function (e) {
            var $li = $(this);
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $input.val($li.data('sku'));
                $dropdown.removeClass('qo-ac-dropdown--open').empty();
                $row.find('.qty-input').focus();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                var $next = $li.next('.qo-ac-item');
                $li.removeClass('qo-ac-item--active');
                ($next.length ? $next : $dropdown.find('.qo-ac-item').first()).addClass('qo-ac-item--active').focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                var $prev = $li.prev('.qo-ac-item');
                $li.removeClass('qo-ac-item--active');
                if ($prev.length) {
                    $prev.addClass('qo-ac-item--active').focus();
                } else {
                    $input.focus();
                }
            } else if (e.key === 'Escape') {
                $dropdown.removeClass('qo-ac-dropdown--open').empty();
                $input.focus();
            }
        });
    }

    // Close dropdown when clicking outside
    $(document).on('click.qoAcGlobal', function (e) {
        if (!$(e.target).closest('.quickorder-row').length) {
            closeAllDropdowns();
        }
    });

    /* ─── Main widget ───────────────────────────────────────────────────────── */

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

        // CSV elements
        var $csvZone = $root.find('#quickorder-csv-zone');
        var $dropzone = $root.find('#csv-dropzone');
        var $fileInput = $root.find('#csv-file-input');
        var $csvStatus = $root.find('#csv-status');
        var $csvDownloadSample = $root.find('#csv-download-sample');

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
            // Bind autocomplete on the newly created SKU input
            var $newInput = $rowsContainer.find('.quickorder-row').last().find('.sku-input');
            bindAutocomplete($newInput);
        }

        function clearStatuses() {
            $rowsContainer.find('.quickorder-row').removeClass('has-error');
        }

        /**
         * Parse CSV text into array of {sku, qty} objects
         * Supports: SKU,QTY and SKU;QTY formats
         * First line is skipped if it looks like a header
         */
        function parseCSV(text) {
            var lines = text.split(/\r?\n/);
            var items = [];
            var separator = ',';

            // Detect separator from first non-empty line
            for (var s = 0; s < lines.length; s++) {
                var trimmed = $.trim(lines[s]);
                if (trimmed) {
                    if (trimmed.indexOf(';') > -1 && trimmed.indexOf(',') === -1) {
                        separator = ';';
                    }
                    break;
                }
            }

            $.each(lines, function (index, line) {
                line = $.trim(line);
                if (!line) {
                    return;
                }

                var parts = line.split(separator);
                var sku = $.trim(parts[0] || '').replace(/^["']|["']$/g, '');
                var qtyStr = $.trim(parts[1] || '').replace(/^["']|["']$/g, '');

                // Skip header row
                if (index === 0 && sku && isNaN(parseInt(qtyStr, 10)) &&
                    /^(sku|codigo|código|produto|product|item)/i.test(sku)) {
                    return;
                }

                if (!sku) {
                    return;
                }

                var qty = parseInt(qtyStr, 10);
                if (isNaN(qty) || qty < 1) {
                    qty = 1;
                }

                items.push({ sku: sku, qty: qty });
            });

            return items;
        }

        /**
         * Clear existing rows and populate from parsed CSV items
         */
        function populateFromCSV(items) {
            // Remove all existing rows
            $rowsContainer.find('.quickorder-row').remove();
            rowIndex = 0;

            if (!items.length) {
                showAlert(messages.csvEmpty || $t('O arquivo CSV não contém produtos válidos.'));
                for (var i = 0; i < initialRows; i++) {
                    addRow();
                }
                return;
            }

            // Create rows for each CSV item
            $.each(items, function (idx, item) {
                addRow();
                var $row = $rowsContainer.find('.quickorder-row').last();
                $row.find('.sku-input').val(item.sku);
                $row.find('.qty-input').val(item.qty);
            });

            // Add a few extra empty rows
            addRow();
            addRow();

            var msg = (messages.csvLoaded || $t('%1 produto(s) carregado(s) do CSV.'))
                .replace('%1', items.length);
            $csvStatus.text(msg).addClass('csv-status--success').show();

            setTimeout(function () {
                $csvStatus.removeClass('csv-status--success').fadeOut();
            }, 5000);
        }

        /**
         * Handle file from input or drag-drop
         */
        function handleCSVFile(file) {
            if (!file) {
                return;
            }

            // Validate file type
            var name = (file.name || '').toLowerCase();
            if (name.indexOf('.csv') === -1 && file.type.indexOf('csv') === -1 && file.type !== 'text/plain') {
                showAlert(messages.csvInvalidFile || $t('Selecione um arquivo .csv válido.'));
                return;
            }

            var reader = new FileReader();
            reader.onload = function (e) {
                var text = e.target.result;
                var items = parseCSV(text);
                populateFromCSV(items);
            };
            reader.onerror = function () {
                showAlert(messages.csvInvalidFile || $t('Erro ao ler o arquivo.'));
            };
            reader.readAsText(file, 'UTF-8');
        }

        /**
         * Generate and download a sample CSV file
         */
        function downloadSampleCSV() {
            var csvContent = 'SKU,Quantidade\n';
            csvContent += 'BAG-CG160-001,2\n';
            csvContent += 'RET-TITAN-003,1\n';
            csvContent += 'BAU-45L-PRETO,3\n';

            var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            var url = URL.createObjectURL(blob);
            var link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', 'modelo-pedido-rapido.csv');
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
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

        // === CSV Upload: Drag & Drop ===
        $dropzone.on('dragover dragenter', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('csv-dropzone--active');
        });

        $dropzone.on('dragleave drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('csv-dropzone--active');
        });

        $dropzone.on('drop', function (e) {
            var files = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files;
            if (files && files.length) {
                handleCSVFile(files[0]);
            }
        });

        // Click on dropzone opens file dialog
        $dropzone.on('click', function () {
            $fileInput.trigger('click');
        });

        // Keyboard accessibility: Enter/Space opens file dialog
        $dropzone.on('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $fileInput.trigger('click');
            }
        });

        // File input change
        $fileInput.on('change', function () {
            var files = this.files;
            if (files && files.length) {
                handleCSVFile(files[0]);
                // Reset input so same file can be re-uploaded
                $(this).val('');
            }
        });

        // Download sample CSV
        $csvDownloadSample.on('click', function (e) {
            e.preventDefault();
            downloadSampleCSV();
        });

        for (var i = 0; i < initialRows; i += 1) {
            addRow();
        }
    };
});
