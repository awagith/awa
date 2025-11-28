define(['jquery'], function ($) {
    'use strict';

    function toggleExpanded($el, expanded) {
        try {
            $el.attr('aria-expanded', expanded ? 'true' : 'false');
        } catch (e) {}
    }

    $(function () {
        var $toggle = $('.action.nav-toggle');
        if (!$toggle.length) return;

        // Inicializa o estado
        if (!$toggle.attr('aria-expanded')) {
            toggleExpanded($toggle, false);
        }

        // Click/touch
        $toggle.on('click', function () {
            var current = $(this).attr('aria-expanded') === 'true';
            toggleExpanded($(this), !current);
        });

        // Teclado: Enter/Espaço
        $toggle.on('keydown', function (e) {
            var key = e.which || e.keyCode;
            if (key === 13 || key === 32) { // Enter or Space
                e.preventDefault();
                $(this).trigger('click');
            }
        });

        // Se o tema sinaliza corpo com classe, sincroniza (best-effort)
        var bodyClassObserver = new MutationObserver(function () {
            var isOpen = document.body.classList.contains('nav-open') || document.body.classList.contains('active-nav');
            toggleExpanded($toggle, !!isOpen);
        });
        try {
            bodyClassObserver.observe(document.body, { attributes: true, attributeFilter: ['class'] });
        } catch (e) {}
    });
});
