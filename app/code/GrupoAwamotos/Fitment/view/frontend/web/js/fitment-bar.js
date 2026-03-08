/**
 * Fitment Bar Widget — PLP
 * Cascading selects: Marca → Modelo → Ano
 * Loads brands on init, then chains models and years via AJAX.
 *
 * @module awaFitmentBar
 */
define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';

    return function (config, element) {
        var $bar      = $(element);
        var $marca    = $bar.find('[data-fitment-role="marca"]');
        var $modelo   = $bar.find('[data-fitment-role="modelo"]');
        var $ano      = $bar.find('[data-fitment-role="ano"]');
        var $submit   = $bar.find('#fitment-bar-submit');

        var brandsUrl = config.brandsUrl || '';
        var modelsUrl = config.modelsUrl || '';
        var yearsUrl  = config.yearsUrl  || '';

        var activeMarca  = config.activeMarca  || '';
        var activeModelo = config.activeModelo || '';
        var activeAno    = config.activeAno    || '';

        function setLoading($select, loading) {
            var $wrap = $select.closest('.awa-fitment-select-control');
            $wrap.toggleClass('awa-fitment-loading', loading);
            $select.prop('disabled', loading);
        }

        function resetSelect($select, placeholder) {
            $select.empty().append(
                $('<option>', { value: '', text: placeholder })
            ).prop('disabled', true);
        }

        function populate($select, items, selectedVal) {
            $select.empty().append(
                $('<option>', { value: '', text: $select.data('placeholder') || $t('— Selecione —') })
            );
            $.each(items, function (_, val) {
                var $opt = $('<option>', { value: val, text: val });
                if (val === selectedVal) {
                    $opt.prop('selected', true);
                }
                $select.append($opt);
            });
            if (items.length) {
                $select.prop('disabled', false);
            }
        }

        function updateSubmit() {
            $submit.prop('disabled', !$marca.val());
        }

        // ── Load Brands ─────────────────────────────────────────────────────
        function loadBrands() {
            setLoading($marca, true);
            $.getJSON(brandsUrl).done(function (resp) {
                if (resp && resp.items) {
                    populate($marca, resp.items, activeMarca);
                    if (activeMarca) {
                        loadModels(activeMarca);
                    }
                }
            }).always(function () {
                setLoading($marca, false);
                updateSubmit();
            });
        }

        // ── Load Models ─────────────────────────────────────────────────────
        function loadModels(marca) {
            setLoading($modelo, true);
            resetSelect($modelo, $t('— Modelo —'));
            resetSelect($ano, $t('— Ano —'));

            $.getJSON(modelsUrl, { marca: marca }).done(function (resp) {
                if (resp && resp.items) {
                    populate($modelo, resp.items, activeModelo);
                    if (activeModelo) {
                        loadYears(marca, activeModelo);
                    }
                }
            }).always(function () {
                setLoading($modelo, false);
            });
        }

        // ── Load Years ──────────────────────────────────────────────────────
        function loadYears(marca, modelo) {
            setLoading($ano, true);
            resetSelect($ano, $t('— Ano —'));

            $.getJSON(yearsUrl, { marca: marca, modelo: modelo }).done(function (resp) {
                if (resp && resp.items) {
                    populate($ano, resp.items, activeAno);
                }
            }).always(function () {
                setLoading($ano, false);
            });
        }

        // ── Event Bindings ──────────────────────────────────────────────────
        $marca.on('change', function () {
            var val = this.value;
            activeMarca  = val;
            activeModelo = '';
            activeAno    = '';
            resetSelect($modelo, $t('— Modelo —'));
            resetSelect($ano, $t('— Ano —'));
            updateSubmit();
            if (val) {
                loadModels(val);
            }
        });

        $modelo.on('change', function () {
            var val = this.value;
            activeModelo = val;
            activeAno    = '';
            resetSelect($ano, $t('— Ano —'));
            if (val && activeMarca) {
                loadYears(activeMarca, val);
            }
        });

        $ano.on('change', function () {
            activeAno = this.value;
        });

        // ── Salva seleção no sessionStorage para integração com PDP ─────────
        $bar.closest('form').on('submit', function () {
            var selection = {
                marca:  $marca.val()  || '',
                modelo: $modelo.val() || '',
                ano:    $ano.val()    || ''
            };
            try {
                sessionStorage.setItem('awa_fitment', JSON.stringify(selection));
            } catch (e) {
                // sessionStorage não disponível — ignorar
            }
        });

        // ── Init ────────────────────────────────────────────────────────────
        loadBrands();
    };
});
