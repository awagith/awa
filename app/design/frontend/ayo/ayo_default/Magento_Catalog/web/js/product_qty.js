define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        $('.qty-up').on('click',function(event){
            event.preventDefault();
            var input_ = $(this).closest('.info-qty').find('#qty');
            var qtyval = parseInt(input_.val(),10);
            qtyval=qtyval+1;
            input_.val(qtyval);
        });
        $('.qty-down').on('click',function(event){
            event.preventDefault();
            var input_ = $(this).closest('.info-qty').find('#qty');
            var qtyval = parseInt(input_.val(),10);
            qtyval=qtyval-1;
            if(qtyval>1){
                input_.val(qtyval);
            }else{
                qtyval=1;
                input_.val(qtyval);
            }
        });
    };
});
