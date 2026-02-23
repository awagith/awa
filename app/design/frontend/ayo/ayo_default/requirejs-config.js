var config = {
    /* awa/fixes path REMOVED — awa-fixes.js is deprecated.
       All fixes consolidated into awa-master-fix.js (loaded via awa-js-loader.phtml). */
    paths: {
        'rokanthemes/theme': 'js/theme'
    },
    config: {
        mixins: {
            'Magento_Search/js/form-mini': {
                'Magento_Search/js/quicksearch-a11y-mixin': true
            }
        }
    }
};
