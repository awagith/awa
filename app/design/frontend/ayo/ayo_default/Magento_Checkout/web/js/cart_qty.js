define([
    'jquery',
    'mage/mage'
], function ($) {
    'use strict';

    return function (config, element) {
        $(".qty-down-fixed-onclick-page-cart").click(function() {
            var number_click = parseInt($(this).closest('div.qty').find('.input-text.qty').attr('data-qty'));
            var val_input = $(this).closest('div.qty').find('.input-text.qty').val();
            val_input = parseInt(val_input);
            if(val_input <= number_click){
                val_input = number_click;
            }
            else{
                val_input = val_input - number_click;
            }
            $(this).closest('div.qty').find('.input-text.qty').val(val_input);
            return false;
        });
        $(".qty-up-fixed-onclick-page-cart").click(function() {
            var number_click_2 = parseInt($(this).closest('div.qty').find('.input-text.qty').attr('data-qty'));
            var val_input = $(this).closest('div.qty').find('.input-text.qty').val();
            val_input = parseInt(val_input);
            val_input = val_input + number_click_2;
            $(this).closest('div.qty').find('.input-text.qty').val(val_input);
            return false;
        });
    };
});
