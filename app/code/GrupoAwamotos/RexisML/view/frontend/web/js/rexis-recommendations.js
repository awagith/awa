/**
 * REXIS ML - Real-time Recommendations Module
 *
 * Usage:
 * require(['rexisRecommendations'], function(rexis) {
 *     rexis.load({
 *         container: '#my-recommendations',
 *         classificacao: 'Oportunidade Cross-sell',
 *         limit: 4,
 *         onSuccess: function(data) { ... }
 *     });
 * });
 */
define([
    'jquery',
    'mage/url',
    'mage/template',
    'Magento_Customer/js/customer-data',
    'mage/cookies'
], function($, urlBuilder, mageTemplate, customerData) {
    'use strict';

    var defaultTemplate =
        '<% _.each(recommendations, function(item) { %>' +
        '   <div class="rexis-ajax-item" data-product-id="<%= item.product_id %>">' +
        '       <div class="rexis-ajax-image">' +
        '           <a href="<%= item.url %>">' +
        '               <img src="<%= item.image %>" alt="<%= item.name %>" loading="lazy" />' +
        '           </a>' +
        '       </div>' +
        '       <div class="rexis-ajax-details">' +
        '           <h4><a href="<%= item.url %>"><%= item.name %></a></h4>' +
        '           <div class="rexis-ajax-price"><%= item.price %></div>' +
        '           <% if (item.score >= 85) { %>' +
        '               <div class="rexis-ajax-score"><%= Math.round(item.score) %>% Match</div>' +
        '           <% } %>' +
        '           <button class="action primary rexis-ajax-addtocart" data-sku="<%= item.sku %>" data-product-id="<%= item.product_id %>">' +
        '               Adicionar ao Carrinho' +
        '           </button>' +
        '       </div>' +
        '   </div>' +
        '<% }); %>';

    function getFormKey() {
        return $.mage && $.mage.cookies ? $.mage.cookies.get('form_key') : '';
    }

    /**
     * Bind add-to-cart handlers on recommendation buttons within $container
     *
     * @param {jQuery} $container
     */
    function bindAddToCart($container) {
        $container.find('.rexis-ajax-addtocart').on('click', function(e) {
            e.preventDefault();

            var $btn = $(this);
            var sku = String($btn.data('sku') || '');

            if (!sku || $btn.prop('disabled')) {
                return;
            }

            $btn.prop('disabled', true).text('Adicionando...');

            $.ajax({
                url: urlBuilder.build('checkout/cart/add'),
                type: 'POST',
                dataType: 'json',
                data: {
                    sku: sku,
                    qty: 1,
                    form_key: getFormKey()
                }
            }).done(function(response) {
                if (response && response.success !== false && !response.error) {
                    customerData.reload(['cart'], true);
                    $btn.text('Adicionado ✓').addClass('rexis-added');
                } else {
                    $btn.prop('disabled', false).text('Adicionar ao Carrinho');
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('Adicionar ao Carrinho');
            });
        });
    }

    return {
        /**
         * Load recommendations via AJAX
         *
         * @param {Object} options
         */
        load: function(options) {
            var settings = $.extend({
                container: '#rexis-recommendations',
                classificacao: null,
                limit: 4,
                minScore: 0.7,
                template: null,
                showLoader: true,
                onSuccess: null,
                onError: null
            }, options);

            var $container = $(settings.container);
            if ($container.length === 0) {
                return;
            }

            // Show loader
            if (settings.showLoader) {
                $container.html('<div class="rexis-ajax-loader">Carregando recomendações...</div>');
            }

            // Build URL with params
            var url = urlBuilder.build('rexisml/ajax/getrecommendations');
            var params = {
                limit: settings.limit,
                minScore: settings.minScore
            };

            if (settings.classificacao) {
                params.classificacao = settings.classificacao;
            }

            // AJAX request
            $.ajax({
                url: url,
                type: 'GET',
                data: params,
                dataType: 'json'
            }).done(function(response) {
                if (response.success && response.recommendations.length > 0) {
                    var template = settings.template || defaultTemplate;
                    var compiled = mageTemplate(template);
                    var html = compiled({
                        recommendations: response.recommendations
                    });

                    $container.html(html);
                    bindAddToCart($container);

                    if (settings.onSuccess) {
                        settings.onSuccess(response);
                    }
                } else {
                    $container.html('<div class="rexis-ajax-empty">Nenhuma recomendação disponível no momento.</div>');
                }
            }).fail(function() {
                $container.html('<div class="rexis-ajax-error">Erro ao carregar recomendações.</div>');

                if (settings.onError) {
                    settings.onError('network_error');
                }
            });
        },

        /**
         * Refresh recommendations in container
         */
        refresh: function(container) {
            var $container = $(container);
            var options = $container.data('rexis-options') || {};
            options.container = container;
            this.load(options);
        },

        /**
         * Track recommendation view (analytics integration point)
         *
         * @param {string|number} productId
         * @param {number} score
         */
        trackView: function(productId, score) { // eslint-disable-line no-unused-vars
            // Implement analytics tracking here (e.g., GA4, Meta Pixel) when required
        },

        /**
         * Track recommendation click (analytics integration point)
         *
         * @param {string|number} productId
         * @param {number} score
         */
        trackClick: function(productId, score) { // eslint-disable-line no-unused-vars
            // Implement analytics tracking here (e.g., GA4, Meta Pixel) when required
        }
    };
});
