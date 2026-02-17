define([], function () {
    'use strict';

    return function (config) {
        window.cookiesConfig = window.cookiesConfig || {};
        window.cookiesConfig.secure = !!(config && config.secure);
    };
});
