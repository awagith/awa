/**
 * B2B Credit Payment Method Renderer
 */
define([
    'Magento_Checkout/js/view/payment/default'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'GrupoAwamotos_B2B/payment/b2b_credit'
        },

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
        }
    });
});
