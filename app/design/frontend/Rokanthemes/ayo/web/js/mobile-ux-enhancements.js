/**
 * Mobile UX Enhancements
 * Otimizações específicas para dispositivos móveis
 */

// ====================================
// 1. Input Type Optimization
// ====================================
require(['jquery', 'domReady!'], function ($) {
    'use strict';

    // CEP/Postcode - numeric keyboard
    $('input[name="postcode"], input[name*="zip"], input[name*="cep"]').attr({
        'type': 'tel',
        'inputmode': 'numeric',
        'pattern': '[0-9]*'
    });

    // Email - email keyboard
    $('input[name="email"], input[type="email"]').attr({
        'inputmode': 'email',
        'autocomplete': 'email'
    });

    // Phone - phone keyboard
    $('input[name*="phone"], input[name*="telephone"], input[name*="telefone"]').attr({
        'type': 'tel',
        'inputmode': 'tel',
        'autocomplete': 'tel'
    });

    // Search - search keyboard
    $('input[name="q"], .search input[type="text"]').attr({
        'inputmode': 'search',
        'autocomplete': 'off'
    });

    // CPF/CNPJ - numeric keyboard
    $('input[name*="cpf"], input[name*="cnpj"], input[name*="taxvat"]').attr({
        'type': 'tel',
        'inputmode': 'numeric',
        'pattern': '[0-9]*'
    });
});

// ====================================
// 2. Touch Target Enhancement
// ====================================
require(['jquery', 'domReady!'], function ($) {
    'use strict';

    // Aumentar área de toque em mobile para botões pequenos
    if ('ontouchstart' in window) {
        $('.action.primary, .action.secondary, .tocart, .btn, button').css({
            'min-height': '44px',
            'min-width': '44px'
        });
    }
});

// ====================================
// 3. Remove Hover States on Touch Devices
// ====================================
if ('ontouchstart' in window) {
    document.documentElement.classList.add('touch-device');
}

// CSS será aplicado via :not(.touch-device)

// ====================================
// 4. Swipe Gestures for Product Gallery
// ====================================
require(['jquery', 'domReady!'], function ($) {
    'use strict';

    var $gallery = $('.product.media .gallery-placeholder, .fotorama');
    if ($gallery.length && 'ontouchstart' in window) {
        
        var startX = 0;
        var endX = 0;
        var threshold = 50; // pixels

        $gallery.on('touchstart', function (e) {
            startX = e.originalEvent.touches[0].clientX;
        });

        $gallery.on('touchend', function (e) {
            endX = e.originalEvent.changedTouches[0].clientX;
            handleSwipe();
        });

        function handleSwipe() {
            var diff = startX - endX;

            if (Math.abs(diff) > threshold) {
                if (diff > 0) {
                    // Swipe left - next image
                    $gallery.find('.fotorama__arr--next').trigger('click');
                } else {
                    // Swipe right - previous image
                    $gallery.find('.fotorama__arr--prev').trigger('click');
                }
            }
        }
    }
});

// ====================================
// 5. Pinch to Zoom (Product Images)
// ====================================
require(['jquery', 'domReady!'], function ($) {
    'use strict';

    if ('ontouchstart' in window) {
        $('.product.media img, .fotorama__img').attr('data-zoom-enabled', 'true');
        
        // Permitir zoom nativo em imagens de produto
        $('.product.media').css({
            'touch-action': 'pinch-zoom'
        });
    }
});

// ====================================
// 6. Pull to Refresh (Homepage only)
// ====================================
require(['jquery', 'domReady!'], function ($) {
    'use strict';

    if ('ontouchstart' in window && $('body').hasClass('cms-index-index')) {
        var startY = 0;
        var pullThreshold = 80;
        var $body = $('body');
        var $refreshIndicator = null;

        // Create refresh indicator
        $refreshIndicator = $('<div class="pull-refresh-indicator">↓ Puxe para atualizar</div>');
        $body.prepend($refreshIndicator);

        $(document).on('touchstart', function (e) {
            if ($(window).scrollTop() === 0) {
                startY = e.originalEvent.touches[0].clientY;
            }
        });

        $(document).on('touchmove', function (e) {
            if ($(window).scrollTop() === 0) {
                var currentY = e.originalEvent.touches[0].clientY;
                var pullDistance = currentY - startY;

                if (pullDistance > 0 && pullDistance < pullThreshold * 2) {
                    $refreshIndicator.css({
                        'transform': 'translateY(' + pullDistance + 'px)',
                        'opacity': pullDistance / pullThreshold
                    });

                    if (pullDistance > pullThreshold) {
                        $refreshIndicator.text('↑ Solte para atualizar');
                    } else {
                        $refreshIndicator.text('↓ Puxe para atualizar');
                    }
                }
            }
        });

        $(document).on('touchend', function (e) {
            var currentY = e.originalEvent.changedTouches[0].clientY;
            var pullDistance = currentY - startY;

            if (pullDistance > pullThreshold) {
                $refreshIndicator.text('⟳ Atualizando...');
                
                // Reload page after 500ms
                setTimeout(function () {
                    location.reload();
                }, 500);
            } else {
                $refreshIndicator.css({
                    'transform': 'translateY(0)',
                    'opacity': 0
                });
            }
        });
    }
});

// ====================================
// 7. Prevent Zoom on Double Tap (Inputs)
// ====================================
require(['jquery', 'domReady!'], function ($) {
    'use strict';

    // Prevenir zoom em inputs (iOS Safari bug)
    $('input, select, textarea').on('touchend', function (e) {
        var $this = $(this);
        
        // Garantir font-size >= 16px para evitar auto-zoom
        if (parseInt($this.css('font-size')) < 16) {
            $this.css('font-size', '16px');
        }
    });
});
