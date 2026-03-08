/**
 * ERP Suggestions Widget
 *
 * Handles AJAX loading and interactions for customer purchase suggestions
 */
define([
    'jquery',
    'mage/url',
    'mage/translate',
    'jquery-ui-modules/widget',
    'mage/cookies'
], function ($, urlBuilder, $t) {
    'use strict';

    $.widget('grupoawamotos.erpSuggestions', {
        options: {
            ajaxUrl: '',
            loadingClass: 'loading',
            errorClass: 'error'
        },

        /**
         * Widget initialization
         */
        _create: function () {
            this._bindEvents();
        },

        /**
         * Bind event handlers
         */
        _bindEvents: function () {
            var self = this;

            // Refresh button click
            this.element.on('click', '.erp-refresh-btn', function (e) {
                e.preventDefault();
                self.refreshData($(this).data('type'));
            });

            // Add to cart from suggestions
            this.element.on('click', '.erp-add-to-cart', function (e) {
                e.preventDefault();
                var productId = $(this).data('product-id');
                var qty = $(this).closest('.erp-product-item').find('.erp-qty-input').val() || 1;
                self.addToCart(productId, qty);
            });

            // Quick reorder
            this.element.on('click', '.erp-quick-reorder', function (e) {
                e.preventDefault();
                var orderId = $(this).data('order-id');
                self.quickReorder(orderId);
            });
        },

        /**
         * Refresh data from ERP
         */
        refreshData: function (type) {
            var self = this;
            var $container = this.element;

            $container.addClass(this.options.loadingClass);

            $.ajax({
                url: this.options.ajaxUrl,
                type: 'GET',
                dataType: 'json',
                data: {
                    type: type || 'all'
                }
            }).done(function (response) {
                if (response.success) {
                    self._updateContent(response.data, type);
                } else {
                    self._showError(response.message || $t('Erro ao carregar dados'));
                }
            }).fail(function () {
                self._showError($t('Erro de conexão. Tente novamente.'));
            }).always(function () {
                $container.removeClass(self.options.loadingClass);
            });
        },

        /**
         * Add product to cart
         */
        addToCart: function (productId, qty) {
            var self = this;
            var addToCartUrl = urlBuilder.build('checkout/cart/add');
            var formKey = $.mage && $.mage.cookies ? $.mage.cookies.get('form_key') : '';

            $.ajax({
                url: addToCartUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    product: productId,
                    qty: qty,
                    form_key: formKey
                }
            }).done(function (response) {
                if (response.success || !response.error) {
                    $('[data-block="minicart"]').trigger('contentLoading');
                    self._showSuccess($t('Produto adicionado ao carrinho'));
                } else {
                    self._showError(response.message || $t('Não foi possível adicionar o produto ao carrinho'));
                }
            }).fail(function () {
                self._showError($t('Erro ao adicionar produto ao carrinho'));
            });
        },

        /**
         * Quick reorder - add all items from a previous order
         */
        quickReorder: function (orderId) {
            var self = this;
            var reorderUrl = this.options.ajaxUrl.replace('suggestions', 'reorder');
            var formKey = $.mage && $.mage.cookies ? $.mage.cookies.get('form_key') : '';

            $.ajax({
                url: reorderUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    order_id: orderId,
                    form_key: formKey
                }
            }).done(function (response) {
                if (response.success) {
                    window.location.href = urlBuilder.build('checkout/cart');
                } else {
                    self._showError(response.message || $t('Não foi possível refazer o pedido'));
                }
            }).fail(function () {
                self._showError($t('Erro ao processar pedido'));
            }).always(function () {
                self.element.removeClass(self.options.loadingClass);
            });
        },

        /**
         * Update widget content with fresh data from ERP
         *
         * @param {Object} data
         * @param {string} type
         */
        _updateContent: function (data, type) {
            if (data && data.html) {
                var $target = type ? this.element.find('[data-erp-section="' + type + '"]') : this.element;
                $target = $target.length ? $target : this.element;
                $target.html(data.html);
            }
        },

        /**
         * Show success message
         */
        _showSuccess: function (message) {
            this._showMessage(message, 'success');
        },

        /**
         * Show error message
         */
        _showError: function (message) {
            this._showMessage(message, 'error');
        },

        /**
         * Show message notification
         */
        _showMessage: function (message, type) {
            var $notification = $('<div/>')
                .addClass('erp-notification erp-notification-' + type)
                .text(message)
                .appendTo('body');

            window.setTimeout(function () {
                $notification.fadeOut(function () {
                    $(this).remove();
                });
            }, 3000);
        }
    });

    return $.grupoawamotos.erpSuggestions;
});
