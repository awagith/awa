/**
 * RequireJS Configuration
 * Registra os módulos JavaScript customizados
 */
var config = {
    map: {
        '*': {
            'stickyAddToCart': 'js/sticky-addtocart',
            'mobileBottomNav': 'js/mobile-bottom-nav',
            'mobileUxEnhancements': 'js/mobile-ux-enhancements',
            'newsletterPopup': 'js/newsletter-popup'
        }
    },
    config: {
        mixins: {
            'Magento_Catalog/js/catalog-add-to-cart': {
                'js/sticky-addtocart-mixin': true
            }
        }
    }
};
