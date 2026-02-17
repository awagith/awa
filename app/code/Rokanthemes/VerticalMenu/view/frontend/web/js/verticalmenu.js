;(function($, window, document, undefined) {
    $.fn.VerticalMenu = function() {
        var $nav = $(this);
        var isSideMenu = $nav.hasClass('side-verticalmenu');

        /* --------------------------------------------------------
         *  Classic child‑submenu positioning (nested levels)
         * ------------------------------------------------------ */
        $nav.find("li.classic .subchildmenu > li.parent").on("mouseenter", function(){
            var $popup = $(this).children("ul.subchildmenu");
            var wWidth = $(window).innerWidth();
            if ($popup.length) {
                var pos = $(this).offset();
                var cWidth = $popup.outerWidth();
                if (wWidth <= pos.left + $(this).outerWidth() + cWidth) {
                    $popup.css({"left": "auto", "right": "100%", "border-radius": "6px 0 6px 6px"});
                } else {
                    $popup.css({"left": "100%", "right": "auto", "border-radius": "0 6px 6px 6px"});
                }
            }
        });

        /* --------------------------------------------------------
         *  Static / classic parent submenu — non‑side menus only
         * ------------------------------------------------------ */
        if (!isSideMenu) {
            $nav.find("li.staticwidth.parent, li.classic.parent").on("mouseenter", function(){
                var $popup = $(this).children(".submenu");
                var wWidth = $(window).innerWidth();
                var wHeight = $(window).innerHeight();
                if ($popup.length) {
                    var pos = $(this).offset();
                    var cWidth = $popup.outerWidth();
                    var cHeight = $popup.outerHeight();
                    if (wWidth <= pos.left + $(this).outerWidth() + cWidth) {
                        $popup.css({"left": "auto", "right": "0", "border-radius": "6px 0 6px 6px"});
                    } else {
                        $popup.css({"left": "0", "right": "auto", "border-radius": "0 6px 6px 6px"});
                    }
                    var scrollTop = $(window).scrollTop();
                    var topRelat = pos.top - scrollTop;
                    if (topRelat + cHeight > wHeight) {
                        var maxTop = Math.max(0, wHeight - cHeight - 10);
                        $popup.css({"top": (maxTop - topRelat + scrollTop) + "px"});
                    } else {
                        $popup.css({"top": ""});
                    }
                }
            });
        } else {
            /* Side vertical menu — submenu positioned via CSS, handle overflow only */
            $nav.find("li.level0.parent").on("mouseenter", function(){
                var $popup = $(this).children(".submenu");
                var wWidth = $(window).innerWidth();
                if ($popup.length) {
                    var navOffset = $nav.offset();
                    var navWidth = $nav.outerWidth();
                    var cWidth = $popup.outerWidth();
                    if (navOffset.left + navWidth + cWidth > wWidth) {
                        $popup.css({"left": "auto", "right": "100%", "border-radius": "6px 0 0 6px"});
                    }
                }
            });
        }

        /* --------------------------------------------------------
         *  Reset on resize
         * ------------------------------------------------------ */
        var resizeTimer;
        $(window).on("resize", function(){
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function(){
                if (!isSideMenu) {
                    $nav.find("li.classic .submenu, li.staticwidth .submenu, li.classic .subchildmenu .subchildmenu").each(function(){
                        $(this).css({"left": "", "right": "", "top": ""});
                    });
                }
            }, 150);
        });

        /* --------------------------------------------------------
         *  Mobile: open‑children‑toggle for level 0
         * ------------------------------------------------------ */
        $nav.find("li.ui-menu-item > .open-children-toggle").off("click").on("click", function(e){
            e.preventDefault();
            e.stopPropagation();
            var $parent = $(this).parent();
            var $submenu = $parent.children(".submenu");
            var $link = $parent.children("a");
            var isOpen = $submenu.hasClass("opened");

            $parent.siblings().children(".submenu").removeClass("opened");
            $parent.siblings().children("a").removeClass("ui-state-active");

            if (!isOpen) {
                $submenu.addClass("opened");
                $link.addClass("ui-state-active");
            } else {
                $submenu.removeClass("opened");
                $link.removeClass("ui-state-active");
            }
        });

        /* --------------------------------------------------------
         *  Mobile: subchild submenu toggle
         * ------------------------------------------------------ */
        $nav.find(".submenu .subchildmenu li.ui-menu-item > .open-children-toggle").off("click").on("click", function(e){
            e.preventDefault();
            e.stopPropagation();
            var $parent = $(this).parent();
            var $sub = $parent.children(".subchildmenu");
            var $link = $parent.children("a");
            if (!$sub.hasClass("opened")) {
                $sub.addClass("opened").slideDown(200);
                $link.addClass("ui-state-active");
            } else {
                $sub.removeClass("opened").slideUp(200);
                $link.removeClass("ui-state-active");
            }
        });
    };
})(window.Zepto || window.jQuery, window, document); 