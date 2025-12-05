define([
    'jquery',
    'rokanthemes/owl'
], function ($) {
    'use strict';

    return function (config, element) {
        var identify = config.identify;
        var $element = $(element);

        // Scope searches to the current element to avoid conflicts
        $element.find(".tab_content_" + identify).hide();
        $element.find(".tab_content_" + identify + ":first").show();

        $element.find("ul.tabs-" + identify + " li").click(function() {
            $element.find("ul.tabs-" + identify + " li").removeClass("active");
            $(this).addClass("active");
            $element.find(".tab_content_" + identify).hide();
            var activeTab = $(this).attr("rel");
            $element.find("#" + activeTab).show();
        });

        $element.find(".cat_home2").owlCarousel({
            lazyLoad: true,
            items: 6,
            itemsDesktop: [1366, 4],
            itemsDesktopSmall: [1199, 3],
            itemsTablet: [991, 3],
            itemsMobile: [680, 2],
            navigation: true,
            pagination: false,
            afterAction: function(el){
                this.$owlItems.removeClass('first-active');
                this.$owlItems.eq(this.currentItem).addClass('first-active');
            }
        });
    };
});
