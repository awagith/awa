define([], function () {
    'use strict';

    var PDP_ICON_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false">'
        + '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>'
        + '<polyline points="16 17 21 12 16 7"></polyline>'
        + '<line x1="21" y1="12" x2="9" y2="12"></line>'
        + '</svg>';

    var PENDING_ICON_SVG = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false">'
        + '<circle cx="12" cy="12" r="10"></circle>'
        + '<polyline points="12 6 12 12 16 14"></polyline>'
        + '</svg>';

    function isElementVisible(el) {
        return !!(el && (el.offsetWidth || el.offsetHeight || el.getClientRects().length));
    }

    function createLoginButton(options) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'b2b-login-to-buy-btn' + (options && options.variantClass ? (' ' + options.variantClass) : '');
        btn.innerHTML = (options && options.html) ? options.html : (options && options.text ? options.text : 'Entrar para Comprar');
        if (options && options.disabled) {
            btn.disabled = true;
            btn.classList.add('b2b--disabled');
        }
        return btn;
    }

    function init(config) {
        if (!config) {
            return;
        }

        // Determine mode from server: 'guest' or 'pending'
        var mode = config.mode || 'guest';
        var isGuestMode = (mode === 'guest');
        var isPendingMode = (mode === 'pending');
        var bodyClass = isGuestMode ? 'b2b-guest-mode' : 'b2b-pending-mode';

        var overlay = document.getElementById('b2b-login-modal');
        var pendingBanner = document.getElementById('b2b-pending-banner');
        var dialog = overlay ? overlay.querySelector('.b2b-login-modal') : null;
        var closeBtn = overlay ? overlay.querySelector('[data-b2b-login-close]') : null;
        var lastActiveElement = null;
        var lastTriggerButton = null;
        var previousBodyOverflow = null;

        // Show pending banner if in pending mode
        if (isPendingMode && pendingBanner) {
            pendingBanner.style.display = '';
        }

        function isModalOpen() {
            return overlay && overlay.classList.contains('active');
        }

        function getFocusableElements() {
            if (!dialog) {
                return [];
            }

            var focusables = Array.prototype.slice.call(
                dialog.querySelectorAll(
                    'a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
                )
            );

            return focusables.filter(function (el) {
                return isElementVisible(el);
            });
        }

        function openModal(triggerEl) {
            if (!overlay || !isGuestMode || isModalOpen()) {
                return;
            }
            lastActiveElement = document.activeElement;
            lastTriggerButton = triggerEl || lastActiveElement;
            if (lastTriggerButton && typeof lastTriggerButton.setAttribute === 'function') {
                lastTriggerButton.setAttribute('aria-expanded', 'true');
            }
            overlay.classList.add('active');
            overlay.setAttribute('aria-hidden', 'false');

            if (previousBodyOverflow === null) {
                previousBodyOverflow = document.body.style.overflow;
            }
            document.body.style.overflow = 'hidden';

            window.setTimeout(function () {
                var focusables = getFocusableElements();
                if (focusables.length) {
                    focusables[0].focus();
                } else if (dialog) {
                    dialog.focus();
                }
            }, 0);
        }

        function closeModal() {
            if (!overlay) {
                return;
            }
            overlay.classList.remove('active');
            overlay.setAttribute('aria-hidden', 'true');

            document.body.style.overflow = previousBodyOverflow !== null ? previousBodyOverflow : '';
            previousBodyOverflow = null;

            if (lastTriggerButton && typeof lastTriggerButton.setAttribute === 'function') {
                lastTriggerButton.setAttribute('aria-expanded', 'false');
            }
            lastTriggerButton = null;

            if (lastActiveElement && typeof lastActiveElement.focus === 'function') {
                lastActiveElement.focus();
            }
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                closeModal();
            });
        }

        if (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) {
                    closeModal();
                }
            });
        }

        document.addEventListener('keydown', function (e) {
            if (!isModalOpen()) {
                return;
            }

            if (e.key === 'Escape') {
                closeModal();
                return;
            }

            if (e.key !== 'Tab') {
                return;
            }

            var focusables = getFocusableElements();
            if (!focusables.length) {
                e.preventDefault();
                return;
            }

            var first = focusables[0];
            var last = focusables[focusables.length - 1];
            var isShiftPressed = e.shiftKey;
            var currentActiveElement = document.activeElement;

            if (isShiftPressed) {
                if (currentActiveElement === first) {
                    e.preventDefault();
                    last.focus();
                }
            } else {
                if (currentActiveElement === last) {
                    e.preventDefault();
                    first.focus();
                }
            }
        });

        function replaceAddToCartButtons() {
            // Add the appropriate body class
            document.body.classList.add(bodyClass);
            // Also add a generic class for shared CSS rules
            document.body.classList.add('b2b-restricted-mode');

            var iconSvg = isGuestMode ? PDP_ICON_SVG : PENDING_ICON_SVG;

            // PDP (product detail page)
            var productAddForm = document.querySelector('.product-add-form');
            if (productAddForm) {
                var addToCartBtn = productAddForm.querySelector('button.tocart, button#product-addtocart-button');
                if (addToCartBtn && !productAddForm.querySelector('.b2b-login-to-buy-btn')) {
                    addToCartBtn.setAttribute('data-b2b-original-hidden', '1');
                    addToCartBtn.style.display = 'none';

                    var pdpBtn = createLoginButton({
                        html: iconSvg + ' ' + ((config && config.pdpButtonText) ? config.pdpButtonText : 'Entrar para Comprar'),
                        disabled: isPendingMode
                    });
                    pdpBtn.setAttribute('data-b2b-injected', '1');

                    if (isGuestMode) {
                        pdpBtn.setAttribute('aria-haspopup', 'dialog');
                        pdpBtn.setAttribute('aria-controls', 'b2b-login-modal');
                        pdpBtn.addEventListener('click', function (e) {
                            openModal(e.currentTarget);
                        });
                    }

                    addToCartBtn.parentNode.insertBefore(pdpBtn, addToCartBtn.nextSibling);
                }
            }

            // Product listings (category, search, widgets)
            document.querySelectorAll('.product-item-actions .actions-primary, .product-info-cart .actions-primary').forEach(function (actionsContainer) {
                var addBtn = actionsContainer.querySelector('button.tocart, form button.tocart');
                if (addBtn && !actionsContainer.querySelector('.b2b-login-to-buy-btn')) {
                    addBtn.setAttribute('data-b2b-original-hidden', '1');
                    addBtn.style.display = 'none';

                    var listingBtn = createLoginButton({
                        text: (config && config.listingButtonText) ? config.listingButtonText : 'Entrar para Comprar',
                        variantClass: 'b2b--listing',
                        disabled: isPendingMode
                    });
                    listingBtn.setAttribute('data-b2b-injected', '1');

                    if (isGuestMode) {
                        listingBtn.setAttribute('aria-haspopup', 'dialog');
                        listingBtn.setAttribute('aria-controls', 'b2b-login-modal');
                        listingBtn.addEventListener('click', function (e) {
                            openModal(e.currentTarget);
                        });
                    }

                    actionsContainer.appendChild(listingBtn);
                }
            });
        }

        // Initial run
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', replaceAddToCartButtons);
        } else {
            replaceAddToCartButtons();
        }

        // Re-run with throttle on DOM changes
        var scheduled = false;
        function scheduleReplace() {
            if (scheduled) {
                return;
            }
            scheduled = true;
            window.setTimeout(function () {
                scheduled = false;
                replaceAddToCartButtons();
            }, 120);
        }

        var observer = new MutationObserver(function (mutations) {
            for (var i = 0; i < mutations.length; i++) {
                if (mutations[i].addedNodes && mutations[i].addedNodes.length) {
                    scheduleReplace();
                    break;
                }
            }
        });

        observer.observe(document.body, {childList: true, subtree: true});

        // Re-run when customer-data updates
        if (typeof require === 'function') {
            require(['Magento_Customer/js/customer-data'], function (customerData) {
                try {
                    customerData.get('customer').subscribe(function () {
                        scheduleReplace();
                    });
                } catch (e) {
                    // ignore
                }
            });
        }
    }

    return init;
});
