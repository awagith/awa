define([
    'jquery'
], function ($) {
    'use strict';

    const SEARCH_FORM_SELECTOR = 'form.form.minisearch, #search_mini_form';
    const SEARCH_BOOT_INIT_KEY = '__awaSearchCompatBootInit';
    const SEARCH_OBSERVER_KEY = '__awaSearchCompatBootObserver';
    const SEARCH_SCHEDULED_KEY = '__awaSearchCompatBootScheduled';
    const B2B_BOOT_INIT_KEY = '__awaB2bCheckoutCompatBootInit';
    const HOME_CATEGORY_BOOT_INIT_KEY = '__awaHomeCategoryCompatBootInit';

    function toBool(value) {
        return value === true || value === 1 || value === '1' || value === 'true';
    }

    function onReady(callback) {
        if (document.readyState === 'loading') {
            $(callback);
            return;
        }

        callback();
    }

    function runOnce(key, callback) {
        if (window[key]) {
            return;
        }

        window[key] = true;
        callback();
    }

    function nodeContainsSearchForm(node) {
        if (!node || node.nodeType !== 1) {
            return false;
        }

        const $node = $(node);

        return $node.is(SEARCH_FORM_SELECTOR) || $node.find(SEARCH_FORM_SELECTOR).length > 0;
    }

    function mutationsContainSearchForm(mutations) {
        if (!mutations || !mutations.length) {
            return false;
        }

        for (const mutation of mutations) {
            if (!mutation) {
                continue;
            }

            for (const node of (mutation.addedNodes || [])) {
                if (nodeContainsSearchForm(node)) {
                    return true;
                }
            }

            for (const node of (mutation.removedNodes || [])) {
                if (nodeContainsSearchForm(node)) {
                    return true;
                }
            }
        }

        return false;
    }

    function initSearchCompat() {
        runOnce(SEARCH_BOOT_INIT_KEY, () => {
            require(['js/awa-search-autocomplete-compat'], (initAwaSearchCompat) => {
                function boot() {
                    $(SEARCH_FORM_SELECTOR).each(function () {
                        initAwaSearchCompat({}, this);
                    });
                }

                function scheduleBoot() {
                    if (window[SEARCH_SCHEDULED_KEY]) {
                        return;
                    }

                    window[SEARCH_SCHEDULED_KEY] = true;
                    function flush() {
                        window[SEARCH_SCHEDULED_KEY] = false;
                        boot();
                    }

                    if (typeof window.requestAnimationFrame === 'function') {
                        window.requestAnimationFrame(flush);
                        return;
                    }

                    window.setTimeout(flush, 0);
                }

                onReady(() => {
                    scheduleBoot();

                    $(document).on('contentUpdated.awaSearchCompatBootstrap', (event) => {
                        if (!event || !event.target || nodeContainsSearchForm(event.target)) {
                            scheduleBoot();
                        }
                    });

                    if (window.MutationObserver && document.body && !window[SEARCH_OBSERVER_KEY]) {
                        window[SEARCH_OBSERVER_KEY] = new window.MutationObserver((mutations) => {
                            if (!mutationsContainSearchForm(mutations)) {
                                return;
                            }

                            scheduleBoot();
                        });

                        window[SEARCH_OBSERVER_KEY].observe(document.body, {
                            childList: true,
                            subtree: true
                        });
                    }
                });
            });
        });
    }

    function initB2bCheckoutCompat() {
        runOnce(B2B_BOOT_INIT_KEY, () => {
            require(['js/awa-custom-b2b-cart-checkout-compat'], (initAwaB2bCartCheckoutCompat) => {
                onReady(() => {
                    initAwaB2bCartCheckoutCompat();
                });
            });
        });
    }

    function initHomeCategoryCompat() {
        runOnce(HOME_CATEGORY_BOOT_INIT_KEY, () => {
            require(['js/awa-custom-home-category-compat'], (initAwaHomeCategoryCompat) => {
                onReady(() => {
                    initAwaHomeCategoryCompat();
                });
            });
        });
    }

    return function (config) {
        const options = config || {};

        if (toBool(options.load_search_compat_js)) {
            initSearchCompat();
        }

        if (toBool(options.load_b2b_checkout_compat_js)) {
            initB2bCheckoutCompat();
        }

        if (toBool(options.load_home_category_compat_js)) {
            initHomeCategoryCompat();
        }
    };
});
