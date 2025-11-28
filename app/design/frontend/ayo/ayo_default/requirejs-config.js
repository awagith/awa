var config = {
    deps: [
        'js/lazy-product-images',
        'js/header-a11y',
        'js/brasil-masks',
        'js/form-enhancements'
    ],
    paths: {
        'brasilMasks': 'js/brasil-masks',
        'formEnhancements': 'js/form-enhancements'
    },
    shim: {
        'js/brasil-masks': {
            deps: ['jquery']
        },
        'js/form-enhancements': {
            deps: ['jquery', 'mage/translate']
        }
    }
};
