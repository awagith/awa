/**
 * Social Proof Injection Script
 * Injeta elementos de prova social diretamente via JavaScript
 * Compatível com tema Rokanthemes Ayo
 * 
 * @category  GrupoAwamotos
 * @package   GrupoAwamotos_SocialProof
 * @author    Grupo Awamotos
 * @copyright Copyright (c) 2025
 */

define([
    'jquery',
    'domReady!'
], function($) {
    'use strict';

    // Função para gerar views aleatórias consistentes
    function getViewsToday(productId) {
        const date = new Date().toISOString().split('T')[0];
        const seed = parseInt(productId) + date.split('-').reduce((a, b) => parseInt(a) + parseInt(b), 0);
        return 15 + (seed % 31); // 15-45 views
    }

    // Função para verificar se é best seller
    function isBestSeller(productId) {
        return (parseInt(productId) % 5) === 0; // 20% dos produtos
    }

    // Função para injetar social proof
    function injectSocialProof() {
        // Verificar se estamos na página de produto
        const productId = $('[data-product-id]').attr('data-product-id') || 
                         $('input[name="product"]').val() ||
                         $('.product-info-main').data('product-id');

        if (!productId) {
            console.log('Social Proof: Produto ID não encontrado');
            return;
        }

        // Verificar se já foi injetado
        if ($('.product-social-proof').length > 0) {
            return;
        }

        console.log('Social Proof: Injetando para produto ' + productId);

        const views = getViewsToday(productId);
        const bestSeller = isBestSeller(productId);

        // HTML do social proof
        let html = '<div class="product-social-proof" style="' +
            'border-top: 1px solid #e0e0e0; ' +
            'border-bottom: 1px solid #e0e0e0; ' +
            'margin: 20px 0; ' +
            'padding: 15px 0; ' +
            'animation: fadeIn 0.5s;">';

        // Best Seller Badge
        if (bestSeller) {
            html += '<div class="social-proof-badge" style="' +
                'display: flex; ' +
                'align-items: center; ' +
                'gap: 8px; ' +
                'margin-bottom: 12px; ' +
                'padding: 8px 12px; ' +
                'background: linear-gradient(135deg, #ff6b35 0%, #ff4500 100%); ' +
                'color: white; ' +
                'border-radius: 20px; ' +
                'font-size: 13px; ' +
                'font-weight: 700; ' +
                'text-transform: uppercase; ' +
                'display: inline-flex; ' +
                'box-shadow: 0 2px 8px rgba(255, 69, 0, 0.3); ' +
                'animation: firePulse 2s ease-in-out infinite;">' +
                '<span style="font-size: 16px;">🔥</span>' +
                '<span>MAIS VENDIDO</span>' +
                '</div>';
        }

        // Views Counter
        html += '<div class="social-proof-views" style="' +
            'display: flex; ' +
            'align-items: center; ' +
            'gap: 8px; ' +
            'margin-bottom: 10px; ' +
            'font-size: 14px; ' +
            'color: #4CAF50;">' +
            '<span style="font-size: 18px;">👁️</span>' +
            '<span><strong>' + views + '</strong> pessoas visualizaram este produto hoje</span>' +
            '</div>';

        // Stock Urgency (exemplo - ajustar com dados reais)
        const stockQty = Math.floor(Math.random() * 15) + 1;
        if (stockQty < 10) {
            html += '<div class="social-proof-stock" style="' +
                'display: flex; ' +
                'align-items: center; ' +
                'gap: 8px; ' +
                'font-size: 14px; ' +
                'color: #FF9800; ' +
                'animation: urgencyPulse 2s ease-in-out infinite;">' +
                '<span style="font-size: 18px;">⚠️</span>' +
                '<span>Últimas <strong style="color: #b73337;">' + stockQty + '</strong> unidades em estoque!</span>' +
                '</div>';
        }

        html += '</div>';

        // CSS Animations
        if ($('#social-proof-animations').length === 0) {
            $('head').append('<style id="social-proof-animations">' +
                '@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }' +
                '@keyframes firePulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.2); } }' +
                '@keyframes urgencyPulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }' +
                '@media (max-width: 768px) {' +
                '  .product-social-proof { font-size: 12px; padding: 10px 0; }' +
                '  .social-proof-badge { font-size: 11px; }' +
                '}' +
                '</style>');
        }

        // Injetar antes do preço
        const $priceBox = $('.product-info-price, .price-box').first();
        if ($priceBox.length > 0) {
            $priceBox.before(html);
            console.log('Social Proof: Injetado com sucesso antes do preço');
        } else {
            // Fallback: injetar no início do .product-info-main
            const $productInfo = $('.product-info-main, .product-add-form').first();
            if ($productInfo.length > 0) {
                $productInfo.prepend(html);
                console.log('Social Proof: Injetado com sucesso no início do formulário');
            } else {
                console.warn('Social Proof: Container não encontrado');
            }
        }
    }

    // Executar quando a página carregar
    $(document).ready(function() {
        // Timeout para garantir que o DOM está completo
        setTimeout(injectSocialProof, 500);
    });

    // Reexecutar em Ajax (quickview, etc)
    $(document).ajaxComplete(function() {
        setTimeout(injectSocialProof, 300);
    });

    return {
        inject: injectSocialProof
    };
});
