/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'mage/smart-keyboard-handler',
    'mage/mage',
    'domReady!'
], function ($, keyboardHandler) {
    'use strict';

    if ($('body').hasClass('checkout-cart-index')) {
        if ($('#co-shipping-method-form .fieldset.rates').length > 0 && $('#co-shipping-method-form .fieldset.rates :checked').length === 0) {
            $('#block-shipping').on('collapsiblecreate', function () {
                $('#block-shipping').collapsible('forceActivate');
            });
        }
    }

    $('.cart-summary').mage('sticky', {
        container: '#maincontent'
    });

    $('.panel.header > .header.links').clone().appendTo('#store\\.links');

    function applyAwaPublicHotfix(root) {
        var scope = root && root.querySelectorAll ? root : document;

        scope.querySelectorAll('.contact-index-index h1, .contact-index-index h2, .contact-index-index h3, .contact-index-index button, .contact-index-index .action.submit').forEach(function (el) {
            var text = (el.textContent || '').trim();
            if (text === 'Drop Us A Message') {
                el.textContent = 'Envie sua mensagem';
            } else if (text === 'Send Message' || text === 'Send message') {
                el.textContent = 'Enviar mensagem';
            }
        });

        scope.querySelectorAll('.contact-index-index input[placeholder], .contact-index-index textarea[placeholder]').forEach(function (el) {
            var placeholder = (el.getAttribute('placeholder') || '').trim();
            if (placeholder === "What's on your mind?") {
                el.setAttribute('placeholder', 'Como podemos ajudar?');
            } else if (placeholder === 'Phone Number') {
                el.setAttribute('placeholder', 'Telefone');
            } else if (placeholder === 'Your Message') {
                el.setAttribute('placeholder', 'Sua mensagem');
            }
        });

        scope.querySelectorAll('.cms-page-view h2, .cms-page-view h3, .cms-page-view h4').forEach(function (heading) {
            var txt = (heading.textContent || '').trim();
            if (txt && /^\?{2,}/.test(txt)) {
                heading.textContent = txt.replace(/^\?+\s*/, '').trim();
            }
        });

        scope.querySelectorAll('a[href]').forEach(function (anchor) {
            var hrefAttr = anchor.getAttribute('href');
            if (!hrefAttr) return;

            var href = hrefAttr.trim();
            if (!/\/ofertas\/?($|[?#])/i.test(href)) return;

            try {
                var url = new URL(href, window.location.origin);
                var path = (url.pathname || '').replace(/\/+$/, '').toLowerCase();
                if (path !== '/ofertas') return;

                url.pathname = '/ofertas.html';
                var normalized = /^\//.test(href) && !/^https?:\/\//i.test(href)
                    ? (url.pathname + url.search + url.hash)
                    : url.toString();

                anchor.setAttribute('href', normalized);
            } catch (e) {
                // noop
            }
        });
    }

    applyAwaPublicHotfix(document);

    if (window.MutationObserver) {
        var observerTarget = document.querySelector('.page-wrapper') || document.body;
        if (observerTarget) {
            var hotfixObserver = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (node && node.nodeType === 1) {
                            applyAwaPublicHotfix(node);
                        }
                    });
                });
            });

            hotfixObserver.observe(observerTarget, {
                childList: true,
                subtree: true
            });
        }
    }

    keyboardHandler.apply();
});
