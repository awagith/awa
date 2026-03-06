define([
    'jquery',
    'js/awa-category-icons'
], function ($, categoryIcons) {
    'use strict';

    const MUELLER_ICON_OPTIONS = { cssClass: 'awa-mueller-cat-icon', strokeWidth: '1.7', fallback: 'package' };

    return function (config, element) {
        const $nav = $(element);
        const settings = $.extend({
            desktopBreakpoint: 992,
            openModeDesktop: 'hover',
            openModeMobile: 'click',
            mobileMenuMode: 'accordion',
            mobilePanelAnimationMs: 240,
            mobilePanelMaxDepth: 3,
            disableOverlay: true,
            limitShow: 0,
            keepOpenOnHomeDesktop: false
        }, config || {});
        const uid = (Date.now().toString(36) + Math.random().toString(36).slice(2, 8)).replace(/[^a-z0-9_-]/gi, '');
        const ns = '.awaMuellerMenu-' + uid;
        const $trigger = $nav.find('[data-role="awa-vertical-menu-trigger"], .title-category-dropdown').first();
        const $list = $nav.children('ul.togge-menu.list-category-dropdown').first();
        const $expandLink = $nav.find('.vm-toggle-categories').first();
        const configuredLimitShow = parseInt(settings.limitShow, 10) || 0;
        let desktopPinned = false;
        let overlayGuardObserver = null;
        let listStyleGuardObserver = null;
        let listStyleGuardBusy = false;
        let mobilePanelStack = [];
        let mobilePanelsPrepared = false;

        if (!$nav.length || !$trigger.length || !$list.length || $nav.data('awaMuellerMenuInit')) {
            return;
        }

        function isDesktop() {
            return window.innerWidth >= settings.desktopBreakpoint;
        }

        function isHomeContext() {
            const body = document.body;
            if (!body) {
                return false;
            }

            return body.classList.contains('cms-index-index')
                || body.classList.contains('cms-home')
                || body.classList.contains('cms-homepage_ayo_home5')
                || body.classList.contains('cms-homepage_ayo_home5_demo_stage');
        }

        function keepDesktopMenuExpanded() {
            return isDesktop() && !!settings.keepOpenOnHomeDesktop && isHomeContext();
        }

        function isPanelSlideMobileMode() {
            return settings.openModeMobile === 'click' && settings.mobileMenuMode === 'panel-slide';
        }

        function getMobilePanelAnimationMs() {
            const value = parseInt(settings.mobilePanelAnimationMs, 10) || 240;

            return Math.max(120, value);
        }

        function getMobilePanelMaxDepth() {
            const value = parseInt(settings.mobilePanelMaxDepth, 10) || 3;

            return Math.max(1, value);
        }

        function getRootList() {
            return $list;
        }

        function getTopItems() {
            return getRootList().children('li.ui-menu-item.level0');
        }

        function getParentItems() {
            return getRootList().children('li.ui-menu-item.level0.parent');
        }

        function injectCategoryIcons() {
            getTopItems().each(function () {
                const $item = $(this);
                const $link = $item.children('a.level-top');

                if (!$link.length) {
                    return;
                }

                $link.find('.awa-mueller-cat-icon, .menu-thumb-icon, img.menu-thumb-icon').remove();
                const titleText = $.trim($link.find('> span').first().text()) || $.trim($link.text());
                const iconType = categoryIcons.resolveIconType(titleText, 'package');
                $item.attr('data-awa-icon', iconType);
                $link.prepend(categoryIcons.buildIconSvg(iconType, MUELLER_ICON_OPTIONS));
            });
        }

        function getLevelPanels($item) {
            return $item.children('.submenu, .level0.submenu, ul.level0, .subchildmenu');
        }

        function getFirstLevelPanel($item) {
            return getLevelPanels($item).first();
        }

        function normalizeFlyoutPanel($item) {
            const $panel = getFirstLevelPanel($item);

            if (!$panel.length || !isDesktop()) {
                return;
            }

            const $menuGrid = $panel.find('> .row > .subchildmenu.navigation__inner-list--level1').first();

            $menuGrid.children('li.navigation__inner-item.col-1').each(function () {
                this.style.setProperty('width', '100%', 'important');
                this.style.setProperty('max-width', '100%', 'important');
                this.style.setProperty('min-width', '0', 'important');
            });

            $menuGrid.children('li.navigation__inner-item.img-subcategory').each(function () {
                const image = this.querySelector('img');

                this.style.setProperty('display', 'block', 'important');
                this.style.setProperty('position', 'absolute', 'important');
                this.style.setProperty('top', '0', 'important');
                this.style.setProperty('left', 'auto', 'important');
                this.style.setProperty('right', '0', 'important');
                this.style.setProperty('width', '100%', 'important');
                this.style.setProperty('max-width', '100%', 'important');
                this.style.setProperty('min-width', '0', 'important');
                this.style.setProperty('height', '100%', 'important');
                this.style.setProperty('margin', '0', 'important');
                this.style.setProperty('padding', '0', 'important');
                this.style.setProperty('box-sizing', 'border-box');

                if (image) {
                    image.style.setProperty('opacity', '1', 'important');
                    image.style.setProperty('animation', 'none', 'important');
                    image.style.setProperty('width', '100%', 'important');
                    image.style.setProperty('max-width', '100%', 'important');
                    image.style.setProperty('height', '100%', 'important');
                    image.style.setProperty('object-fit', 'cover', 'important');
                    image.style.setProperty('margin', '0', 'important');
                    image.style.setProperty('padding', '0', 'important');
                }
            });
        }

        function getLimitShow() {
            return parseInt($list.attr('data-limit-show'), 10) || configuredLimitShow;
        }

        function setDisplayImportant($element, visible, displayValue) {
            const element = $element && $element.get ? $element.get(0) : null;

            if (!element) {
                return;
            }

            if (visible) {
                element.style.setProperty('display', displayValue || 'block', 'important');
                return;
            }

            element.style.setProperty('display', 'none', 'important');
        }

        function resetRootListInlineStyles() {
            const listElement = getRootList().get(0);

            if (!listElement) {
                return;
            }

            listElement.style.removeProperty('display');
            listElement.style.removeProperty('max-height');
            listElement.style.removeProperty('overflow');
            listElement.style.removeProperty('overflow-x');
            listElement.style.removeProperty('overflow-y');
            listElement.style.removeProperty('transform');
            listElement.style.removeProperty('transition-duration');
        }

        function applyDesktopListInlineState(opened) {
            const listElement = getRootList().get(0);

            if (!listElement || !isDesktop()) {
                return;
            }

            if (opened) {
                listElement.style.setProperty('display', 'block', 'important');
                listElement.style.setProperty('max-height', '576px', 'important');
                listElement.style.setProperty('overflow', 'visible', 'important');
                listElement.style.setProperty('overflow-x', 'visible', 'important');
                listElement.style.setProperty('overflow-y', 'visible', 'important');
                return;
            }

            listElement.style.setProperty('display', 'none', 'important');
            listElement.style.setProperty('max-height', 'none', 'important');
            listElement.style.setProperty('overflow', 'visible', 'important');
            listElement.style.setProperty('overflow-x', 'visible', 'important');
            listElement.style.setProperty('overflow-y', 'visible', 'important');
        }

        function enforceDesktopListInlineState() {
            if (!isDesktop()) {
                return;
            }

            if ($nav.hasClass('awa-menu-expanded') || getRootList().hasClass('menu-open') || getRootList().hasClass('vmm-open')) {
                applyDesktopListInlineState(true);
                return;
            }

            applyDesktopListInlineState(false);
        }

        function setRootSubmenuState(opened) {
            getRootList().toggleClass('awa-mueller-submenu-open', !!opened);
        }

        function setMenuOpen(opened) {
            getRootList().toggleClass('menu-open', !!opened);
            getRootList().toggleClass('vmm-open', !!opened);
            $nav.toggleClass('awa-menu-expanded', !!opened);
            $('body').toggleClass('awa-menu-open', !!opened);
            $trigger.toggleClass('active', !!opened).attr('aria-expanded', opened ? 'true' : 'false');
        }

        function disableOverlayArtifacts() {
            if (!settings.disableOverlay) {
                return;
            }

            $('.shadow_bkg, .shadow_bkg_show, .vmm-overlay').css({
                display: 'none',
                opacity: 0,
                visibility: 'hidden',
                pointerEvents: 'none'
            });

            $('body').removeClass('background_shadow background_shadow_show shadow_bkg_show');
        }

        function collapseSearchAutocomplete() {
            const $body = $('body');
            const $searchLayers = $(
                '.mst-searchautocomplete__autocomplete, ' +
                '.mst-searchautocomplete__result, ' +
                '.mst-searchautocomplete, ' +
                '.search-autocomplete, ' +
                '.searchsuite-autocomplete'
            );
            const $searchInput = $('#search, [data-awa-search-input="true"]').first();
            const $searchForm = $searchInput.closest('form, .block-search, .field.search, .control');

            if (!$searchLayers.length) {
                if ($searchInput.length) {
                    $searchInput.removeClass('searchautocomplete__active mst-searchautocomplete--active');
                    $searchInput.attr('aria-expanded', 'false');
                }
                return;
            }

            $body.removeClass('searchautocomplete__active mst-searchautocomplete--active');
            $searchForm.removeClass('searchautocomplete__active mst-searchautocomplete--active');

            $searchLayers.each(function () {
                this.style.setProperty('display', 'none', 'important');
                this.style.setProperty('visibility', 'hidden', 'important');
                this.style.setProperty('opacity', '0', 'important');
                this.style.setProperty('pointer-events', 'none', 'important');
            });

            if ($searchInput.length) {
                $searchInput.removeClass('searchautocomplete__active mst-searchautocomplete--active');
                $searchInput.attr('aria-expanded', 'false');
                $searchInput.trigger('blur');
            }
        }

        function setExpandedState($item, expanded) {
            const value = expanded ? 'true' : 'false';

            $item.attr('aria-expanded', value);
            $item.attr('data-open', expanded ? 'true' : 'false');
            $item.children('a.level-top').attr('aria-expanded', value);
            $item.children('a.navigation__inner-link').attr('aria-expanded', value);
            $item.children('.open-children-toggle').attr('aria-expanded', value);
        }

        function clearMobileItemState($item) {
            const $panel = getFirstLevelPanel($item);

            if (!$item || !$item.length) {
                return;
            }

            $item.removeClass('_active awa-mueller-open');
            $item.children('a').removeClass('ui-state-active');
            setExpandedState($item, false);

            if ($panel.length) {
                $panel.removeClass('opened awa-mobile-panel--active');
            }
        }

        function getItemMenuTitle($item) {
            const title = $.trim(
                $item.attr('data-menu-title')
                || $item.children('a').attr('data-menu-title')
                || ''
            );

            if (title !== '') {
                return title;
            }

            return $.trim($item.children('a').first().text());
        }

        function getParentPanelId($item) {
            const $parentPanel = $item.closest('ul[data-mobile-panel-id]');

            if ($parentPanel.length) {
                return $parentPanel.attr('data-mobile-panel-id') || 'root';
            }

            return 'root';
        }

        function getActiveMobilePanel() {
            if (mobilePanelStack.length) {
                return mobilePanelStack[mobilePanelStack.length - 1].panel;
            }

            return getRootList();
        }

        function applyMobilePanelDepth(depth) {
            const listElement = getRootList().get(0);
            const transformValue = `translate3d(${depth * -100}%, 0, 0)`;

            if (!listElement) {
                return;
            }

            listElement.style.setProperty('transform', transformValue, 'important');
            listElement.style.setProperty('transition-duration', `${getMobilePanelAnimationMs()}ms`, 'important');
            $nav.attr('data-mobile-depth', String(depth));
        }

        function syncMobilePanelClasses() {
            if (!isPanelSlideMobileMode()) {
                return;
            }

            getRootList()
                .addClass('awa-mobile-panel-host awa-mobile-panel awa-mobile-panel--root')
                .removeClass('awa-mobile-panel--active');

            const $allPanels = getRootList().find('.awa-mobile-panel');
            $allPanels.removeClass('awa-mobile-panel--active');
            $allPanels.each(function () {
                this.style.setProperty('pointer-events', 'none', 'important');
                this.style.setProperty('z-index', '1', 'important');
            });
            const $activePanel = getActiveMobilePanel();

            if ($activePanel.length) {
                $activePanel.addClass('awa-mobile-panel--active');
                $activePanel.get(0).style.setProperty('pointer-events', 'auto', 'important');
                $activePanel.get(0).style.setProperty('z-index', '3', 'important');
            }

            applyMobilePanelDepth(mobilePanelStack.length);
            setRootSubmenuState(mobilePanelStack.length > 0);
        }

        function resetMobilePanelStack() {
            if (!isPanelSlideMobileMode()) {
                return;
            }

            mobilePanelStack = [];
            getRootList().find('.ui-menu-item.parent, .ui-menu-item.level0.parent').each(function () {
                clearMobileItemState($(this));
            });
            syncMobilePanelClasses();
        }

        function prependMobilePanelHeader($panel, titleText) {
            let $header = $panel.children('.awa-mobile-panel__header');
            const title = $.trim(titleText);

            if (!$header.length) {
                $header = $(
                    '<div class="awa-mobile-panel__header">' +
                    '<button type="button" class="awa-mobile-panel__back" aria-label="Voltar">' +
                    '<span class="awa-mobile-panel__back-text">Voltar</span>' +
                    '</button>' +
                    '<span class="awa-mobile-panel__title"></span>' +
                    '</div>'
                );
                $panel.prepend($header);
            }

            $header.find('.awa-mobile-panel__title').text(title || 'Categorias');
        }

        function decorateMobilePanels() {
            if (!isPanelSlideMobileMode() || mobilePanelsPrepared) {
                return;
            }

            getRootList()
                .attr('data-mobile-panel-id', 'root')
                .attr('data-mobile-title', 'Categorias');

            const rootElement = getRootList().get(0);
            if (rootElement) {
                rootElement.style.setProperty('position', 'relative', 'important');
                rootElement.style.setProperty('overflow', 'hidden', 'important');
                rootElement.style.setProperty('min-height', '460px', 'important');
            }

            getRootList().find('.ui-menu-item.parent, .ui-menu-item.level0.parent').each(function () {
                const $item = $(this);
                const $panel = getFirstLevelPanel($item);

                if (!$panel.length) {
                    return;
                }

                const panelLevel = parseInt($panel.attr('data-level'), 10) || (parseInt($item.attr('data-level'), 10) || 0) + 1;
                if (panelLevel >= getMobilePanelMaxDepth()) {
                    return;
                }

                let panelId = $.trim($panel.attr('data-menu-id') || $panel.attr('id') || '');
                if (panelId === '') {
                    panelId = ($item.attr('data-menu-id') || $item.attr('data-menu') || 'menu-item') + '-panel';
                }

                const parentPanelId = getParentPanelId($item);
                const title = getItemMenuTitle($item);

                $panel
                    .addClass('awa-mobile-panel')
                    .attr('data-mobile-panel-id', panelId)
                    .attr('data-mobile-parent-id', parentPanelId)
                    .attr('data-mobile-title', title);

                const panelElement = $panel.get(0);
                if (panelElement) {
                    panelElement.style.setProperty('position', 'absolute', 'important');
                    panelElement.style.setProperty('top', '0', 'important');
                    panelElement.style.setProperty('left', '100%', 'important');
                    panelElement.style.setProperty('width', '100%', 'important');
                    panelElement.style.setProperty('height', '100%', 'important');
                    panelElement.style.setProperty('display', 'block', 'important');
                    panelElement.style.setProperty('z-index', '1', 'important');
                }

                prependMobilePanelHeader($panel, title);
            });

            mobilePanelsPrepared = true;
            syncMobilePanelClasses();
        }

        function trimMobileStackToParent(parentPanelId) {
            let targetIndex = -1;

            if (parentPanelId !== 'root') {
                for (let idx = 0; idx < mobilePanelStack.length; idx += 1) {
                    if ((mobilePanelStack[idx].panel.attr('data-mobile-panel-id') || '') === parentPanelId) {
                        targetIndex = idx;
                        break;
                    }
                }
            }

            while (mobilePanelStack.length > targetIndex + 1) {
                clearMobileItemState(mobilePanelStack[mobilePanelStack.length - 1].item);
                mobilePanelStack.pop();
            }
        }

        function openMobilePanelForItem($item) {
            const $panel = getFirstLevelPanel($item);
            const panelLevel = parseInt($panel.attr('data-level'), 10) || (parseInt($item.attr('data-level'), 10) || 0) + 1;
            const parentPanelId = getParentPanelId($item);

            if (!$panel.length || panelLevel >= getMobilePanelMaxDepth()) {
                return false;
            }

            trimMobileStackToParent(parentPanelId);

            if (mobilePanelStack.length) {
                const currentPanel = mobilePanelStack[mobilePanelStack.length - 1].panel;
                if (currentPanel.length && currentPanel.get(0) === $panel.get(0)) {
                    return true;
                }
            }

            $item.siblings('.ui-menu-item.parent, .ui-menu-item.level0.parent').each(function () {
                clearMobileItemState($(this));
            });

            $item.addClass('_active awa-mueller-open');
            $item.children('a').addClass('ui-state-active');
            setExpandedState($item, true);
            $panel.addClass('opened');
            mobilePanelStack.push({
                item: $item,
                panel: $panel
            });
            syncMobilePanelClasses();
            return true;
        }

        function closeCurrentMobilePanel() {
            if (!isPanelSlideMobileMode() || !mobilePanelStack.length) {
                return false;
            }

            const state = mobilePanelStack.pop();
            clearMobileItemState(state.item);
            syncMobilePanelClasses();
            return true;
        }

        function closeDesktopItems() {
            getTopItems().removeClass('awa-mueller-open vmm-active');
            getTopItems().each(function () {
                setExpandedState($(this), false);
            });
            setRootSubmenuState(false);
        }

        function closeMobileItem($item) {
            const $panel = getFirstLevelPanel($item);

            if (isPanelSlideMobileMode()) {
                clearMobileItemState($item);
                return;
            }

            $item.removeClass('_active awa-mueller-open');
            $item.children('a').removeClass('ui-state-active');
            setExpandedState($item, false);

            if ($panel.length) {
                $panel.removeClass('opened').stop(true, true).slideUp(170);
            }
        }

        function openMobileItem($item) {
            const $panel = getFirstLevelPanel($item);

            if (!$panel.length) {
                return;
            }

            if (isPanelSlideMobileMode()) {
                openMobilePanelForItem($item);
                return;
            }

            $item.addClass('_active awa-mueller-open');
            $item.children('a').addClass('ui-state-active');
            setExpandedState($item, true);
            $panel.addClass('opened').stop(true, true).slideDown(170);
        }

        function closeMobileSiblingItems($item) {
            $item.siblings('.ui-menu-item.parent, .ui-menu-item.level0.parent').each(function () {
                closeMobileItem($(this));
            });
        }

        function openMenu() {
            collapseSearchAutocomplete();
            setMenuOpen(true);
            if (isPanelSlideMobileMode()) {
                decorateMobilePanels();
                syncMobilePanelClasses();
            }
            getRootList().stop(true, true).show();

            if (isDesktop()) {
                applyDesktopListInlineState(true);
                window.requestAnimationFrame(() => {
                    applyDesktopListInlineState(true);
                });
                window.setTimeout(() => {
                    applyDesktopListInlineState(true);
                }, 90);
                disableOverlayArtifacts();
                return;
            }

            if (isPanelSlideMobileMode()) {
                resetMobilePanelStack();
                $nav.addClass('awa-mobile-panel-enabled');
            }

            if (!settings.disableOverlay) {
                $('body').addClass('background_shadow_show');
            }
        }

        function closeMenu(forceDesktopClose) {
            if (isDesktop() && !forceDesktopClose && keepDesktopMenuExpanded()) {
                openMenu();
                return;
            }

            setMenuOpen(false);
            closeDesktopItems();

            if (isDesktop()) {
                applyDesktopListInlineState(false);
                getRootList().stop(true, true).hide();
                disableOverlayArtifacts();
                return;
            }

            if (isPanelSlideMobileMode()) {
                resetMobilePanelStack();
                getRootList().stop(true, true).hide();
                $nav.removeClass('awa-mobile-panel-enabled');
                resetRootListInlineStyles();
            } else {
                resetRootListInlineStyles();
                getRootList().stop(true, true).slideUp(160);
            }

            if (!settings.disableOverlay) {
                $('body').removeClass('background_shadow_show');
            }
        }

        function syncMenuVisibility() {
            const bodyNavOpen = !!(document.body && document.body.classList.contains('nav-open'));

            if (isDesktop()) {
                if (keepDesktopMenuExpanded()) {
                    openMenu();
                } else if (desktopPinned || getRootList().hasClass('menu-open')) {
                    openMenu();
                } else {
                    getRootList().hide();
                    setMenuOpen(false);
                    applyDesktopListInlineState(false);
                }

                disableOverlayArtifacts();
                return;
            }

            if (isPanelSlideMobileMode()) {
                decorateMobilePanels();
                resetRootListInlineStyles();

                if (bodyNavOpen) {
                    setMenuOpen(true);
                }

                if (getRootList().hasClass('menu-open')) {
                    syncMobilePanelClasses();
                    getRootList().show();
                    $nav.addClass('awa-mobile-panel-enabled');
                } else {
                    resetMobilePanelStack();
                    getRootList().hide();
                    $nav.removeClass('awa-mobile-panel-enabled');
                }
                return;
            }

            resetRootListInlineStyles();
            if (getRootList().hasClass('menu-open')) {
                getRootList().show();
            } else {
                getRootList().hide();
            }
        }

        function openDesktopItem($item) {
            if (!$item.length) {
                return;
            }

            const $siblings = $item.siblings('.ui-menu-item.level0');
            $siblings.removeClass('awa-mueller-open vmm-active');
            $siblings.each(function () {
                setExpandedState($(this), false);
            });

            $item.addClass('awa-mueller-open vmm-active');
            setExpandedState($item, true);
            setRootSubmenuState(true);
            normalizeFlyoutPanel($item);
        }

        function closeAll() {
            desktopPinned = false;
            if (isPanelSlideMobileMode()) {
                resetMobilePanelStack();
            } else {
                getParentItems().each(function () {
                    closeMobileItem($(this));
                });
            }
            closeDesktopItems();
            closeMenu(true);
            disableOverlayArtifacts();
        }

        function initShowMore() {
            const limitShow = getLimitShow();
            const $expandRow = $expandLink.closest('.expand-category-link');

            if (!$expandLink.length || limitShow <= 0) {
                setDisplayImportant($expandRow, false);
                return;
            }

            if (getTopItems().length <= limitShow) {
                setDisplayImportant($expandRow, false);
                return;
            }

            getTopItems().each(function (index) {
                if (index >= limitShow) {
                    const $item = $(this);

                    $item.addClass('orther-link');
                    setDisplayImportant($item, false);
                }
            });

            setDisplayImportant($expandRow, true, 'block');

            $expandLink.off('click' + ns + ' keydown' + ns);
            $expandLink.on('click' + ns, function (event) {
                const $link = $(this);
                const expanding = !$link.hasClass('expanding');
                const $hiddenItems = getRootList().children('li.ui-menu-item.level0.orther-link');

                event.preventDefault();

                $link.toggleClass('expanding', expanding);
                $link.closest('.expand-category-link').toggleClass('expanding', expanding);
                $link.attr('aria-expanded', expanding ? 'true' : 'false');

                if ($link.data('show-text') && $link.data('hide-text')) {
                    $link.find('span').text(expanding ? $link.data('hide-text') : $link.data('show-text'));
                }

                $hiddenItems.each(function () {
                    setDisplayImportant($(this), expanding, 'block');
                });
            });

            $expandLink.on('keydown' + ns, function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    $(this).trigger('click');
                }
            });
        }

        function bindDesktopMenu() {
            const $parents = getParentItems();

            $parents.off('mouseenter' + ns + ' focusin' + ns + ' mouseleave' + ns);

            if (settings.openModeDesktop !== 'hover') {
                return;
            }

            $parents.on('mouseenter' + ns + ' focusin' + ns, function () {
                if (!isDesktop()) {
                    return;
                }

                openMenu();
                openDesktopItem($(this));
                disableOverlayArtifacts();
            });

            $parents.on('mouseleave' + ns, function () {
                if (!isDesktop()) {
                    return;
                }

                setExpandedState($(this), false);
                $(this).removeClass('awa-mueller-open');
            });

            $nav.off('mouseleave' + ns).on('mouseleave' + ns, function () {
                if (isDesktop()) {
                    closeDesktopItems();
                    if (!desktopPinned && !keepDesktopMenuExpanded()) {
                        closeMenu(true);
                    }
                }
            });
        }

        function bindTriggerEvents() {
            $trigger.off('click' + ns + ' keydown' + ns + ' mouseenter' + ns + ' focusin' + ns);

            $trigger.on('click' + ns, function (event) {
                event.preventDefault();

                if (isDesktop()) {
                    if (keepDesktopMenuExpanded()) {
                        openMenu();
                        return;
                    }

                    desktopPinned = !desktopPinned;

                    if (!desktopPinned) {
                        closeMenu(true);
                    } else {
                        openMenu();
                    }
                    return;
                }

                if (getRootList().hasClass('menu-open')) {
                    closeMenu(true);
                } else {
                    openMenu();
                }
            });

            $trigger.on('mouseenter' + ns + ' focusin' + ns, function () {
                if (!isDesktop() || settings.openModeDesktop !== 'hover') {
                    return;
                }

                if (!desktopPinned) {
                    openMenu();
                }
            });

            $trigger.on('keydown' + ns, function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    $trigger.trigger('click');
                    return;
                }

                if (event.key === 'Escape') {
                    event.preventDefault();
                    desktopPinned = false;
                    closeMenu(true);
                }
            });
        }

        function bindMobileSubmenuEvents() {
            const toggleSelector = '.ui-menu-item.parent > .open-children-toggle, .ui-menu-item.level0.parent > .open-children-toggle';
            const parentLinkSelector = '.ui-menu-item.parent > a, .ui-menu-item.level0.parent > a';

            getRootList().off('click' + ns, toggleSelector);
            getRootList().off('keydown' + ns, toggleSelector);
            getRootList().off('click' + ns, parentLinkSelector);
            getRootList().off('keydown' + ns, parentLinkSelector);
            getRootList().off('click' + ns, '.awa-mobile-panel__back');
            getRootList().off('keydown' + ns, '.awa-mobile-panel__back');

            if (settings.openModeMobile !== 'click') {
                return;
            }

            if (isPanelSlideMobileMode()) {
                getRootList().on('click' + ns, toggleSelector + ', ' + parentLinkSelector, function (event) {
                    const $item = $(this).closest('.ui-menu-item.parent, .ui-menu-item.level0.parent');
                    const isToggle = $(this).hasClass('open-children-toggle');

                    if (isDesktop()) {
                        return;
                    }

                    if (!$item.length || !getFirstLevelPanel($item).length) {
                        return;
                    }

                    event.preventDefault();
                    event.stopPropagation();
                    collapseSearchAutocomplete();
                    const opened = openMobilePanelForItem($item);
                    if (!opened && !isToggle) {
                        window.location.href = $item.children('a').first().attr('href') || '#';
                    }
                    disableOverlayArtifacts();
                });

                getRootList().on('keydown' + ns, toggleSelector + ', ' + parentLinkSelector, function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        $(this).trigger('click');
                    }
                });

                getRootList().on('click' + ns, '.awa-mobile-panel__back', function (event) {
                    const $panel = $(this).closest('.awa-mobile-panel');
                    const $activePanel = getActiveMobilePanel();

                    event.preventDefault();
                    event.stopPropagation();

                    if (isDesktop()) {
                        return;
                    }

                    if (!$activePanel.length || !$panel.length || $activePanel.get(0) !== $panel.get(0)) {
                        return;
                    }

                    closeCurrentMobilePanel();
                    disableOverlayArtifacts();
                });

                getRootList().on('keydown' + ns, '.awa-mobile-panel__back', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        $(this).trigger('click');
                    }
                });

                return;
            }

            getRootList().on('click' + ns, toggleSelector, function (event) {
                const $item = $(this).closest('.ui-menu-item.parent, .ui-menu-item.level0.parent');
                const $panel = getFirstLevelPanel($item);

                event.preventDefault();
                event.stopPropagation();

                if (isDesktop()) {
                    return;
                }

                if (!$panel.length) {
                    return;
                }

                const opening = !$item.hasClass('_active') || !$panel.hasClass('opened');

                if (opening) {
                    closeMobileSiblingItems($item);
                    openMobileItem($item);
                } else {
                    closeMobileItem($item);
                }

                if ($item.hasClass('level0')) {
                    setRootSubmenuState(opening);
                }

                disableOverlayArtifacts();
            });

            getRootList().on('keydown' + ns, toggleSelector, function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    $(this).trigger('click');
                }
            });
        }

        function bindGlobalEvents() {
            $(document).off('keydown' + ns).on('keydown' + ns, function (event) {
                if (event.key === 'Escape') {
                    desktopPinned = false;
                    if (!isDesktop() || !keepDesktopMenuExpanded()) {
                        closeAll();
                    } else {
                        closeDesktopItems();
                    }
                }
            });

            $(document).off('click' + ns).on('click' + ns, function (event) {
                if (isDesktop()) {
                    return;
                }

                if ($(event.target).closest('.action.nav-toggle, .nav-toggle').length) {
                    window.setTimeout(syncMenuVisibility, 30);
                    return;
                }

                if ($(event.target).closest($nav).length) {
                    return;
                }

                closeMenu(true);
            });

            $(window).off('resize' + ns).on('resize' + ns, function () {
                disableOverlayArtifacts();
                closeDesktopItems();

                if (isDesktop()) {
                    getParentItems().each(function () {
                        getLevelPanels($(this)).removeAttr('style');
                    });
                    if (isPanelSlideMobileMode()) {
                        resetMobilePanelStack();
                        $nav.removeClass('awa-mobile-panel-enabled');
                        resetRootListInlineStyles();
                    }
                    if (!desktopPinned && !keepDesktopMenuExpanded()) {
                        applyDesktopListInlineState(false);
                    }
                } else {
                    desktopPinned = false;
                    resetRootListInlineStyles();
                    if (isPanelSlideMobileMode()) {
                        decorateMobilePanels();
                        syncMobilePanelClasses();
                    }
                }

                syncMenuVisibility();
            });
        }

        function bindOverlayGuard() {
            if (!window.MutationObserver || !document.body || overlayGuardObserver) {
                return;
            }

            overlayGuardObserver = new MutationObserver(() => {
                if (isDesktop()) {
                    disableOverlayArtifacts();
                } else if (isPanelSlideMobileMode()) {
                    syncMenuVisibility();
                }
            });

            overlayGuardObserver.observe(document.body, {
                attributes: true,
                attributeFilter: ['class']
            });
        }

        function bindListStyleGuard() {
            if (!window.MutationObserver || listStyleGuardObserver) {
                return;
            }

            const listElement = getRootList().get(0);

            if (!listElement) {
                return;
            }

            listStyleGuardObserver = new MutationObserver(() => {
                if (!isDesktop() || listStyleGuardBusy) {
                    return;
                }

                listStyleGuardBusy = true;
                window.requestAnimationFrame(() => {
                    enforceDesktopListInlineState();
                    listStyleGuardBusy = false;
                });
            });

            listStyleGuardObserver.observe(listElement, {
                attributes: true,
                attributeFilter: ['style', 'class']
            });
        }

        function setupStructure() {
            getParentItems().each(function () {
                const $item = $(this);

                setExpandedState($item, false);
                $item.attr('data-level', $item.attr('data-level') || '0');

                $item.children('.submenu, .level0.submenu').attr('data-flyout-level', '2');
                $item.find('.subchildmenu .subchildmenu').attr('data-flyout-level', '3');
                normalizeFlyoutPanel($item);
            });

            getRootList().addClass('todasascategorias');
            injectCategoryIcons();
            if (isPanelSlideMobileMode()) {
                $nav.addClass('awa-mobile-panel-mode');
                decorateMobilePanels();
                resetMobilePanelStack();
            }
            setMenuOpen(false);
            setRootSubmenuState(false);
        }

        setupStructure();
        initShowMore();
        bindDesktopMenu();
        bindTriggerEvents();
        bindMobileSubmenuEvents();
        bindGlobalEvents();
        bindOverlayGuard();
        bindListStyleGuard();
        syncMenuVisibility();
        disableOverlayArtifacts();

        $nav.data('awaMuellerMenuInit', true);
    };
});
