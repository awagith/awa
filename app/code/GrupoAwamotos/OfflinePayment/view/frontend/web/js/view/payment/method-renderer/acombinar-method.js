define([
    'Magento_Checkout/js/view/payment/default',
    'jquery',
    'mage/translate'
], function (Component, $, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'GrupoAwamotos_OfflinePayment/payment/acombinar'
        },

        /**
         * Get payment method code
         * @returns {string}
         */
        getCode: function () {
            return 'acombinar';
        },

        /**
         * Check if payment method is active
         * @returns {boolean}
         */
        isActive: function () {
            return true;
        },

        /**
         * Get payment instructions
         * @returns {string}
         */
        getInstructions: function () {
            return window.checkoutConfig.payment.instructions ?
                window.checkoutConfig.payment.instructions[this.getCode()] :
                $t('O pagamento será combinado diretamente com nossa equipe.');
        },

        /**
         * Get payment title
         * @returns {string}
         */
        getTitle: function () {
            return $t('A Combinar');
        }
    });
});
