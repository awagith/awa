/**
 * PO Number Storage Model
 * Stores PO number value for checkout payment submission
 *
 * @module GrupoAwamotos_B2B/js/model/checkout/po-number-storage
 */
define([
    'ko'
], function (ko) {
    'use strict';

    var poNumber = ko.observable('');

    return {
        /**
         * Get current PO number
         * @returns {string}
         */
        getPoNumber: function () {
            return poNumber();
        },

        /**
         * Set PO number
         * @param {string} value
         */
        setPoNumber: function (value) {
            poNumber(value);
        },

        /**
         * Observable for PO number
         * @returns {ko.observable}
         */
        poNumberObservable: poNumber,

        /**
         * Clear PO number
         */
        clear: function () {
            poNumber('');
        },

        /**
         * Check if PO number is set
         * @returns {boolean}
         */
        hasPoNumber: function () {
            return poNumber() !== '' && poNumber() !== null;
        }
    };
});
