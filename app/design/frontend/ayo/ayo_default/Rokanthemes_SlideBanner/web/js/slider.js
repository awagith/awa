define([
    'jquery',
    'rokanthemes/owl'
], function ($) {
    'use strict';

    return function (config, element) {
        var sliderId = config.sliderId;
        var sliderSettings = config.sliderSettings;
        var sliderMobileSettings = config.sliderMobileSettings;

        if (sliderMobileSettings) {
            $(element).find(".slider_" + sliderId + "_mobile .owl").owlCarousel(sliderMobileSettings);
        }
        
        $(element).find(".slider_" + sliderId + " .owl").owlCarousel(sliderSettings);

        // Function to animate slider captions 
        function doAnimations(elems) {
            var animEndEv = 'webkitAnimationEnd animationend';
            elems.each(function () {
                var $this = $(this),
                    $animationType = $this.data('animation');
                $this.addClass($animationType).one(animEndEv, function () {
                    $this.removeClass($animationType);
                });
            });
        }

        var $myCarousel = $(element).find('.wrapper_slider');
        var $firstAnimatingElems = $myCarousel.find('.text-banner').find("[data-animation ^= 'animated']");
        doAnimations($firstAnimatingElems);
    };
});
