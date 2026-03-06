/**
 * AWA B2B — Sticky Header
 * Fixa o header principal ao rolar a página para baixo.
 * Ativa após ultrapassar a altura do top-header (utility bar).
 */
(function () {
    'use strict';

    function init() {
        const headerEl = document.getElementById('header')
            || document.querySelector('.header-container')
            || document.querySelector('header[role="banner"]');
        if (!headerEl) return;

        const topHeader = headerEl.querySelector('.top-header');
        const threshold = topHeader ? topHeader.offsetHeight + 20 : 80;
        let isSticky = false;
        const STICKY_CLASS = 'awa-header-sticky';
        let headerHeight = 0;

        function onScroll() {
            const scrollY = window.pageYOffset || document.documentElement.scrollTop;
            if (scrollY > threshold && !isSticky) {
                headerHeight = headerEl.offsetHeight;
                headerEl.classList.add(STICKY_CLASS);
                document.body.style.paddingTop = `${headerHeight}px`;
                isSticky = true;
            } else if (scrollY <= threshold && isSticky) {
                headerEl.classList.remove(STICKY_CLASS);
                document.body.style.paddingTop = '';
                isSticky = false;
            }
        }

        let supportsPassive = false;
        try {
            const opts = Object.defineProperty({}, 'passive', {
                get() { supportsPassive = true; return true; }
            });
            window.addEventListener('test', null, opts);
        } catch (e) {}

        window.addEventListener('scroll', onScroll, supportsPassive ? { passive: true } : false);
        onScroll();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
