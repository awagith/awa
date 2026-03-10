/**
 * AWA Motos — B2B Hero Bar Personalizer
 *
 * Reads Magento customerData 'customer' section and switches the bar
 * between guest state (login/register CTAs) and logged-in state
 * (welcome greeting + quick-access links).
 *
 * Usage via data-mage-init on .awa-hero-b2b-bar:
 *   data-mage-init='{"AWA_Custom/js/awa-home-b2b-bar": {}}'
 */
define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'mage/translate'
], function ($, customerData, $t) {
    'use strict';

    return function (config, element) {
        var $bar = $(element);

        function applyState(customer) {
            var isLoggedIn = customer && customer.firstname;

            if (isLoggedIn) {
                var firstName = customer.firstname || '';
                var $guestState  = $bar.find('[data-b2b-bar-guest]');
                var $loggedState = $bar.find('[data-b2b-bar-logged]');
                var $nameSlot    = $bar.find('[data-b2b-bar-name]');

                $nameSlot.text(firstName);
                $guestState.hide();
                $loggedState.show().css('display', 'flex');
            }
        }

        var customerSection = customerData.get('customer');
        applyState(customerSection());
        customerSection.subscribe(function (customer) {
            applyState(customer);
        });
    };
});
