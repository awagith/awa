define(function () {
    'use strict';

    var loaded = {
        facebook: false,
        disqus: false
    };

    function loadFacebook(config) {
        var scriptId = 'facebook-jssdk',
            firstScript;

        if (loaded.facebook || document.getElementById(scriptId)) {
            loaded.facebook = true;
            return;
        }

        firstScript = document.getElementsByTagName('script')[0];

        if (!firstScript) {
            return;
        }

        var script = document.createElement('script');
        script.id = scriptId;
        script.src = 'https://connect.facebook.net/' + (config.locale || 'en_US') + '/sdk.js#xfbml=1&version=v2.5&appId=' + (config.appId || '');

        firstScript.parentNode.insertBefore(script, firstScript);

        loaded.facebook = true;
    }

    function loadDisqus(config) {
        if (loaded.disqus) {
            return;
        }

        window.disqus_config = function () {
            this.page.url = config.pageUrl;
            this.page.identifier = config.identifier;
        };

        var script = document.createElement('script');
        script.src = 'https://' + config.shortname + '.disqus.com/embed.js';
        script.setAttribute('data-timestamp', Date.now().toString());

        (document.head || document.body).appendChild(script);

        loaded.disqus = true;
    }

    return function (config) {
        if (!config || !config.type) {
            return;
        }

        if (config.type === 'facebook') {
            loadFacebook(config);
        }

        if (config.type === 'disqus') {
            loadDisqus(config);
        }
    };
});