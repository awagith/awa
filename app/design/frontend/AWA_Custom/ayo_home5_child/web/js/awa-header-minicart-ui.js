(function () {
    'use strict';

    if (window.__awaRound2HeaderMinicartUiInit) {
        return;
    }
    window.__awaRound2HeaderMinicartUiInit = true;

    const SCROLL_THRESHOLD = 48;
    let scheduled = false;
    let searchObserver;
    let stickyObserver;
    let autocompleteOptionId = 0;
    let searchCompatLoading = false;
    let searchCompatLoaded = false;

    function onReady(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
            return;
        }
        callback();
    }

    function getStickyWrapper() {
        return document.querySelector('.header-wrapper-sticky');
    }

    function getSearchScope() {
        return document.querySelector('.header .top-search .block-search');
    }

    function getBody() {
        return document.body;
    }

    function getHomeNavRoot() {
        return document.querySelector('[data-awa-header-nav="true"]')
            || document.querySelector('.header-control.header-nav.header-nav-global.cms_home_1');
    }

    function isVisible(el) {
        if (!el) {
            return false;
        }

        return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
    }

    function setImportantStyle(el, property, value) {
        if (!el) {
            return;
        }

        if (el.style.getPropertyValue(property) === value && el.style.getPropertyPriority(property) === 'important') {
            return;
        }

        el.style.setProperty(property, value, 'important');
    }

    function toOriginalProductImageUrl(url) {
        if (typeof url !== 'string' || url.indexOf('/media/catalog/product/cache/') === -1) {
            return '';
        }

        const withoutQuery = url.split('?')[0];
        const queryPart = url.indexOf('?') >= 0 ? url.slice(url.indexOf('?')) : '';
        const originalPath = withoutQuery.replace(
            /\/media\/catalog\/product\/cache\/[^/]+\//,
            '/media/catalog/product/'
        );

        if (!originalPath || originalPath === withoutQuery) {
            return '';
        }

        return `${originalPath}${queryPart}`;
    }

    function applyProductImageCacheFallback(img) {
        if (!img || img.nodeType !== 1) {
            return;
        }

        const currentSrc = img.currentSrc || img.getAttribute('src') || '';
        const fallbackSrc = toOriginalProductImageUrl(currentSrc);

        if (!fallbackSrc || img.getAttribute('data-awa-cache-fallback-applied') === '1') {
            return;
        }

        img.setAttribute('data-awa-cache-fallback-applied', '1');
        img.removeAttribute('srcset');
        img.setAttribute('src', fallbackSrc);
    }

    function bindProductImageFallback(root) {
        const scope = root && root.querySelectorAll ? root : document;
        const images = scope.querySelectorAll('img[src*="/media/catalog/product/cache/"]');

        for (let i = 0; i < images.length; i += 1) {
            const img = images[i];

            if (img.getAttribute('data-awa-cache-fallback-bound') === '1') {
                continue;
            }

            img.setAttribute('data-awa-cache-fallback-bound', '1');
            img.addEventListener('error', () => {
                applyProductImageCacheFallback(img);
            }, { passive: true });

            if (img.complete && img.naturalWidth === 0) {
                applyProductImageCacheFallback(img);
            }
        }
    }

    function measureVisibleTopStackBottom() {
        const selectors = [
            '.header-wrapper-sticky',
            '.page-wrapper .header-wrapper-sticky',
            '.page-wrapper .top-header',
            '.page-wrapper .header-control',
            '.page-wrapper .header .header_main',
            '.page-wrapper .header .header-main'
        ];
        const seen = [];
        let maxBottom = 0;

        for (let i = 0; i < selectors.length; i += 1) {
            const nodes = document.querySelectorAll(selectors[i]);

            for (let j = 0; j < nodes.length; j += 1) {
                const el = nodes[j];

                if (!el || seen.indexOf(el) !== -1 || !isVisible(el)) {
                    continue;
                }
                seen.push(el);

                const style = window.getComputedStyle ? window.getComputedStyle(el) : null;
                const position = style ? style.position : '';

                if (position !== 'fixed' && position !== 'sticky') {
                    continue;
                }

                const rect = el.getBoundingClientRect();

                if (!rect || rect.height < 20 || rect.bottom <= 0) {
                    continue;
                }

                if (position === 'sticky' && rect.top > 2) {
                    continue;
                }

                if (rect.bottom > maxBottom) {
                    maxBottom = rect.bottom;
                }
            }
        }

        return Math.max(0, maxBottom);
    }

    function isHomeHeaderContext() {
        const body = getBody();

        if (!body) {
            return false;
        }

        return body.classList.contains('cms-index-index') ||
            body.classList.contains('cms-home') ||
            body.classList.contains('cms-homepage_ayo_home5');
    }

    function isPlpContext() {
        const body = getBody();

        if (!body) {
            return false;
        }

        return body.classList.contains('catalog-category-view') ||
            body.classList.contains('catalogsearch-result-index');
    }

    function isStickyFeatureEnabled(wrapper) {
        if (!wrapper) {
            return false;
        }

        return wrapper.classList.contains('enabled-header-sticky') || wrapper.classList.contains('enable-sticky');
    }

    function getSearchUi() {
        const scope = getSearchScope();
        const form = scope ? scope.querySelector('#search_mini_form, form.form.minisearch') : null;

        if (!scope || !form) {
            return null;
        }

        return {
            scope: scope,
            form: form,
            input: form.querySelector('#search, #search-input-autocomplate, input[name="q"]'),
            actions: form.querySelector('.actions'),
            button: form.querySelector('.action.search, button[type="submit"]'),
            panel: scope.querySelector('#search_autocomplete'),
            resultsRoot: scope.querySelector('.mst-searchautocomplete__autocomplete, .searchsuite-autocomplete')
        };
    }

    function isMirasvitAutocompleteAvailable(scope) {
        const context = scope || document;

        return !!(
            document.getElementById('searchAutocompletePlaceholder') ||
            context.querySelector('.mst-searchautocomplete__autocomplete')
        );
    }

    function getRequireFn() {
        if (typeof window.require === 'function') {
            return window.require;
        }

        if (typeof window.requirejs === 'function') {
            return window.requirejs;
        }

        return null;
    }

    function ensureSearchCompatBridge() {
        const requireFn = getRequireFn();
        const searchUi = getSearchUi();

        if (isMirasvitAutocompleteAvailable(searchUi ? searchUi.scope : document)) {
            searchCompatLoaded = true;
            return;
        }

        if (!requireFn || !searchUi || !searchUi.form || searchCompatLoaded || searchCompatLoading) {
            return;
        }

        searchCompatLoading = true;

        requireFn(
            ['js/awa-search-autocomplete-compat'],
            function (initAwaSearchCompat) {
                const latestSearchUi = getSearchUi();

                searchCompatLoading = false;
                searchCompatLoaded = true;

                if (!latestSearchUi || !latestSearchUi.form || typeof initAwaSearchCompat !== 'function') {
                    return;
                }

                initAwaSearchCompat({}, latestSearchUi.form);
                latestSearchUi.form.setAttribute('data-awa-search-compat-bound', 'true');
                scheduleRuntimeSync();
            },
            () => {
                searchCompatLoading = false;
            }
        );
    }

    function ensureSearchLiveRegion(searchUi) {
        if (!searchUi || !searchUi.scope) {
            return null;
        }

        let liveRegion = searchUi.scope.querySelector('#awa-search-live-region');
        if (!liveRegion) {
            liveRegion = document.createElement('div');
            liveRegion.id = 'awa-search-live-region';
            liveRegion.className = 'awa-sr-only';
            liveRegion.setAttribute('aria-live', 'polite');
            liveRegion.setAttribute('aria-atomic', 'true');
            liveRegion.setAttribute('data-awa-search-live', 'true');
            liveRegion.style.position = 'absolute';
            liveRegion.style.width = '1px';
            liveRegion.style.height = '1px';
            liveRegion.style.padding = '0';
            liveRegion.style.margin = '-1px';
            liveRegion.style.overflow = 'hidden';
            liveRegion.style.clip = 'rect(0 0 0 0)';
            liveRegion.style.clipPath = 'inset(50%)';
            liveRegion.style.whiteSpace = 'nowrap';
            searchUi.scope.appendChild(liveRegion);
        }

        if (searchUi.input) {
            const describedBy = searchUi.input.getAttribute('aria-describedby') || '';
            const describedByTokens = describedBy.split(/\s+/).filter(Boolean);
            if (describedByTokens.indexOf(liveRegion.id) === -1) {
                describedByTokens.push(liveRegion.id);
                searchUi.input.setAttribute('aria-describedby', describedByTokens.join(' '));
            }
        }

        return liveRegion;
    }

    function collectAutocompleteOptions(searchUi) {
        const panel = searchUi ? (searchUi.resultsRoot || searchUi.panel) : null;
        const options = [];

        if (!panel) {
            return options;
        }

        const candidates = panel.querySelectorAll('.suggest ul li, .product ul li, [role="option"], li');

        for (let i = 0; i < candidates.length; i += 1) {
            const node = candidates[i];
            let optionNode = node;

            if (!optionNode) {
                continue;
            }

            if (!(optionNode.matches && optionNode.matches('[role="option"], li'))) {
                optionNode = optionNode.closest('[role="option"], li');
            }

            if (!optionNode || !isVisible(optionNode)) {
                continue;
            }

            let duplicate = false;
            for (let j = 0; j < options.length; j += 1) {
                if (options[j].option === optionNode) {
                    duplicate = true;
                    break;
                }
            }

            if (duplicate) {
                continue;
            }

            optionNode.setAttribute('role', 'option');
            if (!optionNode.id) {
                autocompleteOptionId += 1;
                optionNode.id = 'awa-search-option-' + autocompleteOptionId;
            }

            const actionNode = optionNode.matches && optionNode.matches('a[href], button, [tabindex]') ?
                optionNode :
                optionNode.querySelector('a[href], button, [tabindex]');

            options.push({
                option: optionNode,
                action: actionNode || optionNode
            });
        }

        return options;
    }

    function getAutocompleteActiveIndex(searchUi) {
        if (!searchUi || !searchUi.form) {
            return -1;
        }

        const rawIndex = searchUi.form.getAttribute('data-awa-active-index');
        const index = parseInt(rawIndex || '-1', 10);

        if (isNaN(index)) {
            return -1;
        }

        return index;
    }

    function setAutocompleteActiveIndex(searchUi, index) {
        if (!searchUi || !searchUi.form) {
            return;
        }

        searchUi.form.setAttribute('data-awa-active-index', String(index));
    }

    function clearAutocompleteActiveState(searchUi, options) {
        if (!searchUi) {
            return;
        }

        for (let i = 0; i < options.length; i += 1) {
            options[i].option.classList.remove('selected');
            options[i].option.classList.remove('awa-option-active');
            options[i].option.setAttribute('aria-selected', 'false');
        }

        if (searchUi.input) {
            searchUi.input.removeAttribute('aria-activedescendant');
        }

        setAutocompleteActiveIndex(searchUi, -1);
    }

    function setActiveAutocompleteOption(searchUi, options, nextIndex, announce) {
        let boundedIndex = nextIndex;

        if (!searchUi || !options.length) {
            clearAutocompleteActiveState(searchUi, options || []);
            return;
        }

        if (boundedIndex >= options.length) {
            boundedIndex = 0;
        }

        if (boundedIndex < 0) {
            boundedIndex = options.length - 1;
        }

        clearAutocompleteActiveState(searchUi, options);
        const activeEntry = options[boundedIndex];
        activeEntry.option.classList.add('selected');
        activeEntry.option.classList.add('awa-option-active');
        activeEntry.option.setAttribute('aria-selected', 'true');
        setAutocompleteActiveIndex(searchUi, boundedIndex);

        if (searchUi.input) {
            searchUi.input.setAttribute('aria-activedescendant', activeEntry.option.id);
        }

        if (activeEntry.option.scrollIntoView) {
            activeEntry.option.scrollIntoView({
                block: 'nearest',
                inline: 'nearest'
            });
        }

        if (announce) {
            const liveRegion = ensureSearchLiveRegion(searchUi);
            if (liveRegion) {
                const optionLabel = (activeEntry.option.textContent || '').replace(/\s+/g, ' ').trim();
                liveRegion.textContent = `Sugestão ${boundedIndex + 1} de ${options.length}: ${optionLabel}`;
            }
        }
    }

    function closeSearchAutocomplete(searchUi) {
        if (!searchUi) {
            return;
        }

        const options = collectAutocompleteOptions(searchUi);
        clearAutocompleteActiveState(searchUi, options);
        const liveRegion = ensureSearchLiveRegion(searchUi);

        if (liveRegion) {
            liveRegion.textContent = 'Sugestões fechadas.';
        }

        if (!searchUi.panel) {
            return;
        }

        searchUi.panel.setAttribute('aria-hidden', 'true');
        searchUi.panel.classList.remove('is-open');
        searchUi.form.classList.remove('is-open');

        if (searchUi.input) {
            searchUi.input.setAttribute('aria-expanded', 'false');
        }

        searchUi.form.setAttribute('data-awa-panel-closed', 'true');
        setImportantStyle(searchUi.panel, 'display', 'none');
    }

    function announceAutocompleteSummary(searchUi, panelVisible, hasResults, optionsCount) {
        const liveRegion = ensureSearchLiveRegion(searchUi);
        if (!liveRegion) {
            return;
        }

        if (panelVisible && hasResults) {
            liveRegion.textContent = `${optionsCount} sugestões disponíveis. Use seta para baixo e cima para navegar.`;
            return;
        }

        const queryText = searchUi.input ? (searchUi.input.value || '').trim() : '';
        if (panelVisible && !hasResults && queryText.length >= 2) {
            liveRegion.textContent = 'Nenhuma sugestão encontrada.';
            return;
        }

        liveRegion.textContent = '';
    }

    function handleSearchAutocompleteKeyboard(event) {
        const key = event.key;
        const searchUi = getSearchUi();

        if (!searchUi || !searchUi.input || event.target !== searchUi.input) {
            return;
        }

        if (key !== 'ArrowDown' && key !== 'ArrowUp' && key !== 'Enter' && key !== 'Escape' && key !== 'Down' && key !== 'Up') {
            return;
        }

        const options = collectAutocompleteOptions(searchUi);
        const activeIndex = getAutocompleteActiveIndex(searchUi);

        if (key === 'Escape') {
            event.preventDefault();
            closeSearchAutocomplete(searchUi);
            return;
        }

        if (!options.length) {
            return;
        }

        if (key === 'ArrowDown' || key === 'Down') {
            event.preventDefault();
            setActiveAutocompleteOption(searchUi, options, activeIndex + 1, true);
            return;
        }

        if (key === 'ArrowUp' || key === 'Up') {
            event.preventDefault();
            setActiveAutocompleteOption(searchUi, options, activeIndex - 1, true);
            return;
        }

        if (key === 'Enter' && activeIndex >= 0) {
            const activeOption = options[activeIndex];
            if (activeOption && activeOption.action && activeOption.action.click) {
                event.preventDefault();
                activeOption.action.click();
            }
        }
    }

    function handleSearchAutocompleteHover(event) {
        const searchUi = getSearchUi();

        if (!searchUi || !searchUi.panel || !searchUi.panel.contains(event.target)) {
            return;
        }

        const hoveredOption = event.target.closest('[role="option"], li');
        if (!hoveredOption) {
            return;
        }

        const options = collectAutocompleteOptions(searchUi);
        for (let i = 0; i < options.length; i += 1) {
            if (options[i].option === hoveredOption) {
                setActiveAutocompleteOption(searchUi, options, i, false);
                break;
            }
        }
    }

    function canUseUrlParser() {
        return typeof window.URL === 'function';
    }

    function normalizeSearchFormAction() {
        const searchUi = getSearchUi();
        const form = searchUi ? searchUi.form : null;

        if (!form) {
            return;
        }

        const rawAction = form.getAttribute('action') || form.action || '';
        if (!rawAction || !canUseUrlParser()) {
            return;
        }

        let parsedAction;
        try {
            parsedAction = new window.URL(rawAction, window.location.origin);
        } catch (e) {
            return;
        }

        const currentOrigin = window.location.origin;
        if (!/\/catalogsearch\/result\/?/i.test(parsedAction.pathname)) {
            return;
        }

        if (parsedAction.origin === currentOrigin) {
            return;
        }

        const normalizedAction = currentOrigin + parsedAction.pathname;

        if (form.getAttribute('action') !== normalizedAction) {
            form.setAttribute('action', normalizedAction);
        }
    }

    function syncA11yLabels() {
        const searchUi = getSearchUi();
        const searchButton = searchUi ? searchUi.button : null;
        const searchInput = searchUi ? searchUi.input : null;
        const searchPanel = searchUi ? searchUi.panel : null;
        const minicartTrigger = document.querySelector('.header .top-search .minicart-wrapper .action.showcart');

        if (searchButton && !searchButton.getAttribute('aria-label')) {
            searchButton.setAttribute('aria-label', searchButton.getAttribute('title') || 'Buscar');
        }

        if (searchButton && !searchButton.getAttribute('title')) {
            searchButton.setAttribute('title', searchButton.getAttribute('aria-label') || 'Buscar');
        }

        if (searchInput) {
            if (!searchInput.getAttribute('aria-label') && !searchInput.getAttribute('aria-labelledby')) {
                searchInput.setAttribute('aria-label', 'Buscar produtos');
            }

            if (searchPanel && !searchPanel.id) {
                searchPanel.id = 'search_autocomplete';
            }

            if (searchPanel) {
                searchInput.setAttribute('aria-controls', searchPanel.id || 'search_autocomplete');
                searchInput.setAttribute('aria-haspopup', 'listbox');
            }
        }

        if (searchPanel) {
            searchPanel.setAttribute('role', 'listbox');
            if (!searchPanel.getAttribute('aria-hidden')) {
                searchPanel.setAttribute('aria-hidden', 'true');
            }
        }

        if (minicartTrigger && !minicartTrigger.getAttribute('title')) {
            minicartTrigger.setAttribute('title', 'Abrir carrinho');
        }

        if (minicartTrigger && !minicartTrigger.getAttribute('aria-label')) {
            minicartTrigger.setAttribute('aria-label', minicartTrigger.getAttribute('title') || 'Abrir carrinho');
        }
    }

    function syncSearchState() {
        const searchUi = getSearchUi();

        if (!searchUi) {
            return;
        }

        searchUi.form.classList.add('is-ready');
        ensureSearchLiveRegion(searchUi);

        let currentQuery = '';
        if (searchUi.input) {
            currentQuery = searchUi.input.value || '';
        }

        const trimmedQuery = currentQuery.trim();
        const isQueryEmpty = trimmedQuery.length === 0;

        const lastQuery = searchUi.form.getAttribute('data-awa-last-query') || '';
        if (currentQuery !== lastQuery) {
            searchUi.form.setAttribute('data-awa-last-query', currentQuery);
            searchUi.form.removeAttribute('data-awa-panel-closed');
            if (searchUi.panel) {
                searchUi.panel.style.removeProperty('display');
            }
        }

        let panelVisible = !!(searchUi.panel && isVisible(searchUi.panel) && window.getComputedStyle(searchUi.panel).display !== 'none');
        const autocompleteOptions = collectAutocompleteOptions(searchUi);
        let hasResults = false;
        let panelText = '';

        if (searchUi.resultsRoot && isVisible(searchUi.resultsRoot)) {
            hasResults = autocompleteOptions.length > 0 || searchUi.resultsRoot.querySelectorAll('li').length > 0;
            panelText = (searchUi.resultsRoot.textContent || '').trim();
        } else if (searchUi.panel) {
            panelText = (searchUi.panel.textContent || '').trim();
            hasResults = autocompleteOptions.length > 0 || panelText !== '' || searchUi.panel.children.length > 0;
        }

        const panelForcedClosed = searchUi.form.getAttribute('data-awa-panel-closed') === 'true';
        if (panelForcedClosed) {
            panelVisible = false;
            if (searchUi.panel) {
                setImportantStyle(searchUi.panel, 'display', 'none');
            }
        }

        searchUi.form.classList.toggle('is-open', panelVisible);
        searchUi.form.classList.toggle('has-results', hasResults);
        searchUi.form.classList.toggle('is-empty', !hasResults);
        searchUi.form.classList.toggle('is-query-empty', isQueryEmpty);
        searchUi.form.classList.toggle('has-query', !isQueryEmpty);
        searchUi.scope.classList.toggle('is-query-empty', isQueryEmpty);
        searchUi.scope.classList.toggle('has-query', !isQueryEmpty);

        if (searchUi.input) {
            searchUi.input.setAttribute('aria-expanded', panelVisible ? 'true' : 'false');
            searchUi.input.setAttribute('aria-haspopup', 'listbox');
        }

        if (searchUi.panel) {
            searchUi.panel.setAttribute('aria-hidden', panelVisible ? 'false' : 'true');
            searchUi.panel.setAttribute('role', 'listbox');
            searchUi.panel.classList.toggle('is-open', panelVisible);
            searchUi.panel.classList.toggle('has-results', hasResults);

            if (panelVisible) {
                searchUi.panel.style.removeProperty('display');
            }
        }

        if (!panelVisible || !hasResults) {
            clearAutocompleteActiveState(searchUi, autocompleteOptions);
        } else {
            const activeIndex = getAutocompleteActiveIndex(searchUi);
            if (activeIndex >= autocompleteOptions.length) {
                clearAutocompleteActiveState(searchUi, autocompleteOptions);
            }
        }

        announceAutocompleteSummary(searchUi, panelVisible, hasResults, autocompleteOptions.length);
    }

    function classifyTopLinks() {
        const anchors = document.querySelectorAll('.top-account ul.header.links > li > a');

        for (let i = 0; i < anchors.length; i += 1) {
            const anchor = anchors[i];
            const li = anchor ? anchor.closest('li') : null;
            const href = anchor ? (anchor.getAttribute('href') || '') : '';
            const normalizedHref = href.toLowerCase();
            const text = anchor ? ((anchor.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase()) : '';

            if (!anchor || !li) {
                continue;
            }

            li.classList.add('awa-top-link-item');
            anchor.classList.add('awa-top-link-anchor');
            li.classList.remove(
                'awa-top-link-item--compare',
                'awa-top-link-item--b2b-register',
                'awa-top-link-item--login',
                'awa-top-link-item--logout',
                'awa-top-link-item--account'
            );

            if (li.classList.contains('compare') || /comparar/.test(text) || /product_compare/.test(href)) {
                li.classList.add('awa-top-link-item--compare');
            } else if (/cadastro b2b|cadastre-se|cadastrar-se|cadastro/.test(text) || /\/b2b\/register|\/customer\/account\/create|\/register/.test(normalizedHref)) {
                li.classList.add('awa-top-link-item--b2b-register');
            } else if (/entrar|acessar|login/.test(text) || /\/login/.test(normalizedHref)) {
                li.classList.add('awa-top-link-item--login');
            } else if (/sair|logout/.test(text) || /\/logout/.test(normalizedHref)) {
                li.classList.add('awa-top-link-item--logout');
            } else if (/minha conta/.test(text) || /customer\/account/.test(href)) {
                li.classList.add('awa-top-link-item--account');
            }

            if (!anchor.getAttribute('title')) {
                anchor.setAttribute('title', (anchor.textContent || '').replace(/\s+/g, ' ').trim());
            }
        }
    }

    function syncTopLinkCounters() {
        const items = document.querySelectorAll('.top-account ul.header.links > li');

        for (let i = 0; i < items.length; i += 1) {
            const item = items[i];
            if (!item) {
                continue;
            }

            const counter = item.querySelector('.counter.qty');
            if (!counter) {
                item.classList.remove('awa-top-link-counter-zero', 'awa-top-link-counter-has-value');
                continue;
            }

            const raw = (counter.textContent || '').replace(/[^\d]/g, '');
            const parsed = raw ? Number(raw) : 0;
            const hasCount = Number.isFinite(parsed) && parsed > 0;

            item.classList.toggle('awa-top-link-counter-has-value', hasCount);
            item.classList.toggle('awa-top-link-counter-zero', !hasCount);
            counter.setAttribute('aria-hidden', hasCount ? 'false' : 'true');
        }
    }

    function enforceSearchButtonTouchTarget() {
        const searchUi = getSearchUi();
        const searchButton = searchUi ? searchUi.button : null;
        const actions = searchUi ? searchUi.actions : null;
        const isMobile = window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
        let targetSize = 44;

        if (!searchButton || !actions) {
            return;
        }

        if (isMobile) {
            targetSize = 48;
        }

        const buttonRect = searchButton.getBoundingClientRect();

        setImportantStyle(actions, 'display', 'flex');
        setImportantStyle(actions, 'position', 'static');
        setImportantStyle(actions, 'width', `${targetSize}px`);
        setImportantStyle(actions, 'min-width', `${targetSize}px`);
        setImportantStyle(actions, 'flex', `0 0 ${targetSize}px`);
        setImportantStyle(actions, 'height', '44px');
        setImportantStyle(actions, 'max-height', '44px');

        setImportantStyle(searchButton, 'position', 'static');
        setImportantStyle(searchButton, 'width', `${targetSize}px`);
        setImportantStyle(searchButton, 'min-width', `${targetSize}px`);
        setImportantStyle(searchButton, 'flex', `0 0 ${targetSize}px`);
        setImportantStyle(searchButton, 'height', '44px');
        setImportantStyle(searchButton, 'min-height', '44px');
        setImportantStyle(searchButton, 'max-height', '44px');
        setImportantStyle(searchButton, 'line-height', '1');
        setImportantStyle(searchButton, 'box-sizing', 'border-box');

        if (buttonRect.width > (targetSize + 8) || isMobile) {
            setImportantStyle(searchButton, 'padding-left', '0');
            setImportantStyle(searchButton, 'padding-right', '0');
            setImportantStyle(searchButton, 'font-size', '0');
        }
    }

    function enforceSearchInlineLayout() {
        const searchUi = getSearchUi();
        const input = searchUi ? searchUi.input : null;

        if (!searchUi) {
            return;
        }

        setImportantStyle(searchUi.form, 'display', 'flex');
        setImportantStyle(searchUi.form, 'align-items', 'stretch');
        setImportantStyle(searchUi.form, 'flex-wrap', 'nowrap');
        setImportantStyle(searchUi.form, 'width', '100%');
        setImportantStyle(searchUi.form, 'min-width', '0');

        if (searchUi.actions) {
            setImportantStyle(searchUi.actions, 'position', 'static');
            setImportantStyle(searchUi.actions, 'inset', 'auto');
            setImportantStyle(searchUi.actions, 'float', 'none');
            setImportantStyle(searchUi.actions, 'margin', '0');
        }

        if (input) {
            setImportantStyle(input, 'width', '100%');
            setImportantStyle(input, 'min-width', '0');
            setImportantStyle(input, 'height', '44px');
            setImportantStyle(input, 'min-height', '44px');
            setImportantStyle(input, 'max-height', '44px');
            setImportantStyle(input, 'box-sizing', 'border-box');
        }
    }

    function suppressNonHomeNavToggle() {
        const navToggle = document.querySelector('.header-control .action.nav-toggle, .header-control .nav-toggle');
        const isHome = isHomeHeaderContext();

        if (!navToggle) {
            return;
        }

        if (isHome) {
            navToggle.style.removeProperty('display');
            navToggle.style.removeProperty('visibility');
            navToggle.style.removeProperty('pointer-events');
            navToggle.removeAttribute('aria-hidden');
            if (navToggle.getAttribute('tabindex') === '-1') {
                navToggle.removeAttribute('tabindex');
            }
            return;
        }

        setImportantStyle(navToggle, 'display', 'none');
        setImportantStyle(navToggle, 'visibility', 'hidden');
        setImportantStyle(navToggle, 'pointer-events', 'none');
        navToggle.setAttribute('aria-hidden', 'true');
        navToggle.setAttribute('tabindex', '-1');
    }

    function stabilizeInternalMobileHeaderLayout() {
        const isHome = isHomeHeaderContext();
        const isMobile = window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
        const rows = document.querySelectorAll('.header .header_main .wp-header, .header .header-main .wp-header');

        if (isHome || !isMobile || !rows.length) {
            return;
        }

        for (let i = 0; i < rows.length; i += 1) {
            const row = rows[i];
            const firstCol = row.querySelector(':scope > [class*="col-"]:first-child');
            const topSearch = row.querySelector(':scope > .top-search');
            const searchBlock = topSearch ? topSearch.querySelector(':scope > .block-search') : null;
            const miniCartWrapper = topSearch ? topSearch.querySelector(':scope > .mini-cart-wrapper') : null;
            const miniCarts = miniCartWrapper ? miniCartWrapper.querySelector(':scope > .mini-carts, .minicart-wrapper') : null;
            const miniCartTrigger = miniCartWrapper ? miniCartWrapper.querySelector('.showcart, .action.showcart') : null;
            const logo = firstCol ? firstCol.querySelector('.logo') : row.querySelector('.logo');
            const logoLink = logo ? logo.querySelector('a') : null;
            const logoImg = logo ? logo.querySelector('img') : null;

            setImportantStyle(row, 'display', 'grid');
            setImportantStyle(row, 'grid-template-columns', 'clamp(82px, 24vw, 108px) minmax(0, 1fr)');
            setImportantStyle(row, 'align-items', 'center');
            setImportantStyle(row, 'gap', '8px');
            setImportantStyle(row, 'width', '100%');
            setImportantStyle(row, 'max-width', '100%');
            setImportantStyle(row, 'min-width', '0');

            if (firstCol) {
                setImportantStyle(firstCol, 'display', 'flex');
                setImportantStyle(firstCol, 'align-items', 'center');
                setImportantStyle(firstCol, 'justify-content', 'flex-start');
                setImportantStyle(firstCol, 'grid-column', '1');
                setImportantStyle(firstCol, 'width', '100%');
                setImportantStyle(firstCol, 'max-width', '100%');
                setImportantStyle(firstCol, 'min-width', '0');
                setImportantStyle(firstCol, 'flex', '0 0 auto');
                setImportantStyle(firstCol, 'overflow', 'visible');
            }

            if (topSearch) {
                setImportantStyle(topSearch, 'display', 'grid');
                setImportantStyle(topSearch, 'grid-template-columns', 'minmax(0, 1fr) 44px');
                setImportantStyle(topSearch, 'grid-template-areas', '"search cart"');
                setImportantStyle(topSearch, 'grid-template-rows', '44px');
                setImportantStyle(topSearch, 'align-items', 'center');
                setImportantStyle(topSearch, 'gap', '8px');
                setImportantStyle(topSearch, 'grid-column', '2');
                setImportantStyle(topSearch, 'width', '100%');
                setImportantStyle(topSearch, 'max-width', '100%');
                setImportantStyle(topSearch, 'min-width', '0');
                setImportantStyle(topSearch, 'min-height', '44px');
                setImportantStyle(topSearch, 'margin', '0');
                setImportantStyle(topSearch, 'position', 'relative');
            }

            if (searchBlock) {
                setImportantStyle(searchBlock, 'grid-area', 'search');
                setImportantStyle(searchBlock, 'grid-column', '1');
                setImportantStyle(searchBlock, 'width', '100%');
                setImportantStyle(searchBlock, 'max-width', '100%');
                setImportantStyle(searchBlock, 'min-width', '0');
                setImportantStyle(searchBlock, 'order', '0');
            }

            if (miniCartWrapper) {
                setImportantStyle(miniCartWrapper, 'display', 'block');
                setImportantStyle(miniCartWrapper, 'position', 'static');
                setImportantStyle(miniCartWrapper, 'inset', 'auto');
                setImportantStyle(miniCartWrapper, 'top', 'auto');
                setImportantStyle(miniCartWrapper, 'right', 'auto');
                setImportantStyle(miniCartWrapper, 'left', 'auto');
                setImportantStyle(miniCartWrapper, 'bottom', 'auto');
                setImportantStyle(miniCartWrapper, 'grid-area', 'cart');
                setImportantStyle(miniCartWrapper, 'grid-column', '2');
                setImportantStyle(miniCartWrapper, 'width', '44px');
                setImportantStyle(miniCartWrapper, 'min-width', '44px');
                setImportantStyle(miniCartWrapper, 'max-width', '44px');
                setImportantStyle(miniCartWrapper, 'min-height', '44px');
                setImportantStyle(miniCartWrapper, 'margin', '0');
                setImportantStyle(miniCartWrapper, 'order', '0');
            }

            if (miniCarts) {
                setImportantStyle(miniCarts, 'position', 'static');
                setImportantStyle(miniCarts, 'inset', 'auto');
                setImportantStyle(miniCarts, 'width', '44px');
                setImportantStyle(miniCarts, 'min-width', '44px');
                setImportantStyle(miniCarts, 'max-width', '44px');
                setImportantStyle(miniCarts, 'height', '44px');
                setImportantStyle(miniCarts, 'display', 'flex');
                setImportantStyle(miniCarts, 'align-items', 'center');
                setImportantStyle(miniCarts, 'justify-content', 'center');
            }

            if (miniCartTrigger) {
                setImportantStyle(miniCartTrigger, 'position', 'static');
                setImportantStyle(miniCartTrigger, 'inset', 'auto');
                setImportantStyle(miniCartTrigger, 'width', '44px');
                setImportantStyle(miniCartTrigger, 'min-width', '44px');
                setImportantStyle(miniCartTrigger, 'height', '44px');
                setImportantStyle(miniCartTrigger, 'display', 'inline-flex');
                setImportantStyle(miniCartTrigger, 'align-items', 'center');
                setImportantStyle(miniCartTrigger, 'justify-content', 'center');
                setImportantStyle(miniCartTrigger, 'margin', '0');
            }

            if (logo) {
                setImportantStyle(logo, 'display', 'flex');
                setImportantStyle(logo, 'align-items', 'center');
                setImportantStyle(logo, 'justify-content', 'flex-start');
                setImportantStyle(logo, 'position', 'static');
                setImportantStyle(logo, 'left', '0');
                setImportantStyle(logo, 'right', 'auto');
                setImportantStyle(logo, 'width', 'clamp(82px, 24vw, 108px)');
                setImportantStyle(logo, 'min-width', '82px');
                setImportantStyle(logo, 'max-width', '108px');
                setImportantStyle(logo, 'margin', '0');
                setImportantStyle(logo, 'float', 'none');
                setImportantStyle(logo, 'transform', 'none');
                setImportantStyle(logo, 'overflow', 'visible');
            }

            if (logoLink) {
                setImportantStyle(logoLink, 'display', 'inline-flex');
                setImportantStyle(logoLink, 'align-items', 'center');
                setImportantStyle(logoLink, 'justify-content', 'flex-start');
                setImportantStyle(logoLink, 'position', 'static');
                setImportantStyle(logoLink, 'left', '0');
                setImportantStyle(logoLink, 'right', 'auto');
                setImportantStyle(logoLink, 'width', '100%');
                setImportantStyle(logoLink, 'max-width', '100%');
                setImportantStyle(logoLink, 'margin', '0');
                setImportantStyle(logoLink, 'float', 'none');
                setImportantStyle(logoLink, 'transform', 'none');
            }

            if (logoImg) {
                setImportantStyle(logoImg, 'display', 'block');
                setImportantStyle(logoImg, 'width', '100%');
                setImportantStyle(logoImg, 'max-width', '100%');
                setImportantStyle(logoImg, 'height', 'auto');
                setImportantStyle(logoImg, 'max-height', '56px');
                setImportantStyle(logoImg, 'margin', '0');
                setImportantStyle(logoImg, 'transform', 'none');
            }
        }
    }

    function capHomeMenuColumn() {
        const isHome = isHomeHeaderContext();
        const isDesktop = window.matchMedia && window.matchMedia('(min-width: 768px)').matches;
        const navShell = getHomeNavRoot();
        const navContainer = navShell ? navShell.querySelector(':scope > .container') : null;
        const navRow = navContainer ? navContainer.querySelector(':scope > .row') : null;
        const menuColumn = navShell ? navShell.querySelector('.menu_left_home1') : null;
        const menuNav = menuColumn ? menuColumn.querySelector('.navigation.verticalmenu.side-verticalmenu') : null;
        const list = menuColumn ? menuColumn.querySelector('.list-category-dropdown') : null;
        const promo = list ? list.querySelector(':scope > .vertical-bg-img') : null;
        const expandLink = list ? list.querySelector(':scope > .expand-category-link') : null;
        const isMuellerMenu = !!(menuNav && menuNav.classList.contains('navigation--mueller'));

        if (!isHome || !isDesktop || !navShell) {
            return;
        }

        if (isMuellerMenu) {
            // Mueller menu controls layout/overflow in its own CSS contract.
            return;
        }

        setImportantStyle(navShell, 'height', 'auto');
        setImportantStyle(navShell, 'min-height', '0');
        setImportantStyle(navContainer, 'height', 'auto');
        setImportantStyle(navContainer, 'min-height', '0');
        setImportantStyle(navRow, 'height', 'auto');
        setImportantStyle(navRow, 'min-height', '0');
        setImportantStyle(menuColumn, 'max-height', 'none');
        setImportantStyle(menuColumn, 'overflow', 'visible');

        setImportantStyle(list, 'max-height', 'none');
        setImportantStyle(list, 'overflow-x', 'visible');
        setImportantStyle(list, 'overflow-y', 'visible');

        setImportantStyle(promo, 'display', 'none');
        setImportantStyle(expandLink, 'position', 'static');
        setImportantStyle(expandLink, 'inset-block-end', 'auto');
        setImportantStyle(expandLink, 'background', 'transparent');
        setImportantStyle(expandLink, 'z-index', 'auto');
    }

    function syncMobilePlpFilterToggle() {
        const body = getBody();
        const isMobile = window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
        const toolbar = document.querySelector('.shop-tab-select .toolbar.toolbar-products');
        const filterBlock = document.querySelector('#layered-ajax-filter-block, .block.filter');

        if (!body || !isPlpContext() || !toolbar || !filterBlock) {
            return;
        }

        const modesWrap = toolbar.querySelector('.modes');
        const toggle = modesWrap ? modesWrap.querySelector('.modes-label') : null;

        if (!toggle) {
            return;
        }

        toolbar.classList.add('awa-filter-toggle-ready');
        modesWrap.setAttribute('data-awa-filter-toggle-ready', 'true');
        toggle.setAttribute('role', 'button');
        toggle.setAttribute('tabindex', '0');
        toggle.setAttribute('data-awa-filter-toggle', 'true');
        const filterBlockId = filterBlock.getAttribute('id') || 'awa-plp-filter-panel';
        if (!filterBlock.getAttribute('id')) {
            filterBlock.setAttribute('id', filterBlockId);
        }
        toggle.setAttribute('aria-controls', filterBlockId);

        if (isMobile && !body.getAttribute('data-awa-filter-init')) {
            body.setAttribute('data-awa-filter-init', 'true');
            body.classList.add('awa-plp-filters-collapsed');
        } else if (!isMobile) {
            body.classList.remove('awa-plp-filters-collapsed');
        }

        const collapsed = isMobile && body.classList.contains('awa-plp-filters-collapsed');
        const toggleLabel = collapsed ? 'Mostrar Filtros' : 'Ocultar Filtros';

        toggle.textContent = toggleLabel;
        toggle.setAttribute('aria-label', toggleLabel);
        toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        filterBlock.setAttribute('aria-hidden', collapsed ? 'true' : 'false');
    }

    function syncPlpMobileStickyToolbarOffset() {
        const body = getBody();
        const root = document.documentElement;
        const isMobile = window.matchMedia && window.matchMedia('(max-width: 767px)').matches;
        const toolbar = document.querySelector('.shop-tab-select .toolbar.toolbar-products');

        if (!root) {
            return;
        }

        if (!body || !isPlpContext() || !isMobile || !toolbar) {
            root.style.removeProperty('--awa-plp-toolbar-top-offset');
            return;
        }

        const topStackBottom = measureVisibleTopStackBottom();
        const offset = Math.round(Math.min(220, Math.max(6, topStackBottom + 8)));

        root.style.setProperty('--awa-plp-toolbar-top-offset', `${offset}px`);
    }

    function handleMobilePlpFilterToggle(event) {
        const target = event && event.target ? event.target : null;
        const toggle = target ? target.closest('.toolbar .modes .modes-label[data-awa-filter-toggle="true"]') : null;
        const body = getBody();
        const isMobile = window.matchMedia && window.matchMedia('(max-width: 767px)').matches;

        if (!toggle || !body || !isMobile || !isPlpContext()) {
            return;
        }

        event.preventDefault();
        body.classList.toggle('awa-plp-filters-collapsed');
        scheduleRuntimeSync();
    }

    function handleMobilePlpFilterToggleKeydown(event) {
        const target = event && event.target ? event.target : null;
        const toggle = target ? target.closest('.toolbar .modes .modes-label[data-awa-filter-toggle="true"]') : null;

        if (!toggle) {
            return;
        }

        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            handleMobilePlpFilterToggle(event);
        }
    }

    function enforceHeaderRuntimeGuards() {
        normalizeSearchFormAction();
        enforceSearchInlineLayout();
        enforceSearchButtonTouchTarget();
        suppressNonHomeNavToggle();
        stabilizeInternalMobileHeaderLayout();
        capHomeMenuColumn();
        syncPlpMobileStickyToolbarOffset();
        syncMobilePlpFilterToggle();
    }

    function syncRuntimeLayout() {
        ensureSearchCompatBridge();
        enforceHeaderRuntimeGuards();
        bindProductImageFallback(document);
        syncA11yLabels();
        syncSearchState();
        classifyTopLinks();
        syncTopLinkCounters();
    }

    function syncRuntimeAndSticky() {
        syncCondensedState();
        syncRuntimeLayout();
    }

    function scheduleRuntimeSync() {
        if (scheduled) {
            return;
        }

        scheduled = true;
        window.requestAnimationFrame(() => {
            scheduled = false;
            syncRuntimeAndSticky();
        });
    }

    function syncCondensedState() {
        const wrapper = getStickyWrapper();
        const body = getBody();

        if (!body || !wrapper || !isStickyFeatureEnabled(wrapper)) {
            return;
        }

        const shouldCondense = window.scrollY >= SCROLL_THRESHOLD;

        wrapper.classList.toggle('awa-header-condensed', shouldCondense);
        body.classList.toggle('awa-header-condensed', shouldCondense);
    }

    onReady(() => {
        syncRuntimeAndSticky();

        window.addEventListener('scroll', scheduleRuntimeSync, { passive: true });
        window.addEventListener('resize', scheduleRuntimeSync, { passive: true });
        document.addEventListener('keydown', handleSearchAutocompleteKeyboard, true);
        document.addEventListener('mouseover', handleSearchAutocompleteHover, true);
        document.addEventListener('focusin', scheduleRuntimeSync, true);
        document.addEventListener('keyup', scheduleRuntimeSync, true);
        document.addEventListener('click', scheduleRuntimeSync, true);
        document.addEventListener('input', scheduleRuntimeSync, true);
        document.addEventListener('click', handleMobilePlpFilterToggle, true);
        document.addEventListener('keydown', handleMobilePlpFilterToggleKeydown, true);

        if (window.MutationObserver) {
            const wrapper = getStickyWrapper();
            const searchScope = getSearchScope();

            if (wrapper) {
                stickyObserver = new MutationObserver(scheduleRuntimeSync);
                stickyObserver.observe(wrapper, {
                    attributes: true,
                    attributeFilter: ['class']
                });
            }

            if (searchScope) {
                searchObserver = new MutationObserver(scheduleRuntimeSync);
                searchObserver.observe(searchScope, {
                    childList: true,
                    subtree: true,
                    attributes: true,
                    attributeFilter: ['class', 'style', 'aria-expanded', 'aria-hidden']
                });
            }
        }
    });
}());
