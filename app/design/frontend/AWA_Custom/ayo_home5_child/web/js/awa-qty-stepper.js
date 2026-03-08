/**
 * AWA Motos — Qty Stepper Enterprise
 * Incrementa/decrementa campo de quantidade em qualquer contexto.
 * Compatível com RequireJS / data-mage-init / x-magento-init.
 *
 * HTML esperado:
 *   <div data-mage-init='{"AWA_Custom/js/awa-qty-stepper": {}}'>
 *     <button data-action="qty-dec">−</button>
 *     <input type="number" data-qty-input min="1" max="999" value="1">
 *     <button data-action="qty-inc">+</button>
 *   </div>
 */
define(['jquery'], function ($) {
    'use strict';

    return function (config, element) {
        var $root  = $(element);
        var cfg    = Object.assign({ step: 1, min: 1, max: 9999 }, config || {});

        function getInput() {
            return $root.find('[data-qty-input], input[name="qty"], input.qty').first();
        }

        function clamp(val, min, max) {
            return Math.min(max, Math.max(min, val));
        }

        function update($input, delta) {
            var min  = parseInt($input.attr('min'),  10) || cfg.min;
            var max  = parseInt($input.attr('max'),  10) || cfg.max;
            var step = parseInt($input.attr('step'), 10) || cfg.step;
            var val  = parseInt($input.val(),        10) || min;
            var next = clamp(val + (delta * step), min, max);

            if (next !== val) {
                $input.val(next).trigger('change');
            }

            // feedback visual de limite
            $root.find('[data-action="qty-dec"]').prop('disabled', next <= min);
            $root.find('[data-action="qty-inc"]').prop('disabled', next >= max);
        }

        $root.on('click', '[data-action="qty-dec"]', function (e) {
            e.preventDefault();
            update(getInput(), -1);
        });

        $root.on('click', '[data-action="qty-inc"]', function (e) {
            e.preventDefault();
            update(getInput(), +1);
        });

        $root.on('change blur', '[data-qty-input], input[name="qty"], input.qty', function () {
            var $input = $(this);
            var min    = parseInt($input.attr('min'), 10) || cfg.min;
            var max    = parseInt($input.attr('max'), 10) || cfg.max;
            var val    = parseInt($input.val(), 10);

            if (isNaN(val) || val < min) { $input.val(min); }
            else if (val > max)           { $input.val(max); }

            $root.find('[data-action="qty-dec"]').prop('disabled', (parseInt($input.val(), 10) || min) <= min);
            $root.find('[data-action="qty-inc"]').prop('disabled', (parseInt($input.val(), 10) || min) >= max);
        });

        // Estado inicial
        getInput().trigger('change');
    };
});
