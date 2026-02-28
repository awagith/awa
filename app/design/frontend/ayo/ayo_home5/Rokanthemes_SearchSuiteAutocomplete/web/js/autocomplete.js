/**
 * AWA Autocomplete — override com Recent Searches (localStorage)
 * Mantém estrutura idêntica ao original, apenas adiciona recentSearches.
 */
define([
    'jquery',
    'uiComponent',
    'ko'
], function ($, Component, ko) {
    'use strict';

    var STORAGE_KEY = 'awa_recent_searches';
    var MAX_RECENT = 6;

    return Component.extend({
        defaults: {
            template: 'Rokanthemes_SearchSuiteAutocomplete/autocomplete',
            addToCartFormSelector: '[data-role=searchsuiteautocomplete-tocart-form]',
            showPopup: ko.observable(false),
            result: {
                suggest: {
                    data: ko.observableArray([])
                },
                product: {
                    data: ko.observableArray([]),
                    size: ko.observable(0),
                    url: ko.observable('')
                }
            },
            anyResultCount: false
        },

        initialize: function () {
            var self = this;
            this._super();

            this.recentSearches = ko.observableArray([]);

            this.anyResultCount = ko.computed(function () {
                var sum = self.result.suggest.data().length + self.result.product.data().length;
                if (sum > 0) {
                    return true;
                }
                return false;
            }, this);

            this._loadRecentSearches();
            this._watchSearchSubmit();
        },

        _loadRecentSearches: function () {
            try {
                var stored = localStorage.getItem(STORAGE_KEY);
                if (stored) {
                    var items = JSON.parse(stored);
                    if (Array.isArray(items)) {
                        this.recentSearches(items.slice(0, MAX_RECENT));
                    }
                }
            } catch (e) {
                // silencioso
            }
        },

        saveRecentSearch: function (term) {
            if (!term || term.length < 2) {
                return;
            }
            term = $.trim(term).toLowerCase();
            try {
                var stored = localStorage.getItem(STORAGE_KEY);
                var items = stored ? JSON.parse(stored) : [];
                items = $.grep(items, function (item) {
                    return item.term !== term;
                });
                items.unshift({
                    term: term,
                    url: '/catalogsearch/result/?q=' + encodeURIComponent(term)
                });
                items = items.slice(0, MAX_RECENT);
                localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
                this.recentSearches(items);
            } catch (e) {
                // silencioso
            }
        },

        _watchSearchSubmit: function () {
            var self = this;
            $(document).on('submit', '#search_mini_form', function () {
                var val = $(this).find('input[name="q"]').val();
                if (val) {
                    self.saveRecentSearch(val);
                }
            });
        }
    });
});
