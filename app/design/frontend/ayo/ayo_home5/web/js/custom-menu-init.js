define([
    'jquery',
    'rokanthemes/custommenu',
    'mage/menu'
], function ($) {
    'use strict';

    var ROOT_SELECTOR = '.navigation.custommenu.main-nav';

    function normalizePath(path) {
        if (!path) {
            return '/';
        }

        path = String(path).replace(/[#?].*$/, '');

        if (path.length > 1 && path.slice(-1) === '/') {
            path = path.slice(0, -1);
        }

        return path || '/';
    }

    function resolveMenuRoot($scope) {
        var $menuRoot = $scope.is(ROOT_SELECTOR) ? $scope : $scope.closest(ROOT_SELECTOR);

        if (!$menuRoot.length) {
            $menuRoot = $scope.filter('.custommenu').add($scope.find('.custommenu')).first();
        }

        if (!$menuRoot.length) {
            $menuRoot = $(ROOT_SELECTOR).first();
        }

        return $menuRoot;
    }

    function initMagentoMenu($menuList) {
        if (!$menuList.length || $menuList.data('mageMenuInit')) {
            return;
        }

        $menuList.menu({
            responsive: true,
            expanded: true,
            position: {
                my: 'left top',
                at: 'left bottom'
            }
        });

        $menuList.data('mageMenuInit', 1);
    }

    function bindTouchToggles($menuRoot) {
        if ($menuRoot.data('awaTouchToggleBound')) {
            return;
        }

        $menuRoot.on('click.awaMainNav', '.open-children-toggle', function (event) {
            var $toggle = $(this);
            var $item = $toggle.closest('li');
            var $submenu = $item.children('.submenu, .groupmenu, .subchildmenu').first();
            var isOpen;

            if (!$submenu.length) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            isOpen = $submenu.hasClass('opened') || $submenu.hasClass('active') || $item.hasClass('active') || $item.hasClass('_active');

            if (isOpen) {
                $submenu.removeClass('opened active').hide();
                $item.removeClass('active _active');
                return;
            }

            $item.siblings('.active, ._active')
                .removeClass('active _active')
                .children('.submenu, .groupmenu, .subchildmenu')
                .removeClass('opened active')
                .hide();

            $submenu.addClass('opened active').show();
            $item.addClass('active _active');
        });

        $menuRoot.data('awaTouchToggleBound', 1);
    }

    function markCurrentMenuItem($menuRoot) {
        var currentPath = normalizePath(window.location.pathname);

        $menuRoot.find('a[aria-current="page"]').removeAttr('aria-current');
        $menuRoot.find('li.awa-has-current-descendant').removeClass('awa-has-current-descendant');

        $menuRoot.find('a[href]').each(function () {
            var $link = $(this);
            var href = $link.attr('href');
            var linkUrl;
            var linkPath;

            if (!href || href.charAt(0) === '#') {
                return;
            }

            try {
                linkUrl = new URL(href, window.location.origin);
            } catch (error) {
                return;
            }

            if (linkUrl.origin !== window.location.origin) {
                return;
            }

            linkPath = normalizePath(linkUrl.pathname);

            if (linkPath !== currentPath) {
                return;
            }

            $link.attr('aria-current', 'page');
            $link.closest('li').parents('li').addClass('awa-has-current-descendant');
        });
    }

    return function (config, element) {
        var $scope = $(element);
        var $menuRoot = resolveMenuRoot($scope);
        var $menuList = $menuRoot.children('ul').first();
        var useMagentoMenu = !!(config && config.useMagentoMenu);

        if (!$menuRoot.length || $menuRoot.data('awaMainNavInit')) {
            return;
        }

        if (useMagentoMenu) {
            initMagentoMenu($menuList);
        } else if (typeof $menuRoot.CustomMenu === 'function') {
            $menuRoot.CustomMenu();
        } else {
            initMagentoMenu($menuList);
        }

        bindTouchToggles($menuRoot);
        markCurrentMenuItem($menuRoot);
        $menuRoot.addClass('awa-nav-ready').data('awaMainNavInit', 1);
    };
});
