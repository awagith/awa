/**
 * AWA PDP Sticky Buy Bar — aparece no mobile ao rolar além do box-tocart original.
 * Só ativa em viewport <= 767px.
 */
define(['jquery'], function ($) {
    'use strict';

    return function (config, element) {
        if (window.innerWidth > 767) {
            return;
        }

        var $tocart = $('.box-tocart').first();
        if (!$tocart.length) {
            return;
        }

        // Cria a barra sticky
        var priceText = $('.product-info-price .price').first().text().trim();
        var productName = $('.page-title .base').first().text().trim();
        var $bar = $([
            '<div class="awa-sticky-buy-bar" id="awa-sticky-buy-bar" role="complementary" aria-label="Compra rápida">',
            '<div class="awa-sticky-buy-bar__price">',
            '<small>' + $('<span>').text(productName.substring(0, 28) + (productName.length > 28 ? '…' : '')).html() + '</small>',
            $('<span>').text(priceText).html(),
            '</div>',
            '<button type="button" class="awa-sticky-buy-bar__btn" data-action="sticky-add-to-cart">',
            '<span>Comprar</span>',
            '</button>',
            '</div>'
        ].join(''));

        $('body').append($bar).addClass('awa-has-sticky-bar');

        // Observar quando box-tocart sai da viewport
        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                var entry = entries[0];
                if (!entry.isIntersecting) {
                    $bar.addClass('awa-sticky-buy-bar--visible');
                } else {
                    $bar.removeClass('awa-sticky-buy-bar--visible');
                }
            }, { threshold: 0.1 });
            observer.observe($tocart[0]);
        }

        // Clicar na barra rola até o box-tocart original
        $bar.on('click', '[data-action="sticky-add-to-cart"]', function () {
            $('html, body').animate({ scrollTop: $tocart.offset().top - 80 }, 300, function () {
                $tocart.find('button[type=submit]').first().trigger('focus');
            });
        });
    };
});
