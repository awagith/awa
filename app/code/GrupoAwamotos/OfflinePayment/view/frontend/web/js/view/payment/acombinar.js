define([
    'uiComponent',
    'Magento_Checkout/js/model/payment/renderer-list'
], function (Component, rendererList) {
    'use strict';

    rendererList.push({
        type: 'acombinar',
        component: 'GrupoAwamotos_OfflinePayment/js/view/payment/method-renderer/acombinar-method'
    });

    return Component.extend({});
});
