/**
 * JavaScript para campos brasileiros de cliente
 * Alterna entre PF/PJ e aplica máscaras
 */
define([
    'jquery',
    'mage/validation'
], function ($) {
    'use strict';

    return function () {
        $(document).ready(function () {
            var $personType = $('#person_type');
            var $pfFields = $('.person-fisica-fields');
            var $pjFields = $('.person-juridica-fields');
            var $cpfField = $('#cpf');
            var $cnpjField = $('#cnpj');

            // Toggle entre PF e PJ
            function togglePersonType() {
                var type = $personType.val();
                
                if (type === 'pf') {
                    $pfFields.show();
                    $pjFields.hide();
                    
                    // Ajusta validação
                    $cpfField.addClass('required-entry').attr('data-validate', '{required:true, "validate-cpf":true}');
                    $cnpjField.removeClass('required-entry').removeAttr('data-validate');
                    $('#company_name').removeClass('required-entry');
                } else {
                    $pfFields.hide();
                    $pjFields.show();
                    
                    // Ajusta validação
                    $cpfField.removeClass('required-entry').removeAttr('data-validate');
                    $cnpjField.addClass('required-entry').attr('data-validate', '{required:true, "validate-cnpj":true}');
                    $('#company_name').addClass('required-entry');
                }
            }

            // Aplica máscara de CPF
            function maskCpf(value) {
                value = value.replace(/\D/g, '');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                return value;
            }

            // Aplica máscara de CNPJ
            function maskCnpj(value) {
                value = value.replace(/\D/g, '');
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
                value = value.replace(/\.(\d{3})(\d)/, '.$1/$2');
                value = value.replace(/(\d{4})(\d)/, '$1-$2');
                return value;
            }

            // Validação de CPF
            $.validator.addMethod(
                'validate-cpf',
                function (value) {
                    if (!value) return true;
                    
                    var cpf = value.replace(/\D/g, '');
                    
                    if (cpf.length !== 11) return false;
                    if (/^(\d)\1{10}$/.test(cpf)) return false;

                    var sum = 0, remainder;
                    
                    for (var i = 1; i <= 9; i++) {
                        sum += parseInt(cpf.substring(i - 1, i)) * (11 - i);
                    }
                    remainder = (sum * 10) % 11;
                    if (remainder === 10 || remainder === 11) remainder = 0;
                    if (remainder !== parseInt(cpf.substring(9, 10))) return false;

                    sum = 0;
                    for (var i = 1; i <= 10; i++) {
                        sum += parseInt(cpf.substring(i - 1, i)) * (12 - i);
                    }
                    remainder = (sum * 10) % 11;
                    if (remainder === 10 || remainder === 11) remainder = 0;
                    
                    return remainder === parseInt(cpf.substring(10, 11));
                },
                $.mage.__('CPF inválido. Verifique os números digitados.')
            );

            // Validação de CNPJ
            $.validator.addMethod(
                'validate-cnpj',
                function (value) {
                    if (!value) return true;
                    
                    var cnpj = value.replace(/\D/g, '');
                    
                    if (cnpj.length !== 14) return false;
                    if (/^(\d)\1{13}$/.test(cnpj)) return false;

                    var length = cnpj.length - 2;
                    var numbers = cnpj.substring(0, length);
                    var digits = cnpj.substring(length);
                    var sum = 0;
                    var pos = length - 7;

                    for (var i = length; i >= 1; i--) {
                        sum += numbers.charAt(length - i) * pos--;
                        if (pos < 2) pos = 9;
                    }
                    
                    var result = sum % 11 < 2 ? 0 : 11 - sum % 11;
                    if (result !== parseInt(digits.charAt(0))) return false;

                    length = length + 1;
                    numbers = cnpj.substring(0, length);
                    sum = 0;
                    pos = length - 7;

                    for (var i = length; i >= 1; i--) {
                        sum += numbers.charAt(length - i) * pos--;
                        if (pos < 2) pos = 9;
                    }
                    
                    result = sum % 11 < 2 ? 0 : 11 - sum % 11;
                    
                    return result === parseInt(digits.charAt(1));
                },
                $.mage.__('CNPJ inválido. Verifique os números digitados.')
            );

            // Event listeners
            $personType.on('change', togglePersonType);
            
            $cpfField.on('input', function () {
                this.value = maskCpf(this.value);
            });
            
            $cnpjField.on('input', function () {
                this.value = maskCnpj(this.value);
            });

            // Inicialização
            togglePersonType();
        });
    };
});
