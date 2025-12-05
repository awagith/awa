define([
    'jquery',
    'mage/mage',
    'rokanthemes/owl',
    'rokanthemes/lazyloadimg'
], function ($) {
    'use strict';

    return function (config, element) {
        var identify = config.identify;
        
        $(".tab_content_" + identify).hide();
        $(".tab_content_" + identify + ":first").show(); 

        $("ul.tabs-" + identify + " li").click(function() {
            $("ul.tabs-" + identify + " li").removeClass("active");
            $(this).addClass("active");
            $(".tab_content_" + identify).hide();
            var activeTab = $(this).attr("rel"); 
            $("#" + activeTab).show();
            $("img.lazy").lazyload({
                skip_invisible: false
            });
        });
        
        // Preserving original selector, though it looks suspicious (cat_special vs cat_special32)
        $(".cat_special32").owlCarousel({
            lazyLoad:true,
            items : 6,
            itemsDesktop : [1366, 4],
            itemsDesktopSmall : [1199,4],
            itemsTablet : [991, 3],
            itemsMobile : [680, 2],
            navigation : false,
            pagination : false,
            afterAction: function(el){
                this.$owlItems.removeClass('first-active')
                this.$owlItems .eq(this.currentItem).addClass('first-active')  
            }
        });

        $(".loadmore").click(function(){
            $(".cat_1 .cat_special .product_row").show();
        });
    };
});
