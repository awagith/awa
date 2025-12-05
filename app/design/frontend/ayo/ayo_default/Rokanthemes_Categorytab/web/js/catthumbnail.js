define([
    'jquery',
    'rokanthemes/owl'
], function ($) {
    'use strict';

    return function (config, element) {
        $(element).owlCarousel({
            autoPlay : false,
            items : 6,
            itemsDesktop : [1366,4],
            itemsDesktopSmall : [1199,3],
            itemsTablet: [768,2],
            itemsMobile : [480,1],
            slideSpeed : 500,
            paginationSpeed : 500,
            rewindSpeed : 500,
            navigation : true,
            stopOnHover : true,
            pagination :false,
            scrollPerPage:true,
            afterAction: function(el){
                this.$owlItems.removeClass('first-active');
                this.$owlItems .eq(this.currentItem).addClass('first-active');
            }
        });
    };
});
