/**
 * Payment Information Mixin
 * Adds PO Number to payment extension attributes before submission
 *
 * @module GrupoAwamotos_B2B/js/model/payment/po-number-assigner
 */
define([
    'jquery',
    'mage/utils/wrapper',
    'GrupoAwamotos_B2B/js/model/checkout/po-number-storage'
], function ($, wrapper, poNumberStorage) {
    'use strict';

    return function (paymentInformationHandler) {
        return wrapper.wrap(paymentInformationHandler, function (originalAction, messageContainer, paymentData, billingAddress) {
            var poNumber = poNumberStorage.getPoNumber();

            if (poNumber && poNumber.trim() !== '') {
                // Ensure extension_attributes exists
                if (!paymentData.extension_attributes) {
                    paymentData.extension_attributes = {};
                }

                // Add PO Number to payment data
                paymentData.extension_attributes.b2b_po_number = poNumber.trim();
            }

            return originalAction(messageContainer, paymentData, billingAddress);
        });
    };
});
