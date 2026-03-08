/**
 * AWA Motos — PDP Scroll Spy
 * Ativa a tab/nav ativa conforme a seção visível na PDP.
 * Compatível com RequireJS / x-magento-init.
 *
 * @param {Object} config
 * @param {string} [config.navSelector]     Seletor do nav de tabs
 * @param {string} [config.linkSelector]    Seletor dos links dentro do nav
 * @param {string} [config.sectionAttr]     Atributo de data na seção alvo
 * @param {number} [config.offset]          Offset do topo em px
 */
define([], function () {
    'use strict';

    return function (config) {
        var navSel     = config.navSelector    || '.product.data.items';
        var linkSel    = config.linkSelector   || '[data-role="trigger"] a';
        var offset     = config.offset         || 80;

        var nav        = document.querySelector(navSel);
        if (!nav) { return; }

        var links      = Array.from(nav.querySelectorAll(linkSel));
        if (!links.length) { return; }

        // Mapeamento link → seção
        var sections = links.map(function (link) {
            var href    = link.getAttribute('href') || '';
            var id      = href.replace(/^.*#/, '');
            var section = id ? document.getElementById(id) : null;
            return { link: link, section: section };
        }).filter(function (item) { return item.section; });

        if (!sections.length) { return; }

        var ticking = false;

        function onScroll() {
            if (ticking) { return; }
            ticking = true;
            window.requestAnimationFrame(function () {
                var scrollY = window.pageYOffset || document.documentElement.scrollTop;
                var active  = null;

                sections.forEach(function (item) {
                    var top = item.section.getBoundingClientRect().top + scrollY - offset;
                    if (scrollY >= top) {
                        active = item;
                    }
                });

                sections.forEach(function (item) {
                    var li = item.link.closest('[data-role="trigger"]');
                    if (!li) { li = item.link.parentElement; }
                    if (item === active) {
                        item.link.classList.add('awa-spy-active');
                        if (li) { li.classList.add('awa-spy-active'); }
                    } else {
                        item.link.classList.remove('awa-spy-active');
                        if (li) { li.classList.remove('awa-spy-active'); }
                    }
                });

                ticking = false;
            });
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    };
});
