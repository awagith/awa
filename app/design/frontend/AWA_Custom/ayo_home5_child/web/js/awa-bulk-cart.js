/**
 * AWA Motos — Bulk Cart JS
 * Adiciona múltiplos SKUs ao carrinho via AJAX (Quick Order).
 * Compatível com RequireJS / x-magento-init.
 *
 * Uso:
 *   data-mage-init='{"AWA_Custom/js/awa-bulk-cart": {"baseUrl": "https://...", "formKey": "..."}}'
 */
define(['jquery', 'mage/translate', 'mage/url'], function ($, $t, urlBuilder) {
    'use strict';

    var DEFAULT_CONFIG = {
        formKey:     window.FORM_KEY || '',
        rowSelector: '.awa-bulk-row',
        skuSelector: '.awa-bulk-row__sku input',
        qtySelector: '.awa-bulk-row__qty input',
        addBtnSel:   '[data-action="bulk-add"]',
        feedbackSel: '[data-bulk-feedback]',
        addRowSel:   '[data-action="add-row"]',
        endpoint:    '/checkout/cart/add/'
    };

    return function (config, element) {
        var cfg  = Object.assign({}, DEFAULT_CONFIG, config || {});
        var $el  = $(element);

        // ── Adicionar linha de SKU ────────────────────────────────
        $el.on('click', cfg.addRowSel, function () {
            var $rows = $el.find(cfg.rowSelector);
            var $last = $rows.last();
            var $new  = $last.clone();
            $new.find('input').val('');
            $new.find('[data-bulk-row-status]').text('').attr('data-status', '');
            $last.after($new);
        });

        // ── Remover linha ─────────────────────────────────────────
        $el.on('click', '[data-action="remove-row"]', function () {
            var $rows = $el.find(cfg.rowSelector);
            if ($rows.length > 1) {
                $(this).closest(cfg.rowSelector).remove();
            }
        });

        // ── Validar SKU ao perder foco (lookup básico) ───────────
        $el.on('blur', cfg.skuSelector, function () {
            var $input  = $(this);
            var sku     = $input.val().trim().toUpperCase();
            var $row    = $input.closest(cfg.rowSelector);
            var $status = $row.find('[data-bulk-row-status]');
            if (!sku) {
                $status.text('').attr('data-status', '');
                return;
            }
            // Mostra indicador de busca
            $status.text($t('Verificando...')).attr('data-status', 'loading');
        });

        // ── Adicionar todos ao carrinho ───────────────────────────
        $el.on('click', cfg.addBtnSel, function () {
            var $btn      = $(this);
            var $feedback = $el.find(cfg.feedbackSel);
            var rows      = [];

            $el.find(cfg.rowSelector).each(function () {
                var $row = $(this);
                var sku  = $row.find(cfg.skuSelector).val().trim().toUpperCase();
                var qty  = parseInt($row.find(cfg.qtySelector).val(), 10) || 1;
                if (sku) {
                    rows.push({ sku: sku, qty: qty, $row: $row });
                }
            });

            if (!rows.length) {
                showFeedback($feedback, 'error', $t('Adicione ao menos um SKU.'));
                return;
            }

            $btn.prop('disabled', true).addClass('is-loading');
            showFeedback($feedback, 'info', $t('Adicionando itens ao carrinho...'));

            var promises = rows.map(function (item) {
                return addToCart(item, cfg);
            });

            Promise.allSettled(promises).then(function (results) {
                $btn.prop('disabled', false).removeClass('is-loading');

                var ok  = results.filter(function (r) { return r.status === 'fulfilled' && r.value.ok; });
                var err = results.filter(function (r) { return r.status !== 'fulfilled' || !r.value.ok; });

                results.forEach(function (result, idx) {
                    var $row    = rows[idx].$row;
                    var $status = $row.find('[data-bulk-row-status]');
                    if (result.status === 'fulfilled' && result.value.ok) {
                        $status.text($t('Adicionado ✓')).attr('data-status', 'success');
                    } else {
                        var msg = (result.value && result.value.message) ? result.value.message : $t('SKU não encontrado');
                        $status.text(msg).attr('data-status', 'error');
                    }
                });

                if (ok.length && !err.length) {
                    showFeedback($feedback, 'success',
                        $t('%1 item(ns) adicionado(s) ao carrinho.').replace('%1', ok.length)
                    );
                    updateMiniCart();
                } else if (ok.length && err.length) {
                    showFeedback($feedback, 'warning',
                        $t('%1 adicionado(s), %2 não encontrado(s).')
                            .replace('%1', ok.length)
                            .replace('%2', err.length)
                    );
                    updateMiniCart();
                } else {
                    showFeedback($feedback, 'error', $t('Nenhum item foi adicionado. Verifique os SKUs.'));
                }
            });
        });

        /**
         * Envia request de add-to-cart para um item.
         * @param {{sku: string, qty: number}} item
         * @param {Object} cfg
         * @returns {Promise<{ok: boolean, message?: string}>}
         */
        function addToCart(item, cfg) {
            return new Promise(function (resolve) {
                $.ajax({
                    url:    urlBuilder.build(cfg.endpoint),
                    method: 'POST',
                    data: {
                        form_key: cfg.formKey || window.FORM_KEY || '',
                        sku:      item.sku,
                        qty:      item.qty
                    },
                    dataType: 'json',
                    success: function (resp) {
                        if (resp && resp.backUrl) {
                            resolve({ ok: true });
                        } else if (resp && resp.messages && resp.messages.error && resp.messages.error.length) {
                            resolve({ ok: false, message: resp.messages.error[0].text || $t('Erro') });
                        } else {
                            resolve({ ok: true });
                        }
                    },
                    error: function () {
                        resolve({ ok: false, message: $t('Erro de conexão') });
                    }
                });
            });
        }

        /**
         * Atualiza o minicart via evento Magento.
         */
        function updateMiniCart() {
            var sections = ['cart'];
            try {
                require(['Magento_Customer/js/customer-data'], function (customerData) {
                    customerData.invalidate(sections);
                    customerData.reload(sections, true);
                });
            } catch (e) { /* silently ignore */ }
        }

        /**
         * Exibe mensagem de feedback.
         * @param {jQuery} $el
         * @param {'info'|'success'|'warning'|'error'} type
         * @param {string} msg
         */
        function showFeedback($target, type, msg) {
            $target
                .attr('data-type', type)
                .text(msg)
                .show()
                .addClass('is-visible');

            if (type === 'success') {
                setTimeout(function () {
                    $target.removeClass('is-visible');
                }, 5000);
            }
        }
    };
});
