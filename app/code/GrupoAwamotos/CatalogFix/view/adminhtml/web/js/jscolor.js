/**
 * jscolor.js — AWA Motos stub (HTML5-native color picker)
 *
 * Replaces the original jscolor.js library (which was served from the missing
 * pub/media/js/ directory) with a lightweight HTML5 native color input bridge.
 *
 * For each input with class "jscolor", appends a native <input type="color">
 * swatch that stays in sync with the text input value.
 *
 * @license MIT — AWA Motos / GrupoAwamotos
 */
(function () {
    'use strict';

    /**
     * Normalize a value to a valid 6-digit hex color string with leading #.
     * Returns '#000000' if the value is not recognized.
     *
     * @param {string} value
     * @returns {string}
     */
    function toHex6(value) {
        var v = String(value || '').trim();
        if (/^#?[0-9a-fA-F]{6}$/.test(v)) {
            return '#' + v.replace('#', '');
        }
        if (/^#?[0-9a-fA-F]{3}$/.test(v)) {
            var s = v.replace('#', '');
            return '#' + s[0] + s[0] + s[1] + s[1] + s[2] + s[2];
        }
        return '#000000';
    }

    /**
     * Attach a native color picker swatch next to a jscolor text input.
     *
     * @param {HTMLInputElement} input
     */
    function attachSwatch(input) {
        if (input.dataset.jscInit) {
            return;
        }
        input.dataset.jscInit = '1';

        var swatch = document.createElement('input');
        swatch.type = 'color';
        swatch.className = 'jscolor-native-swatch';
        swatch.title = 'Escolher cor';
        swatch.value = toHex6(input.value);

        /* swatch → text input */
        swatch.addEventListener('input', function () {
            input.value = swatch.value.replace('#', '').toUpperCase();
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });

        /* text input → swatch (live typing) */
        input.addEventListener('input', function () {
            var hex = toHex6(input.value);
            if (hex !== '#000000' || /^#?000000$/i.test(input.value)) {
                swatch.value = hex;
            }
        });

        if (input.parentNode) {
            input.parentNode.insertBefore(swatch, input.nextSibling);
        }
    }

    /**
     * Initialize all .jscolor inputs already in the DOM, then watch for new ones
     * added dynamically (e.g. via AJAX section load).
     */
    function init() {
        /* existing inputs */
        document.querySelectorAll('input.jscolor').forEach(attachSwatch);

        /* dynamic inputs — MutationObserver */
        if (typeof MutationObserver !== 'undefined') {
            new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (node.nodeType !== 1) { return; }
                        if (node.matches && node.matches('input.jscolor')) {
                            attachSwatch(/** @type {HTMLInputElement} */ (node));
                        }
                        node.querySelectorAll &&
                            node.querySelectorAll('input.jscolor').forEach(attachSwatch);
                    });
                });
            }).observe(document.body, { childList: true, subtree: true });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
