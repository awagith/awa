define([
    'jquery'
], function ($) {
    'use strict';

    const OBSERVER_KEY = '__awaB2bCheckoutCompatObserver';
    let scheduled = false;

    function inScope() {
        const body = document.body;

        if (!body) {
            return false;
        }

        return body.classList.contains('checkout-cart-index') ||
            body.classList.contains('checkout-index-index') ||
            body.classList.contains('rokanthemes-onepagecheckout');
    }

    function visible(el) {
        return !!(el && (el.offsetWidth || el.offsetHeight || el.getClientRects().length));
    }

    function setLabel($el, text) {
        if (!$el.length || !text) {
            return;
        }

        if (!$el.attr('title')) {
            $el.attr('title', text);
        }

        if (!$el.attr('aria-label')) {
            $el.attr('aria-label', text);
        }
    }

    function setComponent($els, name) {
        $els.each(function () {
            var $el = $(this);

            if (!$el.attr('data-awa-component')) {
                $el.attr('data-awa-component', name);
            }
        });
    }

    function decorateCart() {
        const $body = $(document.body);
        const $summary = $('.cart-summary');
        const $container = $('.cart-container');

        if (!$body.hasClass('checkout-cart-index')) {
            return;
        }

        setComponent($container, 'cart-container');
        setComponent($summary, 'cart-summary');
        setComponent($('.cart.table-wrapper'), 'cart-table');
        setComponent($('.cart-summary .discount.coupon, .cart-container .fieldset.coupon'), 'cart-coupon');

        $('.checkout-methods-items .action.checkout, .cart-summary .action.checkout').each(function () {
            setLabel($(this), 'Ir para checkout');
        });

        $('.cart-summary .action.multicheckout').each(function () {
            setLabel($(this), 'Finalizar com múltiplos endereços');
        });

        $('.cart.table-wrapper .actions-toolbar .action-delete, .cart.table-wrapper .action-delete').each(function () {
            setLabel($(this), 'Remover item do carrinho');
        });

        $('.cart.table-wrapper input.qty').each(function () {
            var $input = $(this);
            const itemName = $.trim($input.closest('tr.item-info, .cart.item').find('.product-item-name, .item-name').first().text());
            const label = itemName ? `Quantidade para ${itemName}` : 'Quantidade do item';
            setLabel($input, label);
        });

        $('.b2b-login-modal-close, [data-b2b-login-close]').each(function () {
            setLabel($(this), 'Fechar modal de login B2B');
        });

        $('.b2b-login-option').each(function () {
            var $link = $(this);
            setLabel($link, $.trim($link.find('.b2b-login-option-title').text()) || $.trim($link.text()));
        });
    }

    function decoratePoNumber() {
        $('.b2b-po-number-container').each(function () {
            var $box = $(this);
            const $input = $box.find('input[name="b2b_po_number"]').first();
            const $note = $box.find('.field-note .note').first();
            let noteId;

            setComponent($box, 'b2b-po-number');
            $box.attr('data-awa-initialized', 'true');

            if ($input.length) {
                setLabel($input, 'Número do Pedido de Compra');
                $input.attr('autocomplete', 'off');

                if ($note.length) {
                    noteId = $note.attr('id') || 'awa-b2b-po-note';
                    $note.attr('id', noteId);
                    $input.attr('aria-describedby', noteId);
                }
            }
        });
    }

    function decorateTerms() {
        $('.b2b-terms-container').each(function () {
            var $box = $(this);
            const $checkbox = $box.find('#b2b-terms-checkbox, input[name="b2b_terms_accepted"]').first();
            const $link = $box.find('.b2b-terms-link').first();
            const $status = $box.find('.b2b-terms-status').first();
            const accepted = $checkbox.length ? !!$checkbox.prop('checked') : false;

            setComponent($box, 'b2b-terms');
            $box.attr('data-awa-initialized', 'true')
                .toggleClass('is-accepted', accepted);

            if ($checkbox.length) {
                setLabel($checkbox, 'Aceitar termos de venda B2B');
            }

            if ($link.length) {
                setLabel($link, 'Abrir termos e condições de venda B2B');
            }

            if ($status.length) {
                $status.attr('aria-live', 'polite');
            }
        });

        $('.b2b-terms-modal-overlay').each(function () {
            var $overlay = $(this);
            const $modal = $overlay.find('.b2b-terms-modal').first();
            const isOpen = visible(this);

            $overlay.toggleClass('is-open', isOpen)
                .attr('aria-hidden', isOpen ? 'false' : 'true');

            if ($modal.length) {
                $modal.attr('role', 'dialog').attr('aria-modal', 'true');
            }
        });

        $('.b2b-terms-modal-close').each(function () {
            setLabel($(this), 'Fechar termos B2B');
        });

        $('.b2b-terms-modal-footer .action.primary').each(function () {
            setLabel($(this), 'Aceitar termos de venda B2B');
        });

        $('.b2b-terms-modal-footer .action.secondary').each(function () {
            setLabel($(this), 'Fechar modal de termos');
        });
    }

    function decorateCheckout() {
        const $body = $(document.body);

        if (!$body.hasClass('checkout-index-index') && !$body.hasClass('rokanthemes-onepagecheckout')) {
            return;
        }

        setComponent($('.checkout-container'), 'checkout-container');
        setComponent($('.opc-wrapper'), 'checkout-main');
        setComponent($('#opc-sidebar, .opc-sidebar, .opc-block-summary'), 'checkout-summary');
        setComponent($('.payment-method'), 'checkout-payment-method');
        setComponent($('.opc-payment'), 'checkout-payment-list');

        $('.opc-wrapper .step-title, .checkout-payment-method .step-title').each(function () {
            var $title = $(this);
            const txt = $.trim($title.text());
            if (txt && !$title.attr('title')) {
                $title.attr('title', txt);
            }
        });

        $('.payment-method-title .label, .payment-method-title label').each(function () {
            var $label = $(this);
            const methodTitle = $.trim($label.text());
            if (methodTitle) {
                $label.attr('data-awa-payment-label', '1');
                setLabel($label, methodTitle);
            }
        });

        $('.actions-toolbar .btn-placeorder, .actions-toolbar .action.checkout').each(function () {
            setLabel($(this), 'Finalizar pedido');
        });

        $('.opc-block-summary input, .opc-wrapper input, .opc-wrapper select, .opc-wrapper textarea').each(function () {
            var $field = $(this);
            if (!$field.attr('aria-label') && !$field.attr('aria-labelledby')) {
                const labelText = $.trim($field.closest('.field').find('label .label, label').first().text());
                if (labelText) {
                    $field.attr('aria-label', labelText);
                }
            }
        });

        decoratePoNumber();
        decorateTerms();
    }

    function decorate() {
        if (!inScope()) {
            return;
        }

        decorateCart();
        decorateCheckout();
    }

    function scheduleDecorate() {
        if (scheduled) {
            return;
        }

        scheduled = true;

        const flush = () => {
            scheduled = false;
            decorate();
        };

        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(flush);
            return;
        }

        window.setTimeout(flush, 0);
    }

    return function initAwaB2bCartCheckoutCompat() {
        if (!inScope()) {
            return;
        }

        decorate();

        if (window.MutationObserver && !window[OBSERVER_KEY]) {
            window[OBSERVER_KEY] = new window.MutationObserver(() => {
                scheduleDecorate();
            });

            if (!document.body) {
                return;
            }

            window[OBSERVER_KEY].observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    };
});
