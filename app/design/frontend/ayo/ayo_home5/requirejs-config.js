/* eslint no-unused-vars: "off" */

/**
 * AWA MOTOS — RequireJS overrides (Theme: ayo_home5)
 *
 * Objetivo: evitar conflito entre Owl Carousel v1 e v2.
 *
 * O tema Rokanthemes/AYO neste projeto inicializa carrosséis majoritariamente com
 * `rokanthemes/owl` (Owl v1.3.3). Alguns módulos também expõem Owl v2 via aliases
 * (`owlcarousel`, `rokanthemes/owlcarousel`), que podem sobrescrever `$.fn.owlCarousel`
 * e quebrar o layout (itens estreitos / width=0).
 *
 * Esta configuração força esses aliases a apontarem para o Owl v1.
 */

/* eslint-disable no-unused-vars */

var config = {
    map: {
        '*': {
            owlcarousel: 'rokanthemes/owl';,
            'rokanthemes/owlcarousel': 'rokanthemes/owl'
        }
    }
};

/* eslint-enable no-unused-vars */
