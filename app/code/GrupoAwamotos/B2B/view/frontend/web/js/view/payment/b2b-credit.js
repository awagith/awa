/**
 * B2B Credit Payment Method Registration
 */
define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push({
        type: 'b2b_credit',
        component: 'GrupoAwamotos_B2B/js/view/payment/method-renderer/b2b-credit'
    });

    return Component.extend({});
});
