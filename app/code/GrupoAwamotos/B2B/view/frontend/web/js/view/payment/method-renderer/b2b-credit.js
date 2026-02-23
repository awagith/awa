/**
 * B2B Credit Payment Method Renderer
 */
define([
    'ko',
    'Magento_Checkout/js/view/payment/default'
], function (ko, Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'GrupoAwamotos_B2B/payment/b2b_credit'
        },

        selectedPaymentTerm: ko.observable(''),

        getCode: function () {
            return 'b2b_credit';
        },

        isActive: function () {
            return true;
        },

        getMethodConfig: function () {
            var checkoutConfig = window.checkoutConfig || {};
            var paymentConfig = checkoutConfig.payment || {};

            return paymentConfig.b2b_credit || null;
        },

        getTitle: function () {
            var methodConfig = this.getMethodConfig();

            return methodConfig && methodConfig.title
                ? methodConfig.title
                : 'Crédito B2B (Faturamento)';
        },

        getCreditInfo: function () {
            var methodConfig = this.getMethodConfig();

            return methodConfig && methodConfig.credit_info
                ? methodConfig.credit_info
                : null;
        },

        /**
         * Get payment terms list from checkout config
         */
        getPaymentTerms: function () {
            var methodConfig = this.getMethodConfig();

            return methodConfig && methodConfig.payment_terms
                ? methodConfig.payment_terms
                : [];
        },

        /**
         * Whether multiple payment terms are available
         */
        hasMultipleTerms: function () {
            return this.getPaymentTerms().length > 1;
        },

        /**
         * Initialize: auto-select first term
         */
        initialize: function () {
            this._super();
            var terms = this.getPaymentTerms();
            if (terms.length === 1) {
                this.selectedPaymentTerm(terms[0].value);
            } else if (terms.length > 1 && !this.selectedPaymentTerm()) {
                this.selectedPaymentTerm(terms[0].value);
            }
            return this;
        },

        /**
         * Override getData to include selected payment term
         */
        getData: function () {
            return {
                'method': this.item.method,
                'additional_data': {
                    'payment_term': this.selectedPaymentTerm()
                }
            };
        }
    });
});
