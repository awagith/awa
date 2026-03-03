define([
    'jquery',
    'knockout',
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/grid/provider'
], function ($, ko, _, Registry, Provider) {
    return Provider.extend({
        defaults: {
            storageConfig: {
                component: 'Mirasvit_Search/js/preview/data-storage'
            }
        },

        reload: function (options) {
            var source = Registry.get('search_scorerule_form.search_scorerule_form_data_source');
            this.params.scoreRule = source.data;
            return this._super(options);
        }
    })
});