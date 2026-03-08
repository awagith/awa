/**
 * Fitment PDP Compatibility Widget
 * Accordion por marca, filtro rápido e integração com sessionStorage da fitment bar.
 *
 * Referenciado via data-mage-init="GrupoAwamotos_Fitment/js/fitment" no template PDP.
 *
 * @module awaFitmentPdp
 */
define(['jquery'], function ($) {
    'use strict';

    return function (config, element) {
        var $section = $(element);
        var $filter  = $section.find('#awa-compat-filter');
        var $myMoto  = $section.find('#awa-compat-my-moto');
        var $status  = $myMoto.find('.awa-compat-status');
        var $noRes   = $section.find('.awa-compat__no-results');

        // ── Accordion ───────────────────────────────────────────────────────
        $section.on('click', '.awa-compat__brand-toggle', function () {
            var $brand  = $(this).closest('.awa-compat__brand');
            var wasOpen = $brand.hasClass('is-open');

            // Fecha todos; abre o clicado (ou só fecha se já estava aberto)
            $section.find('.awa-compat__brand.is-open').removeClass('is-open');
            $(this).attr('aria-expanded', 'false');

            if (!wasOpen) {
                $brand.addClass('is-open');
                $(this).attr('aria-expanded', 'true');
            }
        });

        // Abre o primeiro accordion por padrão
        var $first = $section.find('.awa-compat__brand').first();
        $first.addClass('is-open');
        $first.find('.awa-compat__brand-toggle').attr('aria-expanded', 'true');

        // ── Filtro de busca rápida ───────────────────────────────────────────
        $filter.on('input', function () {
            var q            = $(this).val().toLowerCase().trim();
            var visibleBrands = 0;

            $section.find('.awa-compat__brand').each(function () {
                var $brand      = $(this);
                var visibleRows = 0;

                $brand.find('tbody tr').each(function () {
                    var $tr  = $(this);
                    var text = $tr.text().toLowerCase();
                    var show = !q || text.indexOf(q) !== -1;
                    $tr.toggle(show);
                    if (show) { visibleRows++; }
                });

                var showBrand = !q || visibleRows > 0;
                $brand.toggle(showBrand);

                if (showBrand) {
                    visibleBrands++;
                    // Ao filtrar, abre marcas com resultados
                    if (q && visibleRows > 0) {
                        $brand.addClass('is-open');
                        $brand.find('.awa-compat__brand-toggle').attr('aria-expanded', 'true');
                    }
                }
            });

            $noRes.toggle(visibleBrands === 0);
        });

        // ── Integração sessionStorage — banner "Minha moto" ─────────────────
        function normalize(s) {
            return (s || '').toLowerCase().replace(/\s+/g, ' ').trim();
        }

        function escapeHtml(str) {
            return $('<span>').text(str).html();
        }

        function checkCompatibility() {
            var raw = '';
            try { raw = sessionStorage.getItem('awa_fitment') || ''; } catch (e) { return; }
            if (!raw) { return; }

            var sel;
            try { sel = JSON.parse(raw); } catch (e) { return; }
            if (!sel || !sel.marca) { return; }

            var marca  = normalize(sel.marca);
            var modelo = normalize(sel.modelo || '');
            var ano    = normalize(sel.ano    || '');
            var matched = false;

            $section.find('tbody tr').each(function () {
                var $tr     = $(this);
                var trBrand = normalize($tr.data('brand') || '');
                var trModel = normalize($tr.data('model') || '');
                var trYears = normalize($tr.data('years') || '');

                // Match marca: exato ou substring em qualquer direção
                var brandMatch = trBrand === marca ||
                                 trBrand.indexOf(marca) !== -1 ||
                                 marca.indexOf(trBrand) !== -1;

                // Match modelo: se informado, substring em qualquer direção
                var modelMatch = !modelo ||
                                 trModel === modelo ||
                                 trModel.indexOf(modelo) !== -1 ||
                                 modelo.indexOf(trModel) !== -1;

                // Match ano: se informado, deve constar no campo "years"
                var yearMatch = !ano || trYears.indexOf(ano) !== -1;

                if (brandMatch && modelMatch && yearMatch) {
                    $tr.addClass('is-match');
                    // Abre e destaca a marca correspondente
                    var $brand = $tr.closest('.awa-compat__brand');
                    $brand.addClass('is-open is-highlighted');
                    $brand.find('.awa-compat__brand-toggle').attr('aria-expanded', 'true');
                    matched = true;
                }
            });

            // Constrói label de exibição
            var label = sel.marca;
            if (sel.modelo) { label += ' ' + sel.modelo; }
            if (sel.ano)    { label += ' ' + sel.ano; }

            $myMoto.show();

            if (matched) {
                $myMoto.addClass('is-compatible').removeClass('is-incompatible');
                $status.addClass('is-compatible').removeClass('is-incompatible');
                $status.html(
                    '<span class="awa-compat-status__icon">✅</span>' +
                    '<span class="awa-compat-status__text">Compatível com ' +
                    '<strong>' + escapeHtml(label) + '</strong></span>'
                );
            } else {
                $myMoto.addClass('is-incompatible').removeClass('is-compatible');
                $status.addClass('is-incompatible').removeClass('is-compatible');
                $status.html(
                    '<span class="awa-compat-status__icon">❌</span>' +
                    '<span class="awa-compat-status__text">Não encontrado para ' +
                    '<strong>' + escapeHtml(label) + '</strong></span>'
                );
            }
        }

        checkCompatibility();
    };
});
