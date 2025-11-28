define([
    'jquery',
    'uiComponent',
    'uiRegistry',
    'underscore'
], function ($, Component, registry, _) {
    'use strict';

    return Component.extend({
        defaults: {
            localStorage: '',
            searchText: ''
        },

        load: function () {
            var self = this;

            if (this.xhr) {
                this.xhr.abort();
            }

            this.xhr = $.ajax({
                method: "get",
                dataType: "json",
                url: this.url,
                data: {q: this.searchText},
                beforeSend: function () {
                    self.spinnerShow();
                    if (self.loadFromLocalSorage(self.searchText)) {
                        self.showPopup();
                    }
                },
                success: $.proxy(function (response) {
                    self.parseData(response);
                    self.saveToLocalSorage(response, self.searchText);
                    self.spinnerHide();
                    self.showPopup();
                })
            });
        },

        showPopup: function () {
            registry.get('searchsuiteautocompleteBindEvents', function (binder) {
                binder.showPopup();
            });
        },

        spinnerShow: function () {
            registry.get('searchsuiteautocompleteBindEvents', function (binder) {
                binder.spinnerShow();
            });
        },

        spinnerHide: function () {
            registry.get('searchsuiteautocompleteBindEvents', function (binder) {
                binder.spinnerHide();
            });
        },

        parseData: function (response) {
            this.setSuggested(this.getResponseData(response, 'suggest'));
            this.setProducts(this.getResponseData(response, 'product'));
        },

        getResponseData: function (response, code) {
            var data = []

            if (_.isUndefined(response.result)) {
                return data;
            }

            $.each(response.result, function (index, obj) {
                if (obj.code == code) {
                    data = obj;
                }
            });

            return data;
        },

        setSuggested: function (suggestedData) {
            var suggested = [];

            if (!_.isUndefined(suggestedData.data)) {
                $.each(suggestedData.data, function(key_su,value_su) {
                    var su = {url: value_su.url, title: value_su.title, num_results: value_su.num_results};
                    suggested.push(su);
                });
            }

            registry.get('searchsuiteautocomplete_form', function (autocomplete) {
                autocomplete.result.suggest.data(suggested);
            });
        },

        setProducts: function (productsData) {
            var products = [];

            if (!_.isUndefined(productsData.data)) {
                $.each(productsData.data, function(key_p,value_p) {
                    var pr = {name: value_p.name, sku: value_p.sku, image: value_p.image, reviews_rating: value_p.reviews_rating, short_description: value_p.short_description, description: value_p.description, price: value_p.price, url: value_p.url, add_to_cart: value_p.add_to_cart};
                    products.push(pr);
                }); 
            }

            registry.get('searchsuiteautocomplete_form', function (autocomplete) {
                autocomplete.result.product.data(products);
                autocomplete.result.product.size(productsData.size);
                autocomplete.result.product.url(productsData.url);
            });
        },

        loadFromLocalSorage: function (queryText) {
            if (!this.localStorage) {
                return;
            }

            var hash = this._hash(queryText);
            var data = this.localStorage.get(hash);

            if (!data) {
                return false;
            }

            this.parseData(data);

            return true;
        },

        saveToLocalSorage: function (data, queryText) {
            if (!this.localStorage) {
                return;
            }

            var hash = this._hash(queryText);

            this.localStorage.remove(hash);
            this.localStorage.set(hash, data);
        },

        _hash: function (object) {
            var string = JSON.stringify(object) + "";

            var hash = 0, i, chr, len;
            if (string.length == 0) {
                return hash;
            }
            for (i = 0, len = string.length; i < len; i++) {
                chr = string.charCodeAt(i);
                hash = ((hash << 5) - hash) + chr;
                hash |= 0;
            }
            return 'h' + hash;
        }

    });
});
