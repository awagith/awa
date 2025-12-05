var config = {
    deps: [
        'js/lazy-product-images',
        'js/header-a11y',
        'js/brasil-masks',
        'js/form-enhancements',
        'js/footer-custom',
        'js/custom/microinteractions'
    ],
    paths: {
        'brasilMasks': 'js/brasil-masks',
        'formEnhancements': 'js/form-enhancements',
        'microinteractions': 'js/custom/microinteractions'
    },
    shim: {
        'js/brasil-masks': {
            deps: ['jquery']
        },
        'js/form-enhancements': {
            deps: ['jquery', 'mage/translate']
        },
        'js/custom/microinteractions': {
            deps: ['jquery']
        }
    }
};
