define([
    'jquery'
], function ($) {
    'use strict';

    const DEFAULT_OPTIONS = {
        inputSelector: '#search-input-autocomplate, #search, input[name="q"]',
        panelSelector: '#search_autocomplete',
        resultsRootSelector: '.searchsuite-autocomplete',
        fallbackEndpoint: '',
        searchResultUrl: '/catalogsearch/result/',
        minQueryLength: 2,
        fallbackDelay: 260,
        fallbackTimeout: 8000,
        fallbackSuggestLimit: 6,
        fallbackProductLimit: 6,
        recentSearchStorageKey: 'awa_recent_searches',
        maxRecentSearches: 6,
        popularKeywords: [
            'Bagageiro',
            'Bauleto',
            'Retrovisor',
            'Protetor de motor',
            'Suporte de celular',
            'CG 160',
            'Bros 160',
            'XRE 300'
        ]
    };
    const AUTO_BOOT_KEY = '__awaSearchCompatAutoBoot';
    const AUTO_OBSERVER_KEY = '__awaSearchCompatAutoObserver';
    const SEARCH_FORM_SELECTOR = 'form.form.minisearch, #search_mini_form';

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function toText(value) {
        return $('<div/>').html(value || '').text();
    }

    function findScoped($form, selector) {
        let $scope;
        let $found;

        if (!$form || !$form.length) {
            return $(selector).first();
        }

        $found = $form.find(selector).first();
        if ($found.length) {
            return $found;
        }

        $scope = $form.closest('.block-search, .header .search, .top-search');
        if ($scope.length) {
            $found = $scope.find(selector).first();
            if ($found.length) {
                return $found;
            }
        }

        return $(selector).first();
    }

    function visible(el) {
        if (!el) {
            return false;
        }

        return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
    }

    function applyTitles($root) {
        $root.find('a[href]').each(function () {
            const $a = $(this);
            const text = $.trim($a.text());

            if (!text) {
                return;
            }

            if (!$a.attr('title')) {
                $a.attr('title', text);
            }

            if (!$a.attr('aria-label')) {
                $a.attr('aria-label', text);
            }
        });
    }

    function loadRecentSearches(options) {
        let parsed = [];

        try {
            parsed = JSON.parse(window.localStorage.getItem(options.recentSearchStorageKey) || '[]');
        } catch (e) {
            parsed = [];
        }

        if (!$.isArray(parsed)) {
            return [];
        }

        return parsed.filter(function (item) {
            return item && typeof item.term === 'string' && $.trim(item.term) !== '';
        }).slice(0, options.maxRecentSearches);
    }

    function saveRecentSearch(options, term) {
        const normalizedTerm = $.trim(term || '');
        let recentSearches;

        if (normalizedTerm.length < options.minQueryLength) {
            return;
        }

        recentSearches = loadRecentSearches(options).filter(function (item) {
            return item.term.toLowerCase() !== normalizedTerm.toLowerCase();
        });

        recentSearches.unshift({
            term: normalizedTerm
        });

        try {
            window.localStorage.setItem(
                options.recentSearchStorageKey,
                JSON.stringify(recentSearches.slice(0, options.maxRecentSearches))
            );
        } catch (e) {
            // localStorage unavailable
        }
    }

    function clearRecentSearches(options) {
        try {
            window.localStorage.removeItem(options.recentSearchStorageKey);
        } catch (e) {
            // localStorage unavailable
        }
    }

    function buildSearchUrl(options, term) {
        const searchResultUrl = (options.searchResultUrl || '/catalogsearch/result/').replace(/\/+$/, '');

        return searchResultUrl + '/?q=' + encodeURIComponent(term);
    }

    function buildDiscoveryMarkup(options) {
        const recentSearches = loadRecentSearches(options);
        let html = '';

        html += '<div class="awa-ac-discovery">';
        html += '<div class="awa-ac-discovery-intro">';
        html += '<div class="awa-ac-section-title awa-ac-section-title--discovery">talvez voce procure por...</div>';
        html += '<p class="awa-ac-discovery-copy">atalhos rapidos para as buscas mais comuns da loja.</p>';
        html += '</div>';

        if (recentSearches.length) {
            html += '<div class="awa-ac-recent--discovery">';
            html += '<div class="awa-ac-section-title awa-ac-section-title--recent">';
            html += '<span>Buscas recentes</span>';
            html += '<button class="awa-ac-clear-recent" type="button" data-awa-action="clear-recent-searches">Limpar</button>';
            html += '</div>';
            html += '<div class="awa-ac-chips">';

            recentSearches.forEach(function (item) {
                html += '<a class="awa-ac-chip" href="' + escapeHtml(buildSearchUrl(options, item.term)) + '" data-awa-action="recent-search" data-term="' + escapeHtml(item.term) + '">';
                html += '<svg class="awa-ac-chip-icon" viewBox="0 0 16 16" width="13" height="13" aria-hidden="true">';
                html += '<path d="M8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1zm0 1.2A5.8 5.8 0 1 1 2.2 8 5.8 5.8 0 0 1 8 2.2zm-.4 2.3v4l3.2 1.9.6-1-2.6-1.6V4.5h-1.2z" fill="currentColor"/>';
                html += '</svg>';
                html += '<span>' + escapeHtml(item.term) + '</span>';
                html += '</a>';
            });

            html += '</div>';
            html += '</div>';
        }

        html += '<div class="awa-ac-keywords-section">';
        html += '<div class="awa-ac-section-title">Mais buscados</div>';
        html += '<ol class="awa-ac-keywords">';

        options.popularKeywords.forEach(function (term) {
            html += '<li class="awa-ac-keywords-item">';
            html += '<a class="awa-ac-keywords-link" href="' + escapeHtml(buildSearchUrl(options, term)) + '" data-awa-action="popular-keyword" data-term="' + escapeHtml(term) + '">';
            html += '<span>' + escapeHtml(term) + '</span>';
            html += '</a>';
            html += '</li>';
        });

        html += '</ol>';
        html += '</div>';
        html += '</div>';

        return html;
    }

    function extractSuggestItems(suggestRaw) {
        const suggest = [];

        $.each(suggestRaw || [], function (_, item) {
            let label = '';

            if (typeof item === 'string') {
                label = item;
            } else if (item && typeof item === 'object') {
                label = item.query_text || item.label || item.value || item.name || '';
            }

            label = $.trim(label);
            if (label) {
                suggest.push(label);
            }
        });

        return suggest;
    }

    function extractProductItems(productRaw) {
        const products = [];

        $.each(productRaw || [], function (_, item) {
            if (!item || typeof item !== 'object') {
                return;
            }

            products.push({
                name: $.trim(item.name || ''),
                url: $.trim(item.url || '#'),
                image: $.trim(item.image || ''),
                priceText: $.trim(toText(item.price || ''))
            });
        });

        return products;
    }

    function normalizeFallbackPayload(payload) {
        let suggest = [];
        let products = [];

        if (payload && $.isArray(payload.result)) {
            $.each(payload.result, function (_, chunk) {
                if (!chunk || typeof chunk !== 'object') {
                    return;
                }

                if (chunk.code === 'suggest') {
                    suggest = extractSuggestItems(chunk.data);
                    return;
                }

                if (chunk.code === 'product') {
                    products = extractProductItems(chunk.data);
                }
            });
        }

        return {
            suggest: suggest,
            products: products
        };
    }

    function buildFallbackMarkup(normalized, options, query) {
        let html = '';

        if (normalized.suggest.length || normalized.products.length) {
            html += '<div class="awa-ac-results">';
            html += '<div class="awa-ac-wrap">';

            if (normalized.suggest.length) {
                html += '<div class="awa-ac-left">';
                html += '<div class="awa-ac-suggest">';
                html += '<div class="awa-ac-section-title">Sugestoes</div>';
                html += '<ul role="listbox">';

                normalized.suggest.slice(0, options.fallbackSuggestLimit).forEach(function (label) {
                    html += '<li class="awa-fallback-item" role="option">';
                    html += '<a href="' + escapeHtml(buildSearchUrl(options, label)) + '">';
                    html += '<svg class="awa-ac-icon-search" viewBox="0 0 20 20" width="14" height="14" aria-hidden="true">';
                    html += '<path d="M8.5 3a5.5 5.5 0 0 1 4.383 8.823l3.896 3.9a.75.75 0 0 1-1.06 1.06l-3.9-3.896A5.5 5.5 0 1 1 8.5 3zm0 1.5a4 4 0 1 0 0 8 4 4 0 0 0 0-8z" fill="currentColor"/>';
                    html += '</svg>';
                    html += '<span>' + escapeHtml(label) + '</span>';
                    html += '</a>';
                    html += '</li>';
                });

                html += '</ul>';
                html += '</div>';
                html += '</div>';
            }

            if (normalized.products.length) {
                html += '<div class="awa-ac-right">';
                html += '<div class="awa-ac-section-title">Produtos</div>';
                html += '<ul class="awa-ac-products" role="listbox">';

                normalized.products.slice(0, options.fallbackProductLimit).forEach(function (product) {
                    html += '<li class="awa-ac-product-item awa-fallback-item" role="option">';

                    if (product.image) {
                        html += '<a class="awa-ac-product-image" href="' + escapeHtml(product.url || '#') + '">';
                        html += '<img src="' + escapeHtml(product.image) + '" alt="' + escapeHtml(product.name || query) + '" loading="lazy" />';
                        html += '</a>';
                    }

                    html += '<div class="awa-ac-product-info' + (product.image ? '' : ' awa-ac-noimage') + '">';
                    html += '<a class="awa-ac-product-name" href="' + escapeHtml(product.url || '#') + '">' + escapeHtml(product.name || query) + '</a>';

                    if (product.priceText) {
                        html += '<div class="awa-ac-product-price"><span class="price">' + escapeHtml(product.priceText) + '</span></div>';
                    }

                    html += '</div>';
                    html += '</li>';
                });

                html += '</ul>';
                html += '</div>';
            }

            html += '</div>';
            html += '</div>';
        }

        if (!html) {
            html = '<div class="awa-ac-no-result">'
                + '<svg viewBox="0 0 20 20" width="20" height="20" aria-hidden="true">'
                + '<path d="M8.5 3a5.5 5.5 0 0 1 4.383 8.823l3.896 3.9a.75.75 0 0 1-1.06 1.06l-3.9-3.896A5.5 5.5 0 1 1 8.5 3zm0 1.5a4 4 0 1 0 0 8 4 4 0 0 0 0-8z" fill="currentColor"/>'
                + '</svg>'
                + '<span>Nenhum resultado encontrado.</span>'
                + '</div>';
        }

        return html;
    }

    function getFallbackPanel($form, options) {
        const $panel = findScoped($form, options.panelSelector);

        if ($panel.length) {
            return $panel;
        }

        return findScoped($form, options.resultsRootSelector);
    }

    function isMirasvitAutocompleteActive() {
        return document.getElementById('searchAutocompletePlaceholder') !== null
            || document.querySelector('.mst-searchautocomplete__autocomplete') !== null;
    }

    function hasNativeResults($form, options) {
        const $resultsRoot = findScoped($form, options.resultsRootSelector);
        const $panel = getFallbackPanel($form, options);
        let $nativeItems = $();

        if (isMirasvitAutocompleteActive()) {
            return true;
        }

        if ($resultsRoot.length) {
            $nativeItems = $nativeItems.add($resultsRoot.find('li').not('.awa-fallback-item'));
        }

        if ($panel.length) {
            $nativeItems = $nativeItems.add($panel.find('li').not('.awa-fallback-item'));
        }

        return $nativeItems.length > 0;
    }

    function getNavigableItems($panel) {
        return $panel.find(
            '.awa-ac-keywords-link, .awa-ac-chip, .awa-ac-suggest ul li a, .awa-ac-product-item'
        ).filter(':visible');
    }

    function clearActiveState($panel) {
        $panel.find('.awa-ac-nav-active').removeClass('awa-ac-nav-active');
    }

    function updateAriaActiveDescendant($input, state) {
        if (!$input.length) {
            return;
        }

        if (state.navIndex >= 0 && state.$items && state.$items.length) {
            $input.attr('aria-activedescendant', 'awa-ac-item-' + state.navIndex);
            return;
        }

        $input.removeAttr('aria-activedescendant');
    }

    function syncNavIndex(state) {
        if (!state.$panel || !state.$panel.length) {
            state.navIndex = -1;
            return;
        }

        state.$items = getNavigableItems(state.$panel);

        if (!state.$items.length) {
            state.navIndex = -1;
            clearActiveState(state.$panel);
            return;
        }

        if (state.navIndex >= state.$items.length) {
            state.navIndex = state.$items.length - 1;
        }
    }

    function applyNavState($form, state) {
        const $input = findScoped($form, state.options.inputSelector);

        if (!state.$panel || !state.$panel.length) {
            return;
        }

        syncNavIndex(state);
        clearActiveState(state.$panel);

        if (state.navIndex < 0 || !state.$items || !state.$items.length) {
            updateAriaActiveDescendant($input, state);
            return;
        }

        state.$items.each(function (index) {
            $(this).attr('id', 'awa-ac-item-' + index);
        });

        $(state.$items.get(state.navIndex)).addClass('awa-ac-nav-active');
        updateAriaActiveDescendant($input, state);

        if (typeof state.$items.get(state.navIndex).scrollIntoView === 'function') {
            state.$items.get(state.navIndex).scrollIntoView({
                block: 'nearest',
                behavior: 'smooth'
            });
        }
    }

    function renderPanelContent($form, state, html, panelState) {
        const $panel = state.$panel;
        const $input = findScoped($form, state.options.inputSelector);

        if (!$panel || !$panel.length) {
            return;
        }

        state.navIndex = -1;
        $panel.html(html)
            .removeAttr('hidden')
            .show()
            .attr('aria-hidden', 'false')
            .attr('data-awa-panel-state', panelState)
            .attr('data-awa-fallback-rendered', 'true');

        if ($input.length) {
            $input.attr('aria-expanded', 'true');
        }

        applyTitles($panel);
        applyNavState($form, state);
    }

    function renderDiscovery($form, options, state) {
        renderPanelContent($form, state, buildDiscoveryMarkup(options), 'discovery');
        $form.addClass('is-open');
    }

    function clearFallback($form, options, state) {
        const $panel = getFallbackPanel($form, options);
        const $input = findScoped($form, options.inputSelector);

        if (!$panel.length) {
            return;
        }

        if ($panel.attr('data-awa-fallback-rendered') === 'true') {
            $panel.empty();
            $panel.removeAttr('data-awa-fallback-rendered');
            $panel.removeAttr('data-awa-panel-state');
            $panel.attr('hidden', 'hidden');
            $panel.hide();
            $panel.attr('aria-hidden', 'true');
        }

        if ($input.length) {
            $input.attr('aria-expanded', 'false');
            $input.removeAttr('aria-activedescendant');
        }

        if (state) {
            state.navIndex = -1;
            state.$items = $();
        }
    }

    function renderFallback($form, options, payload, query) {
        const state = $form.data('awaSearchCompatState');
        const normalized = normalizeFallbackPayload(payload);
        const panelState = normalized.suggest.length || normalized.products.length ? 'results' : 'empty';

        renderPanelContent($form, state, buildFallbackMarkup(normalized, options, query), panelState);
        $form.addClass('is-open');
    }

    function resolveFallbackEndpoint($form, options) {
        const explicit = options.fallbackEndpoint || '';
        const attrEndpoint = $form.attr('data-awa-search-endpoint') || '';

        if (explicit) {
            return explicit;
        }

        if (attrEndpoint) {
            return attrEndpoint;
        }

        return '/search/ajax/suggest';
    }

    function buildCacheKey(query, categoryValue) {
        return query + '::' + (categoryValue || '');
    }

    function syncState($form, options) {
        const $input = findScoped($form, options.inputSelector);
        const $panel = findScoped($form, options.panelSelector);
        const panelEl = $panel.get(0);
        const $resultsRoot = findScoped($form, options.resultsRootSelector);
        const hasPanel = $panel.length > 0;
        const isVisible = hasPanel && visible(panelEl);
        const query = $input.length ? $.trim($input.val() || '') : '';
        let hasResults = false;

        if ($resultsRoot.length && visible($resultsRoot.get(0))) {
            hasResults = $resultsRoot.find('li').length > 0;
        } else if (hasPanel) {
            hasResults = $.trim($panel.text()).length > 0 || $panel.children().length > 0;
        }

        $form.toggleClass('is-open', !!isVisible)
            .toggleClass('has-results', !!hasResults)
            .toggleClass('is-empty', !hasResults)
            .toggleClass('has-query', query.length >= options.minQueryLength)
            .toggleClass('is-query-empty', query.length < options.minQueryLength);

        $form.closest('.block-search')
            .toggleClass('has-query', query.length >= options.minQueryLength)
            .toggleClass('is-query-empty', query.length < options.minQueryLength);

        if ($input.length) {
            $input.attr('aria-expanded', isVisible ? 'true' : 'false');
        }

        if (hasPanel) {
            $panel.attr('aria-hidden', isVisible ? 'false' : 'true');
            $panel.toggleClass('is-open', !!isVisible)
                .toggleClass('has-results', !!hasResults);
        }

        if ($resultsRoot.length) {
            $resultsRoot.attr('data-awa-component', 'search-results')
                .toggleClass('is-open', !!isVisible)
                .toggleClass('has-results', !!hasResults);

            $resultsRoot.find('ul').attr('role', 'listbox');
            $resultsRoot.find('li').attr('role', 'option');
            applyTitles($resultsRoot);
        }
    }

    function runFallbackRequest($form, options, state, query) {
        const endpoint = resolveFallbackEndpoint($form, options);
        const $category = $form.find('#choose_category');
        const categoryValue = $category.length ? $.trim($category.val() || '') : '';
        const cacheKey = buildCacheKey(query, categoryValue);
        const params = {
            q: query
        };

        if (!endpoint || hasNativeResults($form, options)) {
            clearFallback($form, options, state);
            return;
        }

        if (categoryValue) {
            params.cat = categoryValue;
        }

        if (state.xhr && state.xhr.abort) {
            state.xhr.abort();
        }

        state.requestId += 1;
        state.lastRequestId = state.requestId;

        state.xhr = $.ajax({
            url: endpoint,
            method: 'GET',
            dataType: 'json',
            data: params,
            cache: false,
            timeout: options.fallbackTimeout
        }).done(function (response) {
            if (state.lastRequestId !== state.requestId) {
                return;
            }

            if (!query || query.length < options.minQueryLength || hasNativeResults($form, options)) {
                clearFallback($form, options, state);
                return;
            }

            state.cache[cacheKey] = response;
            renderFallback($form, options, response, query);
            syncState($form, options);
        }).fail(function (_xhr, status) {
            if (status === 'abort') {
                return;
            }

            clearFallback($form, options, state);
            syncState($form, options);
        }).always(function () {
            state.xhr = null;
        });
    }

    function scheduleFallbackRequest($form, options, state, query) {
        const $category = $form.find('#choose_category');
        const categoryValue = $category.length ? $.trim($category.val() || '') : '';
        const cacheKey = buildCacheKey(query, categoryValue);

        if (state.timer) {
            window.clearTimeout(state.timer);
        }

        if (!query || query.length < options.minQueryLength) {
            renderDiscovery($form, options, state);
            syncState($form, options);
            return;
        }

        if (state.cache[cacheKey]) {
            if (!hasNativeResults($form, options)) {
                renderFallback($form, options, state.cache[cacheKey], query);
                syncState($form, options);
            }
            return;
        }

        state.timer = window.setTimeout(function () {
            runFallbackRequest($form, options, state, query);
        }, options.fallbackDelay);
    }

    function initCompat(config, element) {
        const options = $.extend({}, DEFAULT_OPTIONS, config || {});
        const $form = $(element);
        let observer;
        let bodyObserver;
        let panelNode;
        let scheduled = false;
        let scopeNode;
        const fallbackState = {
            timer: null,
            xhr: null,
            cache: {},
            requestId: 0,
            lastRequestId: 0,
            navIndex: -1,
            $items: $(),
            $panel: $(),
            options: options
        };

        if (!$form.length || $form.data('awaSearchCompatInit')) {
            return;
        }

        if (document.getElementById('searchAutocompletePlaceholder') !== null) {
            return;
        }

        function flushSync() {
            scheduled = false;
            attachPanelObserver();
            syncState($form, options);
        }

        function scheduleSync() {
            if (scheduled) {
                return;
            }

            scheduled = true;

            if (typeof window.requestAnimationFrame === 'function') {
                window.requestAnimationFrame(flushSync);
                return;
            }

            window.setTimeout(flushSync, 0);
        }

        function attachPanelObserver() {
            if (!observer) {
                return false;
            }

            const nextPanelNode = findScoped($form, options.panelSelector).get(0);
            if (!nextPanelNode) {
                return false;
            }

            if (panelNode === nextPanelNode) {
                return true;
            }

            observer.disconnect();
            panelNode = nextPanelNode;
            observer.observe(panelNode, {
                subtree: true,
                childList: true,
                attributes: true,
                attributeFilter: ['class', 'style']
            });

            return true;
        }

        $form.attr({
            'data-awa-component': $form.attr('data-awa-component') || 'search-autocomplete',
            'data-awa-initialized': 'true'
        }).addClass('is-ready');

        fallbackState.$panel = getFallbackPanel($form, options);
        $form.data('awaSearchCompatState', fallbackState);

        scheduleSync();

        $form.on('focusin.awaSearchCompat input.awaSearchCompat keyup.awaSearchCompat', options.inputSelector, function (event) {
            const query = $.trim($(this).val() || '');

            if (event.type === 'keyup' && event.key === 'Escape') {
                $form.removeClass('is-open');
                clearFallback($form, options, fallbackState);
            }

            if (event.type === 'input' || event.type === 'keyup' || event.type === 'focusin') {
                scheduleFallbackRequest($form, options, fallbackState, query);
            }

            scheduleSync();
        });

        $form.on('focusout.awaSearchCompat', options.inputSelector, function () {
            window.setTimeout(function () {
                const activeEl = document.activeElement;
                const activePanelNode = getFallbackPanel($form, options).get(0);
                const inputNode = findScoped($form, options.inputSelector).get(0);
                const keepOpen = !!(activePanelNode && activeEl && activePanelNode.contains(activeEl)) || activeEl === inputNode;

                if (!keepOpen && !$.trim(findScoped($form, options.inputSelector).val() || '')) {
                    clearFallback($form, options, fallbackState);
                }

                scheduleSync();
            }, 100);
        });

        $form.on('submit.awaSearchCompat', function () {
            saveRecentSearch(options, $.trim(findScoped($form, options.inputSelector).val() || ''));
        });

        $form.on('click.awaSearchCompat', '[data-awa-action="popular-keyword"], [data-awa-action="recent-search"]', function (event) {
            const term = $.trim($(this).attr('data-term') || '');
            const $input = findScoped($form, options.inputSelector);

            if (!term || !$input.length) {
                return;
            }

            event.preventDefault();
            $input.val(term).trigger('input').trigger('change').focus();
            saveRecentSearch(options, term);
        });

        $form.on('click.awaSearchCompat', '[data-awa-action="clear-recent-searches"]', function (event) {
            event.preventDefault();
            clearRecentSearches(options);
            renderDiscovery($form, options, fallbackState);
            syncState($form, options);
        });

        $form.on('keydown.awaSearchCompat', options.inputSelector, function (event) {
            const panelState = fallbackState.$panel.attr('data-awa-panel-state');
            const maxIndex = fallbackState.$items.length - 1;
            let $activeItem;

            if (!fallbackState.$panel.length || fallbackState.$panel.attr('data-awa-fallback-rendered') !== 'true') {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                fallbackState.navIndex = fallbackState.navIndex < maxIndex ? fallbackState.navIndex + 1 : 0;
                applyNavState($form, fallbackState);
                return;
            }

            if (event.key === 'ArrowUp') {
                event.preventDefault();
                fallbackState.navIndex = fallbackState.navIndex > 0 ? fallbackState.navIndex - 1 : maxIndex;
                applyNavState($form, fallbackState);
                return;
            }

            if (event.key === 'Enter' && fallbackState.navIndex >= 0 && fallbackState.$items.length) {
                $activeItem = $(fallbackState.$items.get(fallbackState.navIndex));
                event.preventDefault();

                if ($activeItem.is('.awa-ac-product-item')) {
                    $activeItem.find('.awa-ac-product-name, .awa-ac-product-image').first().trigger('click');
                } else {
                    $activeItem.trigger('click');
                }

                return;
            }

            if (event.key === 'Escape' && panelState === 'discovery') {
                clearFallback($form, options, fallbackState);
                syncState($form, options);
            }
        });

        $form.on('change.awaSearchCompat', '#choose_category', function () {
            const query = $.trim(findScoped($form, options.inputSelector).val() || '');
            scheduleFallbackRequest($form, options, fallbackState, query);
            scheduleSync();
        });

        if (typeof window.MutationObserver === 'function') {
            observer = new window.MutationObserver(function () {
                scheduleSync();
            });

            attachPanelObserver();
            scopeNode = $form.closest('.block-search, .header .search, .top-search').get(0) || document.body;

            if (!panelNode && scopeNode) {
                bodyObserver = new window.MutationObserver(function () {
                    attachPanelObserver();
                    scheduleSync();
                });

                bodyObserver.observe(scopeNode, {
                    subtree: true,
                    childList: true
                });
            }

            $form.data('awaSearchCompatObserver', observer);
            if (bodyObserver) {
                $form.data('awaSearchCompatBodyObserver', bodyObserver);
            }
        }

        $form.data('awaSearchCompatInit', 1);
    }

    function bootAll(config) {
        const options = $.extend({}, DEFAULT_OPTIONS, config || {});

        $(SEARCH_FORM_SELECTOR).each(function () {
            initCompat(options, this);
        });
    }

    function shouldObserveMutation(mutations) {
        let i;
        let j;
        let mutation;
        let addedNodes;

        for (i = 0; i < mutations.length; i += 1) {
            mutation = mutations[i];
            if (!mutation || !mutation.addedNodes || !mutation.addedNodes.length) {
                continue;
            }

            addedNodes = mutation.addedNodes;
            for (j = 0; j < addedNodes.length; j += 1) {
                if (!addedNodes[j] || addedNodes[j].nodeType !== 1) {
                    continue;
                }

                if ($(addedNodes[j]).is(SEARCH_FORM_SELECTOR) || $(addedNodes[j]).find(SEARCH_FORM_SELECTOR).length) {
                    return true;
                }
            }
        }

        return false;
    }

    function autoBoot() {
        if (window[AUTO_BOOT_KEY]) {
            return;
        }

        window[AUTO_BOOT_KEY] = true;

        if (document.readyState === 'loading') {
            $(function () {
                bootAll({});
            });
        } else {
            bootAll({});
        }

        $(document).on('contentUpdated.awaSearchCompatAuto', function (event) {
            if (!event || !event.target || $(event.target).is(SEARCH_FORM_SELECTOR) || $(event.target).find(SEARCH_FORM_SELECTOR).length) {
                bootAll({});
            }
        });

        if (window.MutationObserver && document.body && !window[AUTO_OBSERVER_KEY]) {
            window[AUTO_OBSERVER_KEY] = new window.MutationObserver(function (mutations) {
                if (!shouldObserveMutation(mutations)) {
                    return;
                }

                bootAll({});
            });

            window[AUTO_OBSERVER_KEY].observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    }

    autoBoot();

    return function (config, element) {
        var form = typeof element === 'string' ? document.querySelector(element) : element;
        if (form && form.__awaSearchCompatInit) { return; }
        if (form) { form.__awaSearchCompatInit = true; }
        initCompat(config, element);
    };
});
