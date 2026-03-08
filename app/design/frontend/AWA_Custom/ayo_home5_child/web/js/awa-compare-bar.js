/**
 * AWA Motos — Compare Bar (v2)
 * Barra sticky inferior de comparação de produtos.
 * Sincroniza com o widget nativo do Magento via MutationObserver.
 * Inicializado via x-magento-init com selector "*".
 */
define(['jquery'], function ($) {
    'use strict';

    var BAR_ID       = '#awa-compare-bar';
    var ITEMS_ID     = '#awa-compare-bar-items';
    var COUNT_ID     = '#awa-compare-bar-count';
    var CLEAR_ID     = '#awa-compare-bar-clear';
    var MAX_COMPARE  = 4;

    /**
     * Lê os itens da lista de comparação via DOM do sidebar/widget Magento
     * @return {Array<{id: string, name: string}>}
     */
    function readCompareItems() {
        var items = [];

        // Tenta ler do sidebar de comparação nativo do Magento
        $('.block-compare .product-item').each(function () {
            var $li     = $(this);
            var $name   = $li.find('.product-item-name a');
            var $img    = $li.find('.product-image-photo');
            var name    = $name.text().trim();
            var id      = $li.data('productId')
                       || $li.find('[data-product-id]').data('productId')
                       || '';
            var imgSrc  = $img.attr('src') || '';

            if (name) {
                items.push({ id: String(id), name: name, img: imgSrc });
            }
        });

        // Fallback: ler do input oculto de count do Magento
        if (!items.length) {
            var $counter = $('.block-compare .counter.qty');
            if ($counter.length && parseInt($counter.text(), 10) > 0) {
                // Não temos os detalhes, mostra count apenas
                return [];
            }
        }

        return items.slice(0, MAX_COMPARE);
    }

    /**
     * Renderiza os itens na barra e controla visibilidade
     * @param {Array} items
     */
    function renderBar(items) {
        var $bar   = $(BAR_ID);
        var $items = $(ITEMS_ID);
        var $count = $(COUNT_ID);

        if (!$bar.length) {
            return;
        }

        $items.empty();

        if (!items.length) {
            $bar.removeAttr('hidden').removeClass('is-visible');
            setTimeout(function () {
                if (!$bar.hasClass('is-visible')) {
                    $bar.attr('hidden', '');
                }
            }, 320);
            return;
        }

        // Mostra o elemento e anima entrada
        $bar.removeAttr('hidden');
        requestAnimationFrame(function () {
            $bar.addClass('is-visible');
        });

        // Atualiza contador
        $count.text(items.length);

        // Renderiza cada item
        items.forEach(function (item) {
            var safeName = $('<span>').text(item.name).html();
            var imgHtml  = item.img
                ? '<img src="' + $('<span>').text(item.img).html() + '" alt="' + safeName + '" class="awa-compare-bar__item-img" loading="lazy">'
                : '<span class="awa-compare-bar__slot" aria-hidden="true">+</span>';

            $items.append(
                '<div class="awa-compare-bar__item" data-product-id="' + $('<span>').text(item.id).html() + '">'
                + imgHtml
                + '<span class="awa-compare-bar__item-name" title="' + safeName + '">' + safeName + '</span>'
                + '<button type="button" class="awa-compare-bar__item-remove" aria-label="Remover ' + safeName + ' da comparação" data-remove-id="' + $('<span>').text(item.id).html() + '">&#x2715;</button>'
                + '</div>'
            );
        });
    }

    /**
     * Sincroniza a barra com o estado atual de comparação
     */
    function syncBar() {
        var items = readCompareItems();
        renderBar(items);
    }

    /**
     * Instala MutationObserver no container de comparação nativo
     */
    function watchCompareWidget() {
        var target = document.querySelector('.block-compare .block-content')
                  || document.querySelector('.block-compare');

        if (target && window.MutationObserver) {
            var observer = new MutationObserver(function () {
                setTimeout(syncBar, 150);
            });
            observer.observe(target, { childList: true, subtree: true });
        }
    }

    return function (config) {
        // Aguarda DOM pronto
        $(function () {
            syncBar();
            watchCompareWidget();

            // Magento dispara este evento após update de compare via Ajax
            $(document).on('ajax:updateCompare customer-data:reload', function () {
                setTimeout(syncBar, 400);
            });

            // Botão limpar
            $(document).on('click', CLEAR_ID, function (e) {
                e.preventDefault();
                var $native = $('.block-compare .action.clear');
                if ($native.length) {
                    $native[0].click();
                }
                $(BAR_ID).removeClass('is-visible');
                setTimeout(function () {
                    $(BAR_ID).attr('hidden', '');
                }, 320);
            });

            // Remover item individual
            $(document).on('click', '.awa-compare-bar__item-remove', function () {
                var pid = $(this).data('removeId');
                if (!pid) { return; }

                // Encontra o botão nativo de remover do sidebar
                var $native = $('.block-compare .action.delete[data-post*="' + pid + '"]');
                if ($native.length) {
                    $native[0].click();
                } else {
                    // Fallback: remove localmente e re-sincroniza
                    $(this).closest('.awa-compare-bar__item').remove();
                    setTimeout(syncBar, 300);
                }
            });
        });
    };
});
