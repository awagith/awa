define([
    'jquery',
    'rokanthemes/owl'
], function ($) {
    'use strict';

    $(document).ready(function() {
        $(".block-content.brandowl-play >ul").owlCarousel({
            lazyLoad: true,
            items: 7,
            itemsDesktop: [1366, 5],
            itemsDesktopSmall: [991, 3],
            itemsTablet: [767, 2],
            itemsMobile: [479, 1],
            navigation: true,
            afterAction: function(el){
                this.$owlItems.removeClass('first-active');
                this.$owlItems.eq(this.currentItem).addClass('first-active');
            }
        });

        responsiveResize();
        $(window).resize(responsiveResize);
    });

    var responsiveflag = false;

    function responsiveResize() {
        if ($(window).width() <= 767 && responsiveflag == false) {
            accordionFooter('enable');
            responsiveflag = true;
        } else if ($(window).width() >= 768) {
            accordionFooter('disable');
            responsiveflag = false;
        }
    }

    function accordionFooter(status) {
        if (status == 'enable') {
            $('.velaFooterMenu h4.velaFooterTitle').on('click', function(e) {
                $(this).toggleClass('active').parent().find('.velaContent').stop().slideToggle('medium');
                e.preventDefault();
            });
        } else {
            $('.velaFooterMenu h4.velaFooterTitle').removeClass('active').off().parent().find('.velaContent').slideDown('fast');
        }
    }
});
