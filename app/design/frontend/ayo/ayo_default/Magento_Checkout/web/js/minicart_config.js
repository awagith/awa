define(function () {
    'use strict';

    return function (config) {
        var checkoutConfig = config && config.checkoutConfig ? config.checkoutConfig : {};

        window.checkout = checkoutConfig;
    };
});