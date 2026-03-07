(function () {
    'use strict';

    if (window.__awaRound3FooterUxInit) {
        return;
    }
    window.__awaRound3FooterUxInit = true;
    let autocompleteObserverBound = false;
    let ajaxsuiteObserverBound = false;
    let newsletterObserverBound = false;
    let ajaxsuiteGhostCloseTimer = null;
    let newsletterGhostCloseTimer = null;

    function isNodeActuallyVisible(node, minWidth, minHeight) {
        if (!node) {
            return false;
        }

        let rect;
        let style;

        try {
            rect = node.getBoundingClientRect();
            style = window.getComputedStyle(node);
        } catch (error) {
            return false;
        }

        if (!rect || rect.width < (minWidth || 1) || rect.height < (minHeight || 1)) {
            return false;
        }

        if (!style) {
            return false;
        }

        return style.display !== 'none' && style.visibility !== 'hidden' && style.opacity !== '0';
    }

    function bindKeyboardClick(node) {
        if (!node || node.dataset.awaKeyboardBound) {
            return;
        }

        node.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            node.click();
        });

        node.dataset.awaKeyboardBound = '1';
    }

    function ensureCatalogA11y(root) {
        const scope = root || document;

        scope.querySelectorAll([
            '.toolbar .modes .modes-mode',
            '.grid-mode-show-type-products a',
            '.pages .item .page',
            '.pages-item-next .action',
            '.pages-item-previous .action',
            '.filter-options-title',
            '.searchsuite-autocomplete .title .see-all',
            '.searchsuite-autocomplete .suggest a',
            '.searchsuite-autocomplete .product a',
            '.wrapper.grid.products-grid .quickview-link',
            '.wrapper.grid.products-grid .btn-add-to-cart'
        ].join(',')).forEach(function (node) {
            let text = (node.getAttribute('aria-label') || node.getAttribute('title') || node.textContent || '')
                .replace(/\s+/g, ' ')
                .trim();

            if (!text && node.classList.contains('quickview-link')) {
                text = 'Visualização rápida do produto';
            }
            if (!text && node.classList.contains('btn-add-to-cart')) {
                text = 'Adicionar ao carrinho';
            }
            if (!text) {
                return;
            }

            if (!node.getAttribute('title')) {
                node.setAttribute('title', text);
            }
            if (!node.getAttribute('aria-label')) {
                node.setAttribute('aria-label', text);
            }
        });

        scope.querySelectorAll('.filter-options-title').forEach((title) => {
            if (!title.hasAttribute('tabindex')) {
                title.setAttribute('tabindex', '0');
            }
            if (!title.hasAttribute('role')) {
                title.setAttribute('role', 'button');
            }

            bindKeyboardClick(title);
        });

        scope.querySelectorAll('.searchsuite-autocomplete [role="listbox"]').forEach((listbox, index) => {
            if (!listbox.id) {
                listbox.id = `awa-searchsuite-listbox-${index + 1}`;
            }
        });
    }

    function bindAutocompleteObserver() {
        if (autocompleteObserverBound || typeof MutationObserver === 'undefined') {
            return;
        }

        const containers = document.querySelectorAll('#search_autocomplete, #searchsuite-autocomplete');

        if (!containers.length) {
            return;
        }

        containers.forEach((container) => {
            const observer = new MutationObserver(() => {
                ensureCatalogA11y(container);
            });

            observer.observe(container, {
                childList: true,
                subtree: true,
                attributes: false
            });
        });

        autocompleteObserverBound = true;
    }

    function hasVisibleAjaxSuiteContent(shellContent) {
        if (!shellContent) {
            return false;
        }

        const innerWrapper = shellContent.querySelector('#mb-ajaxsuite-popup-wrapper');
        const authBlock = shellContent.querySelector('.block-authentication');
        const wrapperSuccess = shellContent.querySelector('.wrapper-success');
        const image = shellContent.querySelector('img');
        const form = shellContent.querySelector('form');
        const actionable = shellContent.querySelector('button, .action, a[href]');
        const text = (shellContent.textContent || '').replace(/\s+/g, ' ').trim();

        if (innerWrapper && (innerWrapper.children.length || (innerWrapper.textContent || '').replace(/\s+/g, '').length)) {
            return true;
        }

        return !!(authBlock || wrapperSuccess || image || form || actionable || text);
    }

    function closeAjaxSuiteGhostPopup() {
        const modalClose = document.querySelector('.modal-popup.ajaxsuite-popup-wrapper._show .modal-header .action-close');
        const modalOverlay = document.querySelector('.modals-overlay');
        const shell = document.getElementById('ajaxsuite-popup-wrapper');
        const shellContent = shell ? shell.querySelector('#ajaxsuite-popup-content') : null;
        const hasContent = hasVisibleAjaxSuiteContent(shellContent);

        if (hasContent) {
            return;
        }

        if (modalClose) {
            modalClose.click();
        }

        if (modalOverlay && modalOverlay.classList.contains('_show')) {
            modalOverlay.classList.remove('_show');
            modalOverlay.style.display = 'none';
        }

        if (shell) {
            shell.removeAttribute('style');
        }

        if (document.body && !document.querySelector('.modal-popup._show')) {
            document.body.classList.remove('_has-modal');
            document.body.style.overflow = '';
        }
    }

    function guardAjaxSuiteGhostPopup() {
        const shell = document.getElementById('ajaxsuite-popup-wrapper');
        const shellContent = shell ? shell.querySelector('#ajaxsuite-popup-content') : null;
        const shellClose = shell ? shell.querySelector('#ajaxsuite-close') : null;
        const modalOpen = !!document.querySelector('.modal-popup.ajaxsuite-popup-wrapper._show');
        const hasContent = hasVisibleAjaxSuiteContent(shellContent);

        if (shellClose) {
            shellClose.setAttribute('aria-hidden', 'true');
        }

        if (!modalOpen || hasContent) {
            if (ajaxsuiteGhostCloseTimer) {
                window.clearTimeout(ajaxsuiteGhostCloseTimer);
                ajaxsuiteGhostCloseTimer = null;
            }
            return;
        }

        if (ajaxsuiteGhostCloseTimer) {
            return;
        }

        ajaxsuiteGhostCloseTimer = window.setTimeout(() => {
            ajaxsuiteGhostCloseTimer = null;
            closeAjaxSuiteGhostPopup();
        }, 180);
    }

    function bindAjaxSuiteObserver() {
        if (ajaxsuiteObserverBound || typeof MutationObserver === 'undefined') {
            return;
        }

        const shellContent = document.querySelector('#ajaxsuite-popup-content');
        const modalsWrapper = document.querySelector('.modals-wrapper');

        if (!shellContent && !modalsWrapper) {
            return;
        }

        [shellContent, modalsWrapper].forEach((node) => {
            if (!node) {
                return;
            }

            const observer = new MutationObserver(() => {
                guardAjaxSuiteGhostPopup();
            });

            observer.observe(node, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class', 'style']
            });
        });

        document.addEventListener('click', (event) => {
            const target = event.target;

            if (!target) {
                return;
            }

            if (target.closest && target.closest('[data-action=\"ajax-popup-login\"], .trigger-auth-popup, [data-b2b-login-open], .b2b-login-to-buy-btn')) {
                window.setTimeout(guardAjaxSuiteGhostPopup, 120);
            }
        }, true);

        ajaxsuiteObserverBound = true;
    }

    function closeStaleMagentoModalOverlay() {
        const modalOverlay = document.querySelector('.modals-overlay');

        if (!modalOverlay || !modalOverlay.classList.contains('_show')) {
            return;
        }

        if (document.querySelector('.modal-popup._show')) {
            return;
        }

        modalOverlay.classList.remove('_show');
        modalOverlay.style.display = 'none';

        if (document.body) {
            document.body.classList.remove('_has-modal');
            document.body.style.overflow = '';
        }
    }

    function closeNewsletterGhostPopup() {
        const popup = document.getElementById('newsletter_pop_up');
        const bModalNodes = document.querySelectorAll('.b-modal');

        if (popup) {
            popup.classList.remove('popup-closing', 'nl-popup-fallback-open');
            popup.style.display = 'none';
            popup.style.opacity = '';
            popup.style.visibility = '';
            popup.style.left = '';
            popup.style.top = '';
            popup.style.alignItems = '';
            popup.style.justifyContent = '';
        }

        if (document.body) {
            document.body.classList.remove('nl-popup-body-open');
        }

        bModalNodes.forEach((overlay) => {
            overlay.style.display = 'none';
            overlay.style.opacity = '0';
            overlay.classList.remove('_show');
        });
    }

    function guardNewsletterGhostPopup() {
        const popup = document.getElementById('newsletter_pop_up');
        const card = popup ? popup.querySelector('.nl-popup-card') : null;
        const popupVisible = isNodeActuallyVisible(popup, 40, 40);
        const cardVisible = isNodeActuallyVisible(card, 120, 120);
        const hasOpenMagentoModal = !!document.querySelector('.modal-popup._show');

        if (popupVisible && !cardVisible) {
            if (newsletterGhostCloseTimer) {
                return;
            }

            newsletterGhostCloseTimer = window.setTimeout(() => {
                newsletterGhostCloseTimer = null;

                if (isNodeActuallyVisible(document.getElementById('newsletter_pop_up'), 40, 40) &&
                    !isNodeActuallyVisible(document.querySelector('#newsletter_pop_up .nl-popup-card'), 120, 120)) {
                    closeNewsletterGhostPopup();
                }
            }, 220);

            return;
        }

        if (newsletterGhostCloseTimer) {
            window.clearTimeout(newsletterGhostCloseTimer);
            newsletterGhostCloseTimer = null;
        }

        if (!popupVisible && !hasOpenMagentoModal) {
            closeStaleMagentoModalOverlay();

            document.querySelectorAll('.b-modal').forEach((overlay) => {
                if (isNodeActuallyVisible(overlay, 10, 10)) {
                    overlay.style.display = 'none';
                    overlay.style.opacity = '0';
                }
            });
        }
    }

    function bindNewsletterGhostObserver() {
        if (newsletterObserverBound || typeof MutationObserver === 'undefined') {
            return;
        }

        const popup = document.getElementById('newsletter_pop_up');

        if (!popup) {
            return;
        }

        const rootObserver = new MutationObserver(() => {
            guardNewsletterGhostPopup();
        });

        rootObserver.observe(popup, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'style']
        });

        const bodyObserver = new MutationObserver(() => {
            guardNewsletterGhostPopup();
        });

        bodyObserver.observe(document.body, {
            childList: true,
            subtree: false
        });

        newsletterObserverBound = true;
    }

    function ensureFooterA11y(root) {
        const scope = root || document;
        const buttons = scope.querySelectorAll('.page_footer .velaFooterTitle');
        const links = scope.querySelectorAll('.page_footer a, .fixed-bottom a');

        buttons.forEach((button, index) => {
            const text = (button.textContent || '').trim();
            if (text && !button.getAttribute('title')) {
                button.setAttribute('title', text);
            }
            if (text && !button.getAttribute('aria-label')) {
                button.setAttribute('aria-label', text);
            }

            const content = button.parentElement ? button.parentElement.querySelector('.velaContent') : null;
            if (!content) {
                return;
            }

            if (!content.id) {
                content.id = `awa-footer-accordion-panel-${index + 1}`;
            }

            button.setAttribute('aria-controls', content.id);
        });

        links.forEach((link) => {
            if (link.getAttribute('title')) {
                return;
            }

            const label = (link.getAttribute('aria-label') || link.textContent || '').trim();
            if (label) {
                link.setAttribute('title', label);
            }
        });

        [
            ['#back-top', 'Voltar ao topo'],
            ['.newletter_popup_close', 'Fechar popup de ofertas'],
            ['.newletter_popup_close_text', 'Fechar popup de ofertas'],
            ['.b2b-login-modal-close', 'Fechar modal de login'],
            ['#ajaxsuite-close', 'Fechar popup rápido']
        ].forEach((entry) => {
            const node = scope.querySelector(entry[0]);
            const label = entry[1];

            if (!node) {
                return;
            }

            if (!node.getAttribute('aria-label')) {
                node.setAttribute('aria-label', label);
            }

            if (!node.getAttribute('title')) {
                node.setAttribute('title', label);
            }
        });

        scope.querySelectorAll('.fixed-bottom .mobile-bottom-link a').forEach((link) => {
            if (!link.getAttribute('title')) {
                const mobileLabel = (link.getAttribute('aria-label') || link.textContent || '').trim();
                if (mobileLabel) {
                    link.setAttribute('title', mobileLabel);
                }
            }
        });

        scope.querySelectorAll('.b2b-login-option, .b2b-login-already a').forEach((link) => {
            const text = (link.textContent || '').replace(/\s+/g, ' ').trim();

            if (text && !link.getAttribute('title')) {
                link.setAttribute('title', text);
            }

            if (text && !link.getAttribute('aria-label')) {
                link.setAttribute('aria-label', text);
            }
        });

        scope.querySelectorAll([
            '.b2b-password-toggle',
            '.b2b-register-password-toggle',
            '.b2b-btn-entrar',
            '.b2b-btn-register',
            '.b2b-btn-claim',
            '.b2b-login-whatsapp',
            '.create-b2b-account',
            '.b2b-login-link',
            '.b2b-benefits-toggle',
            '.progress-step'
        ].join(',')).forEach((node) => {
            const text = (node.getAttribute('aria-label') || node.textContent || '').replace(/\s+/g, ' ').trim();

            if (text && !node.getAttribute('title')) {
                node.setAttribute('title', text);
            }

            if (text && !node.getAttribute('aria-label')) {
                node.setAttribute('aria-label', text);
            }
        });

        scope.querySelectorAll('.b2b-password-toggle, .b2b-register-password-toggle').forEach((toggle) => {
            const targetSelector = toggle.getAttribute('data-target');
            const input = targetSelector ? scope.querySelector(targetSelector) : null;
            let fieldLabel = '';

            if (input) {
                const field = input.closest('.b2b-field, .field');
                const labelNode = field ? field.querySelector('label, .label span') : null;
                fieldLabel = labelNode ? (labelNode.textContent || '').replace(/\\s+/g, ' ').trim() : '';
            }

            if (!fieldLabel) {
                fieldLabel = 'senha';
            }

            if (!toggle.getAttribute('title')) {
                toggle.setAttribute('title', `Mostrar ou ocultar ${fieldLabel.toLowerCase()}`);
            }
            if (!toggle.getAttribute('aria-label')) {
                toggle.setAttribute('aria-label', `Mostrar ou ocultar ${fieldLabel.toLowerCase()}`);
            }
        });

        scope.querySelectorAll('.progress-step').forEach((step, index) => {
            let stepText = (step.textContent || '').replace(/\\s+/g, ' ').trim();

            if (!stepText) {
                stepText = `Etapa ${index + 1}`;
            }

            if (!step.getAttribute('aria-label')) {
                step.setAttribute('aria-label', stepText);
            }
            if (!step.getAttribute('title')) {
                step.setAttribute('title', stepText);
            }
        });

        const ajaxsuiteClose = scope.querySelector('#ajaxsuite-close');

        if (ajaxsuiteClose) {
            ajaxsuiteClose.setAttribute('role', 'button');
            ajaxsuiteClose.setAttribute('tabindex', '0');
            bindKeyboardClick(ajaxsuiteClose);
        }
    }

    function onReady() {
        ensureFooterA11y(document);
        ensureCatalogA11y(document);
        bindAutocompleteObserver();
        bindAjaxSuiteObserver();
        bindNewsletterGhostObserver();
        guardAjaxSuiteGhostPopup();
        guardNewsletterGhostPopup();
        window.setTimeout(() => {
            ensureFooterA11y(document);
            ensureCatalogA11y(document);
            bindAutocompleteObserver();
            bindAjaxSuiteObserver();
            bindNewsletterGhostObserver();
            guardAjaxSuiteGhostPopup();
            guardNewsletterGhostPopup();
        }, 700);
        window.setTimeout(guardAjaxSuiteGhostPopup, 1800);
        window.setTimeout(guardNewsletterGhostPopup, 1800);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onReady, { once: true });
    } else {
        onReady();
    }
})();
