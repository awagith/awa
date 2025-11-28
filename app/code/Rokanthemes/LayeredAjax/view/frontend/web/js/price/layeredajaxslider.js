define([
    'jquery',
    'Rokanthemes_LayeredAjax/js/price/ion.rangeSlider.min',
    'jquery/ui',
    'Rokanthemes_LayeredAjax/js/layeredajax'
], function($,ionRangeSlider) {
    "use strict";

    $.widget('rokanthemes.layeredAjaxSlider', $.rokanthemes.layeredAjax, {
        options: {
            sliderElement: '#price-range-slider',
			minPrice: $("#price-range-slider").data("min"),
            maxPrice: $("#price-range-slider").data("max")
        },
        _create: function () {
            var self = this;
            $(this.options.sliderElement).ionRangeSlider({
				type: "double",
                min: self.options.minPrice,
                max: self.options.maxPrice, 
                from: self.options.selectedFrom,
                to: self.options.selectedTo,
                prettify_enabled: true,
                prefix: self.options.currency,
                grid: true,
                onFinish: function(obj) {
					self.ajaxSubmit(self.getUrl(obj.from, obj.to));
                }
            });
        }, 

        getUrl: function(from, to){
            return this.options.ajaxUrl.replace(encodeURI('{price_start}'), from).replace(encodeURI('{price_end}'), to);
        },
    });

    return $.rokanthemes.layeredAjaxSlider;
});
