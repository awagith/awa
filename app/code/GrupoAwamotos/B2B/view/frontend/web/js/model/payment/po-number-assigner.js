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
        if (typeof paymentInformationHandler !== 'function') {
            return paymentInformationHandler;
        }

        return wrapper.wrap(paymentInformationHandler, function (originalAction) {
            var args = Array.prototype.slice.call(arguments, 1);
            var paymentData = args[1];
            var poNumber = (poNumberStorage.getPoNumber() || '').trim();

            if (poNumber && paymentData && typeof paymentData === 'object') {
                if (!paymentData.extension_attributes || typeof paymentData.extension_attributes !== 'object') {
                    paymentData.extension_attributes = {};
                }

                paymentData.extension_attributes.b2b_po_number = poNumber;
            }

            return originalAction.apply(this, args);
        });
    };
});
