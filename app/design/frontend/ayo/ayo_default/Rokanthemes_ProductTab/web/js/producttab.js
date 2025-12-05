define([
    'jquery',
    'rokanthemes/owl'
], function ($) {
    'use strict';

    return function (config, element) {
        var $element = $(element);
        var $tabContent = $element.find('.productTabContent');

        // Initialize Owl Carousel
        $tabContent.owlCarousel({
            lazyLoad: true,
            autoPlay: false,
            items: 3,
            itemsDesktop: [1366, 3],
            itemsDesktopSmall: [1199, 2],
            itemsTablet: [991, 2],
            itemsMobile: [680, 1],
            slideSpeed: 500,
            paginationSpeed: 500,
            rewindSpeed: 500,
            navigation: true,
            stopOnHover: true,
            pagination: true,
            scrollPerPage: true,
            afterAction: function (el) {
                this.$owlItems.removeClass('first-active');
                this.$owlItems.eq(this.currentItem).addClass('first-active');
            }
        });

        // Tab switching logic
        $element.find(".tab_content").hide();
        $element.find(".tab_content:first").show();
        $element.find("ul.tabs li:first").addClass("active");

        $element.find("ul.tabs li").click(function() {
            $element.find("ul.tabs li").removeClass("active");
            $(this).addClass("active");
            $element.find(".tab_content").hide();
            var activeTab = $(this).attr("rel");
            $element.find("#" + activeTab).fadeIn();
        });
    };
});
