/**
 * AWA Custom child theme (Ayo Home5) RequireJS aliases.
 *
 * rokanthemes/timecircles: Rokanthemes_Superdeals está desabilitado.
 * Mapeando para nosso stub noop.
 *
 * rokanthemes/owl shim fix: Rokanthemes declara apenas deps:["jquery"] sem exports.
 * RequireJS não consegue verificar se o plugin foi registrado em $.fn.
 * O init() garante que o módulo retorna $.fn.owlCarousel após o carregamento.
 */
var config = {
    map: {
        '*': {
            awaCustomCompatBootstrap: 'js/awa-custom-compat-bootstrap'
        }
    },
    shim: {
        'rokanthemes/owl': {
            deps: ['jquery'],
            init: function () {
                'use strict';
                return window.jQuery && window.jQuery.fn && window.jQuery.fn.owlCarousel
                    ? window.jQuery.fn.owlCarousel
                    : undefined;
            }
        }
    },
    paths: {
        'rokanthemes/timecircles': 'js/rokanthemes/timecircles',
        'GrupoAwamotos_Theme/js/announcement-bar': 'js/announcement-bar',
        'GrupoAwamotos_Theme/js/pdp-sticky-bar': 'js/pdp-sticky-bar',
        'AWA_Custom/js/recently-viewed':     'js/awa-recently-viewed',
        'AWA_Custom/js/awa-recently-viewed': 'js/awa-recently-viewed',
        'AWA_Custom/js/awa-home-b2b-bar':    'js/awa-home-b2b-bar',
        'AWA_Custom/js/compare-bar':        'js/awa-compare-bar',
        'AWA_Custom/js/awa-compare-bar':    'js/awa-compare-bar',
        'AWA_Custom/js/awa-bulk-cart':      'js/awa-bulk-cart',
        'AWA_Custom/js/awa-qty-stepper':    'js/awa-qty-stepper',
        'AWA_Custom/js/awa-alert':          'js/awa-alert',
        'AWA_Custom/js/awa-pdp-scroll-spy': 'js/awa-pdp-scroll-spy'
    }
};
