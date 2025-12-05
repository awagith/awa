define(function () {
    'use strict';

    var isLoaded = false;

    return function (config) {
        var scriptUrl = config && config.scriptUrl,
            settings = config && config.settings;

        if (isLoaded || !scriptUrl) {
            return;
        }

        if (settings) {
            window.addthis_config = settings;
        }

        var script = document.createElement('script');
        script.src = scriptUrl;
        script.async = true;

        document.head.appendChild(script);

        isLoaded = true;
    };
});