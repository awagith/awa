/**
 * Máscaras brasileiras para formulários
 * CPF, CNPJ, Telefone, CEP, Placa de Veículo
 */
define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';

    var masks = {
        /**
         * Aplica máscara de CPF (000.000.000-00)
         */
        cpf: function (value) {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})/, '$1-$2')
                .replace(/(-\d{2})\d+?$/, '$1');
        },

        /**
         * Aplica máscara de CNPJ (00.000.000/0000-00)
         */
        cnpj: function (value) {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{2})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1/$2')
                .replace(/(\d{4})(\d)/, '$1-$2')
                .replace(/(-\d{2})\d+?$/, '$1');
        },

        /**
         * Aplica máscara de CPF ou CNPJ automaticamente
         */
        cpfCnpj: function (value) {
            var cleaned = value.replace(/\D/g, '');
            if (cleaned.length <= 11) {
                return masks.cpf(value);
            }
            return masks.cnpj(value);
        },

        /**
         * Aplica máscara de telefone (00) 00000-0000 ou (00) 0000-0000
         */
        telefone: function (value) {
            var cleaned = value.replace(/\D/g, '');
            if (cleaned.length <= 10) {
                return cleaned
                    .replace(/(\d{2})(\d)/, '($1) $2')
                    .replace(/(\d{4})(\d)/, '$1-$2')
                    .replace(/(-\d{4})\d+?$/, '$1');
            }
            return cleaned
                .replace(/(\d{2})(\d)/, '($1) $2')
                .replace(/(\d{5})(\d)/, '$1-$2')
                .replace(/(-\d{4})\d+?$/, '$1');
        },

        /**
         * Aplica máscara de CEP (00000-000)
         */
        cep: function (value) {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{5})(\d)/, '$1-$2')
                .replace(/(-\d{3})\d+?$/, '$1');
        },

        /**
         * Aplica máscara de placa de veículo (ABC-1234 ou ABC1D23)
         */
        placa: function (value) {
            var cleaned = value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            if (cleaned.length <= 7) {
                // Detecta formato Mercosul (ABC1D23) ou antigo (ABC1234)
                if (cleaned.length >= 4 && /^[A-Z]{3}[0-9][A-Z]/.test(cleaned)) {
                    // Mercosul
                    return cleaned.replace(/([A-Z]{3})([0-9])([A-Z])([0-9]{0,2})/, '$1$2$3$4');
                }
                // Formato antigo
                return cleaned.replace(/([A-Z]{3})([0-9]{0,4})/, '$1-$2');
            }
            return cleaned.substring(0, 7);
        },

        /**
         * Aplica máscara de cartão de crédito (0000 0000 0000 0000)
         */
        cartao: function (value) {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{4})(\d)/, '$1 $2')
                .replace(/(\d{4})(\d)/, '$1 $2')
                .replace(/(\d{4})(\d)/, '$1 $2')
                .replace(/(\d{4})\d+?$/, '$1');
        },

        /**
         * Aplica máscara de data (00/00/0000)
         */
        data: function (value) {
            return value
                .replace(/\D/g, '')
                .replace(/(\d{2})(\d)/, '$1/$2')
                .replace(/(\d{2})(\d)/, '$1/$2')
                .replace(/(\d{4})\d+?$/, '$1');
        },

        /**
         * Aplica máscara de moeda brasileira (R$ 0,00)
         */
        moeda: function (value) {
            var cleaned = value.replace(/\D/g, '');
            var number = parseInt(cleaned, 10) / 100;
            return number.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
        },

        /**
         * Apenas números
         */
        numero: function (value) {
            return value.replace(/\D/g, '');
        }
    };

    /**
     * Validadores
     */
    var validators = {
        /**
         * Valida CPF
         */
        cpf: function (cpf) {
            cpf = cpf.replace(/\D/g, '');
            if (cpf.length !== 11 || /^(\d)\1+$/.test(cpf)) return false;

            var soma = 0, resto;
            for (var i = 1; i <= 9; i++) {
                soma += parseInt(cpf.substring(i - 1, i)) * (11 - i);
            }
            resto = (soma * 10) % 11;
            if (resto === 10 || resto === 11) resto = 0;
            if (resto !== parseInt(cpf.substring(9, 10))) return false;

            soma = 0;
            for (i = 1; i <= 10; i++) {
                soma += parseInt(cpf.substring(i - 1, i)) * (12 - i);
            }
            resto = (soma * 10) % 11;
            if (resto === 10 || resto === 11) resto = 0;
            return resto === parseInt(cpf.substring(10, 11));
        },

        /**
         * Valida CNPJ
         */
        cnpj: function (cnpj) {
            cnpj = cnpj.replace(/\D/g, '');
            if (cnpj.length !== 14 || /^(\d)\1+$/.test(cnpj)) return false;

            var tamanho = cnpj.length - 2;
            var numeros = cnpj.substring(0, tamanho);
            var digitos = cnpj.substring(tamanho);
            var soma = 0;
            var pos = tamanho - 7;

            for (var i = tamanho; i >= 1; i--) {
                soma += numeros.charAt(tamanho - i) * pos--;
                if (pos < 2) pos = 9;
            }
            var resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
            if (resultado !== parseInt(digitos.charAt(0))) return false;

            tamanho = tamanho + 1;
            numeros = cnpj.substring(0, tamanho);
            soma = 0;
            pos = tamanho - 7;
            for (i = tamanho; i >= 1; i--) {
                soma += numeros.charAt(tamanho - i) * pos--;
                if (pos < 2) pos = 9;
            }
            resultado = soma % 11 < 2 ? 0 : 11 - soma % 11;
            return resultado === parseInt(digitos.charAt(1));
        },

        /**
         * Valida CEP (apenas formato)
         */
        cep: function (cep) {
            cep = cep.replace(/\D/g, '');
            return cep.length === 8;
        },

        /**
         * Valida telefone brasileiro
         */
        telefone: function (tel) {
            tel = tel.replace(/\D/g, '');
            return tel.length >= 10 && tel.length <= 11;
        }
    };

    /**
     * Inicializa as máscaras nos campos
     */
    function init() {
        // Máscaras automáticas por classe CSS
        var maskSelectors = {
            '.mask-cpf, [data-mask="cpf"], input[name*="cpf" i]': 'cpf',
            '.mask-cnpj, [data-mask="cnpj"], input[name*="cnpj" i]': 'cnpj',
            '.mask-cpf-cnpj, [data-mask="cpf-cnpj"]': 'cpfCnpj',
            '.mask-telefone, .mask-phone, [data-mask="telefone"], input[name*="telephone" i], input[name*="telefone" i], input[name*="fax" i]': 'telefone',
            '.mask-cep, [data-mask="cep"], input[name*="postcode" i], input[name*="cep" i]': 'cep',
            '.mask-placa, [data-mask="placa"]': 'placa',
            '.mask-cartao, [data-mask="cartao"]': 'cartao',
            '.mask-data, [data-mask="data"]': 'data',
            '.mask-moeda, [data-mask="moeda"]': 'moeda',
            '.mask-numero, [data-mask="numero"]': 'numero'
        };

        $.each(maskSelectors, function (selector, maskType) {
            $(document).on('input', selector, function () {
                var $input = $(this);
                var value = $input.val();
                var cursorPos = this.selectionStart;
                var oldLength = value.length;

                $input.val(masks[maskType](value));

                // Ajusta posição do cursor
                var newLength = $input.val().length;
                var newPos = cursorPos + (newLength - oldLength);
                if (this.setSelectionRange) {
                    this.setSelectionRange(newPos, newPos);
                }
            });

            // Aplica máscara em valores existentes
            $(selector).each(function () {
                var $input = $(this);
                if ($input.val()) {
                    $input.val(masks[maskType]($input.val()));
                }
            });
        });

        // Validação em tempo real
        var cpfErrorTemplate = '<div class="field-error field-error-inline">CPF inválido</div>',
            cnpjErrorTemplate = '<div class="field-error field-error-inline">CNPJ inválido</div>';

        $(document).on('blur', '.mask-cpf, [data-mask="cpf"], input[name*="cpf" i]', function () {
            var $input = $(this);
            var value = $input.val();
            if (value && !validators.cpf(value)) {
                $input.addClass('mage-error');
                if (!$input.next('.field-error').length) {
                    $input.after(cpfErrorTemplate);
                }
            } else {
                $input.removeClass('mage-error');
                $input.next('.field-error').remove();
            }
        });

        $(document).on('blur', '.mask-cnpj, [data-mask="cnpj"], input[name*="cnpj" i]', function () {
            var $input = $(this);
            var value = $input.val();
            if (value && !validators.cnpj(value)) {
                $input.addClass('mage-error');
                if (!$input.next('.field-error').length) {
                    $input.after(cnpjErrorTemplate);
                }
            } else {
                $input.removeClass('mage-error');
                $input.next('.field-error').remove();
            }
        });

        // Busca CEP automática
        $(document).on('blur', '.mask-cep, [data-mask="cep"], input[name*="postcode" i]', function () {
            var $input = $(this);
            var cep = $input.val().replace(/\D/g, '');

            if (cep.length === 8) {
                // Busca via ViaCEP
                $.ajax({
                    url: 'https://viacep.com.br/ws/' + cep + '/json/',
                    dataType: 'json',
                    timeout: 5000,
                    success: function (data) {
                        if (!data.erro) {
                            // Preenche campos de endereço
                            var $form = $input.closest('form');
                            var streetField = $form.find('input[name*="street"]').first();
                            var cityField = $form.find('input[name*="city"]');
                            var regionField = $form.find('select[name*="region_id"], input[name*="region"]');
                            var neighborhoodField = $form.find('input[name*="street"][name*="[1]"], input[name*="neighborhood"], input[name*="bairro"]');

                            if (streetField.length && !streetField.val()) {
                                streetField.val(data.logradouro);
                            }
                            if (neighborhoodField.length && !neighborhoodField.val()) {
                                neighborhoodField.val(data.bairro);
                            }
                            if (cityField.length && !cityField.val()) {
                                cityField.val(data.localidade).trigger('change');
                            }
                            if (regionField.length) {
                                // Mapeia UF para region_id do Magento
                                var ufToRegionId = {
                                    'AC': 485, 'AL': 486, 'AP': 487, 'AM': 488, 'BA': 489,
                                    'CE': 490, 'DF': 491, 'ES': 492, 'GO': 493, 'MA': 494,
                                    'MT': 495, 'MS': 496, 'MG': 497, 'PA': 498, 'PB': 499,
                                    'PR': 500, 'PE': 501, 'PI': 502, 'RJ': 503, 'RN': 504,
                                    'RS': 505, 'RO': 506, 'RR': 507, 'SC': 508, 'SP': 509,
                                    'SE': 510, 'TO': 511
                                };
                                if (regionField.is('select')) {
                                    regionField.val(ufToRegionId[data.uf]).trigger('change');
                                } else {
                                    regionField.val(data.uf).trigger('change');
                                }
                            }
                        }
                    }
                });
            }
        });

        console.log('[Brasil Masks] Máscaras inicializadas');
    }

    // Expõe API pública
    return {
        masks: masks,
        validators: validators,
        init: init
    };
});
