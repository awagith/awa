/**
 * Melhorias de formulários - UX Brasil
 * Labels flutuantes, validação visual, acessibilidade
 */
define([
    'jquery',
    'mage/translate',
    'domReady!'
], function ($, $t) {
    'use strict';

    var FormEnhancements = {
        /**
         * Configurações
         */
        config: {
            floatingLabels: true,
            showPasswordStrength: true,
            showCharCounter: true,
            animateErrors: true
        },

        /**
         * Inicializa melhorias
         */
        init: function (options) {
            $.extend(this.config, options || {});

            this.initFloatingLabels();
            this.initPasswordStrength();
            this.initCharCounter();
            this.initFormValidationStyles();
            this.initAccessibility();
            this.initPlaceholders();

            console.log('[Form Enhancements] Inicializado');
        },

        /**
         * Labels flutuantes
         */
        initFloatingLabels: function () {
            if (!this.config.floatingLabels) return;

            var $fields = $('.field input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]), .field textarea, .field select');

            $fields.each(function () {
                var $input = $(this);
                var $field = $input.closest('.field');
                var $label = $field.find('label.label');

                if ($label.length && !$field.hasClass('floating-label-ready')) {
                    $field.addClass('floating-label-ready');

                    // Estado inicial
                    if ($input.val() || $input.is(':focus') || $input.is('select')) {
                        $field.addClass('has-value');
                    }
                }
            });

            // Eventos
            $(document).on('focus', '.floating-label-ready input, .floating-label-ready textarea', function () {
                $(this).closest('.field').addClass('is-focused has-value');
            });

            $(document).on('blur', '.floating-label-ready input, .floating-label-ready textarea', function () {
                var $field = $(this).closest('.field');
                $field.removeClass('is-focused');
                if (!$(this).val()) {
                    $field.removeClass('has-value');
                }
            });

            $(document).on('change', '.floating-label-ready select', function () {
                var $field = $(this).closest('.field');
                if ($(this).val()) {
                    $field.addClass('has-value');
                } else {
                    $field.removeClass('has-value');
                }
            });
        },

        /**
         * Indicador de força de senha
         */
        initPasswordStrength: function () {
            if (!this.config.showPasswordStrength) return;

            $(document).on('input', 'input[type="password"]', function () {
                var $input = $(this);
                var password = $input.val();
                var $field = $input.closest('.field');
                var $meter = $field.find('.password-strength-meter');

                if (!$meter.length) {
                    $meter = $('<div class="password-strength-meter"><div class="meter-bar"></div><span class="meter-text"></span></div>');
                    $input.after($meter);
                }

                var strength = FormEnhancements.calculatePasswordStrength(password);
                var $bar = $meter.find('.meter-bar');
                var $text = $meter.find('.meter-text');

                $bar.removeClass('weak medium strong very-strong').addClass(strength.level);
                $bar.css('width', strength.percent + '%');
                $text.text(strength.text);
            });
        },

        /**
         * Calcula força da senha
         */
        calculatePasswordStrength: function (password) {
            var score = 0;
            var result = { level: 'weak', percent: 0, text: $t('Fraca') };

            if (!password) return result;

            // Comprimento
            if (password.length >= 8) score += 1;
            if (password.length >= 12) score += 1;
            if (password.length >= 16) score += 1;

            // Complexidade
            if (/[a-z]/.test(password)) score += 1;
            if (/[A-Z]/.test(password)) score += 1;
            if (/[0-9]/.test(password)) score += 1;
            if (/[^a-zA-Z0-9]/.test(password)) score += 2;

            // Resultado
            if (score <= 2) {
                result = { level: 'weak', percent: 25, text: $t('Fraca') };
            } else if (score <= 4) {
                result = { level: 'medium', percent: 50, text: $t('Média') };
            } else if (score <= 6) {
                result = { level: 'strong', percent: 75, text: $t('Forte') };
            } else {
                result = { level: 'very-strong', percent: 100, text: $t('Muito forte') };
            }

            return result;
        },

        /**
         * Contador de caracteres
         */
        initCharCounter: function () {
            if (!this.config.showCharCounter) return;

            $('textarea[maxlength], input[maxlength]').each(function () {
                var $input = $(this);
                var maxLength = $input.attr('maxlength');

                if (maxLength && !$input.next('.char-counter').length) {
                    var $counter = $('<div class="char-counter"><span class="current">0</span>/<span class="max">' + maxLength + '</span></div>');
                    $input.after($counter);

                    $input.on('input', function () {
                        $counter.find('.current').text($input.val().length);
                        if ($input.val().length >= maxLength * 0.9) {
                            $counter.addClass('warning');
                        } else {
                            $counter.removeClass('warning');
                        }
                    });

                    // Valor inicial
                    $counter.find('.current').text($input.val().length);
                }
            });
        },

        /**
         * Estilos de validação
         */
        initFormValidationStyles: function () {
            // Adiciona classes visuais em campos válidos/inválidos
            $(document).on('blur', 'input:not([type="hidden"]), textarea, select', function () {
                var $input = $(this);
                var $field = $input.closest('.field');

                // Remove classes anteriores
                $field.removeClass('validation-passed validation-failed');

                // Verifica se é obrigatório e tem valor
                if ($input.prop('required') || $input.hasClass('required')) {
                    if ($input.val() && !$input.hasClass('mage-error')) {
                        $field.addClass('validation-passed');
                    } else if (!$input.val()) {
                        $field.addClass('validation-failed');
                    }
                }
            });

            // Animação em erros
            if (this.config.animateErrors) {
                $(document).on('invalid', 'input, textarea, select', function () {
                    var $field = $(this).closest('.field');
                    $field.addClass('shake');
                    setTimeout(function () {
                        $field.removeClass('shake');
                    }, 500);
                });
            }
        },

        /**
         * Acessibilidade
         */
        initAccessibility: function () {
            // Adiciona aria-labels em campos sem label visível
            $('input:not([aria-label]), textarea:not([aria-label])').each(function () {
                var $input = $(this);
                var $label = $('label[for="' + $input.attr('id') + '"]');
                var placeholder = $input.attr('placeholder');

                if ($label.length) {
                    $input.attr('aria-label', $label.text().trim());
                } else if (placeholder) {
                    $input.attr('aria-label', placeholder);
                }
            });

            // Adiciona role nos grupos de campos
            $('.fieldset, .field-group').attr('role', 'group');

            // Focus visible para teclado
            $(document).on('keydown', function (e) {
                if (e.key === 'Tab') {
                    $('body').addClass('keyboard-navigation');
                }
            });

            $(document).on('mousedown', function () {
                $('body').removeClass('keyboard-navigation');
            });
        },

        /**
         * Placeholders brasileiros
         */
        initPlaceholders: function () {
            var placeholders = {
                'input[name*="telephone" i], input[name*="telefone" i]': '(00) 00000-0000',
                'input[name*="postcode" i], input[name*="cep" i]': '00000-000',
                'input[name*="cpf" i]': '000.000.000-00',
                'input[name*="cnpj" i]': '00.000.000/0000-00',
                'input[name*="taxvat" i]': 'CPF ou CNPJ'
            };

            $.each(placeholders, function (selector, placeholder) {
                $(selector).each(function () {
                    var $input = $(this);
                    if (!$input.attr('placeholder')) {
                        $input.attr('placeholder', placeholder);
                    }
                });
            });
        }
    };

    // Auto-inicializa
    FormEnhancements.init();

    return FormEnhancements;
});
