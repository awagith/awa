(function () {
    'use strict';

    if (window.__awaRound3PdpStickyCtaInit) {
        return;
    }
    window.__awaRound3PdpStickyCtaInit = true;

    const MOBILE_QUERY = '(max-width: 767px)';
    const REDUCED_MOTION_QUERY = '(prefers-reduced-motion: reduce)';
    const INVALID_STICKY_LABEL_RE = /\b(entrar|login|cadastro|acessar)\b/i;
    let stickyStarted = false;
    let deferredRetryBound = false;
    let deferredRetryObserver = null;
    let deferredRetryIntervalId = null;
    let deferredRetryTimeoutId = null;
    const SELECTORS = {
        addToCart: '#product-addtocart-button',
        addToCartForm: '#product_addtocart_form',
        b2bPriceGate: '.product-info-main .b2b-login-to-see-price',
        b2bLoginButton: '.product-add-form .b2b-login-to-buy-btn',
        b2bPendingBanner: '#b2b-pending-banner',
        media: '.product.col.media, .product.media, .column.main .fotorama__stage, .gallery-placeholder',
        price: '.product-info-main .price-box .price-final_price .price, .product-info-main .price-box .price',
        productInfoMain: '.product-info-main'
    };

    function setAttrIfMissing(el, name, value) {
        if (el && !el.getAttribute(name)) {
            el.setAttribute(name, value);
        }
    }

    function isVisible(el) {
        if (!el) {
            return false;
        }
        if (el.hidden || el.getAttribute('aria-hidden') === 'true') {
            return false;
        }
        return !!(el.offsetWidth || el.offsetHeight || (el.getClientRects && el.getClientRects().length));
    }

    function prefersReducedMotion() {
        return !!(window.matchMedia && window.matchMedia(REDUCED_MOTION_QUERY).matches);
    }

    function getButtonLabel(button) {
        return button ? (button.textContent || '').replace(/\s+/g, ' ').trim() : '';
    }

    function clearDeferredRetry() {
        if (deferredRetryObserver) {
            deferredRetryObserver.disconnect();
            deferredRetryObserver = null;
        }

        if (deferredRetryIntervalId) {
            window.clearInterval(deferredRetryIntervalId);
            deferredRetryIntervalId = null;
        }

        if (deferredRetryTimeoutId) {
            window.clearTimeout(deferredRetryTimeoutId);
            deferredRetryTimeoutId = null;
        }

        deferredRetryBound = false;
    }

    function resolveAddToCartButton() {
        const button = document.querySelector(SELECTORS.addToCart);

        if (!button) {
            return null;
        }

        if (button.getAttribute('data-b2b-original-hidden') === '1') {
            return null;
        }

        const form = button.form || button.closest(SELECTORS.addToCartForm);
        if (!form || form.id !== 'product_addtocart_form') {
            return null;
        }

        return button;
    }

    function hasValidCartAction(form) {
        if (!form || !form.getAttribute) {
            return false;
        }

        const action = form.getAttribute('action') || '';
        if (!action) {
            return false;
        }

        return /\/checkout\/cart\/add\/?/i.test(action);
    }

    function hasVisibleB2bPriceGate() {
        const gate = document.querySelector(SELECTORS.b2bPriceGate);
        return isVisible(gate);
    }

    function hasVisibleB2bLoginReplacement() {
        const replacement = document.querySelector(SELECTORS.b2bLoginButton);
        return isVisible(replacement);
    }

    function hasVisiblePendingBanner() {
        const pendingBanner = document.querySelector(SELECTORS.b2bPendingBanner);
        return isVisible(pendingBanner);
    }

    function hasRestrictedB2bBodyState() {
        const body = document.body;

        if (!body) {
            return false;
        }

        return body.classList.contains('b2b-restricted-mode') ||
            body.classList.contains('b2b-pending-mode');
    }

    function isRestrictedB2bContext(button) {
        const form = button ? (button.form || button.closest(SELECTORS.addToCartForm)) : null;
        const gateVisible = hasVisibleB2bPriceGate();

        if (hasRestrictedB2bBodyState()) {
            return true;
        }

        if (hasVisibleB2bLoginReplacement() || hasVisiblePendingBanner()) {
            return true;
        }

        if (!form || !hasValidCartAction(form)) {
            return true;
        }

        if (gateVisible) {
            return true;
        }

        return false;
    }

    function isStickyCapableAddToCartButton(button) {
        if (!button) {
            return false;
        }

        const form = button.form || button.closest(SELECTORS.addToCartForm);
        if (!form || form.id !== 'product_addtocart_form') {
            return false;
        }

        if (!hasValidCartAction(form)) {
            return false;
        }

        if (!isVisible(button)) {
            return false;
        }

        const label = getButtonLabel(button);
        if (label && INVALID_STICKY_LABEL_RE.test(label)) {
            return false;
        }

        if (isRestrictedB2bContext(button)) {
            return false;
        }

        return true;
    }

    function isActionableAddToCartButton(button) {
        if (!isStickyCapableAddToCartButton(button)) {
            return false;
        }

        if (button.disabled || button.getAttribute('aria-disabled') === 'true') {
            return false;
        }

        if (button.classList && button.classList.contains('disabled')) {
            return false;
        }

        return true;
    }

    function enhanceQtyControls() {
        const qtyInput = document.getElementById('qty');
        const qtyUp = document.querySelector('.info-qty .qty-up');
        const qtyDown = document.querySelector('.info-qty .qty-down');

        if (qtyInput) {
            setAttrIfMissing(qtyInput, 'inputmode', 'numeric');
            setAttrIfMissing(qtyInput, 'pattern', '[0-9]*');
            setAttrIfMissing(qtyInput, 'min', '1');
            setAttrIfMissing(qtyInput, 'aria-label', 'Quantidade');
            setAttrIfMissing(qtyInput, 'title', 'Quantidade');
        }

        if (qtyUp) {
            qtyUp.setAttribute('role', 'button');
            setAttrIfMissing(qtyUp, 'aria-label', 'Aumentar quantidade');
            setAttrIfMissing(qtyUp, 'title', 'Aumentar quantidade');
        }

        if (qtyDown) {
            qtyDown.setAttribute('role', 'button');
            setAttrIfMissing(qtyDown, 'aria-label', 'Diminuir quantidade');
            setAttrIfMissing(qtyDown, 'title', 'Diminuir quantidade');
        }
    }

    function getMediaSentinel() {
        const nodes = document.querySelectorAll(SELECTORS.media);
        for (let i = 0; i < nodes.length; i += 1) {
            const rect = nodes[i].getBoundingClientRect();
            if (rect.width > 0 && rect.height > 80) {
                return nodes[i];
            }
        }
        return null;
    }

    function getPriceText() {
        const priceNode = document.querySelector(SELECTORS.price);
        return priceNode ? (priceNode.textContent || '').trim() : '';
    }

    function createStickyUi(getButton) {
        const bar = document.createElement('div');
        bar.className = 'awa-pdp-sticky-cta';
        bar.setAttribute('aria-hidden', 'true');
        bar.innerHTML = '' +
            '<div class="awa-pdp-sticky-cta__inner" role="region" aria-label="Atalho de compra do produto">' +
                '<div class="awa-pdp-sticky-cta__meta">' +
                    '<span class="awa-pdp-sticky-cta__label">Comprar agora</span>' +
                    '<span class="awa-pdp-sticky-cta__price"></span>' +
                '</div>' +
                '<button type="button" class="awa-pdp-sticky-cta__button" title="Comprar" aria-label="Comprar">Comprar</button>' +
            '</div>';

        document.body.appendChild(bar);

        const stickyButton = bar.querySelector('.awa-pdp-sticky-cta__button');
        const stickyPrice = bar.querySelector('.awa-pdp-sticky-cta__price');

        function getLiveButton() {
            if (typeof getButton !== 'function') {
                return null;
            }
            return getButton();
        }

        stickyButton.addEventListener('click', () => {
            const behavior = prefersReducedMotion() ? 'auto' : 'smooth';
            const button = getLiveButton();

            if (!button) {
                return;
            }

            if (!isActionableAddToCartButton(button)) {
                button.focus();
                return;
            }

            try {
                button.scrollIntoView({ block: 'center', behavior: behavior });
            } catch (e) {
                button.scrollIntoView();
            }

            window.setTimeout(() => {
                button.click();
            }, prefersReducedMotion() ? 0 : 120);
        });

        function syncFromOriginal() {
            const button = getLiveButton();
            const label = getButtonLabel(button) || 'Comprar';
            const priceText = getPriceText();
            const canAct = isActionableAddToCartButton(button);

            stickyButton.textContent = label;
            stickyButton.title = label;
            stickyButton.setAttribute('aria-label', label);
            stickyButton.disabled = !canAct;
            bar.classList.toggle('awa-pdp-sticky-cta--disabled', !canAct);
            stickyPrice.classList.toggle('awa-pdp-sticky-cta__price--muted', !priceText);
            stickyPrice.textContent = priceText || 'Confira condições';
        }

        syncFromOriginal();

        if (window.MutationObserver) {
            const observeTarget = document.querySelector(SELECTORS.productInfoMain) || document.body;

            new MutationObserver(syncFromOriginal).observe(observeTarget, {
                childList: true,
                subtree: true,
                attributes: true,
                characterData: true
            });
        }

        return {
            root: bar,
            sync: syncFromOriginal
        };
    }

    function init() {
        if (!document.body || !document.body.classList.contains('catalog-product-view')) {
            return;
        }

        if (stickyStarted) {
            return;
        }

        enhanceQtyControls();

        let addToCartButton = resolveAddToCartButton();
        const productInfoMain = document.querySelector(SELECTORS.productInfoMain);

        if (!addToCartButton || !productInfoMain || !isStickyCapableAddToCartButton(addToCartButton)) {
            scheduleDeferredInit();
            return;
        }

        const mediaSentinel = getMediaSentinel();
        if (!mediaSentinel) {
            scheduleDeferredInit();
            return;
        }

        if (document.querySelector('.awa-pdp-sticky-cta')) {
            stickyStarted = true;
            clearDeferredRetry();
            return;
        }

        clearDeferredRetry();
        stickyStarted = true;

        function getLiveAddToCartButton() {
            let liveButton = resolveAddToCartButton();

            if (liveButton && liveButton !== addToCartButton) {
                addToCartButton = liveButton;
            }

            return addToCartButton;
        }

        const mq = window.matchMedia ? window.matchMedia(MOBILE_QUERY) : null;
        const sticky = createStickyUi(getLiveAddToCartButton);
        const body = document.body;

        function shouldShowSticky() {
            const liveButton = getLiveAddToCartButton();

            if (mq && !mq.matches) {
                return false;
            }
            if (!liveButton) {
                return false;
            }
            if (liveButton.closest('.awa-pdp-sticky-cta')) {
                return false;
            }
            if (!document.contains(liveButton)) {
                return false;
            }
            if (isRestrictedB2bContext(liveButton)) {
                return false;
            }
            return isActionableAddToCartButton(liveButton);
        }

        function setVisible(isVisible) {
            body.classList.toggle('awa-pdp-sticky-cta-visible', !!isVisible);
            body.classList.toggle('awa-pdp-sticky-cta-ready', shouldShowSticky());
            sticky.root.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
        }

        if (window.IntersectionObserver) {
            const observer = new IntersectionObserver((entries) => {
                const entry = entries[0];
                if (!document.contains(getLiveAddToCartButton())) {
                    setVisible(false);
                    return;
                }
                const visible = shouldShowSticky() && entry && !entry.isIntersecting;
                setVisible(visible);
                sticky.sync();
            }, {
                root: null,
                threshold: 0.05
            });

            observer.observe(mediaSentinel);
        } else {
            const onScroll = () => {
                if (!document.contains(getLiveAddToCartButton())) {
                    setVisible(false);
                    return;
                }
                const rect = mediaSentinel.getBoundingClientRect();
                const visible = shouldShowSticky() && rect.bottom < 0;
                setVisible(visible);
                sticky.sync();
            };

            window.addEventListener('scroll', onScroll, { passive: true });
            window.addEventListener('resize', onScroll);
            onScroll();
        }

        if (mq && mq.addEventListener) {
            mq.addEventListener('change', () => {
                sticky.sync();
                if (!mq.matches || !isStickyCapableAddToCartButton(addToCartButton)) {
                    setVisible(false);
                }
            });
        }

        window.addEventListener('resize', sticky.sync, { passive: true });
    }

    function scheduleDeferredInit() {
        if (deferredRetryBound || stickyStarted || !document.body) {
            return;
        }

        deferredRetryBound = true;

        function retryInit() {
            if (stickyStarted) {
                clearDeferredRetry();
                return;
            }
            init();
        }

        deferredRetryIntervalId = window.setInterval(retryInit, 450);
        deferredRetryTimeoutId = window.setTimeout(clearDeferredRetry, 30000);

        if (window.MutationObserver) {
            deferredRetryObserver = new MutationObserver(retryInit);
            deferredRetryObserver.observe(document.body, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class', 'style', 'disabled', 'aria-hidden']
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
