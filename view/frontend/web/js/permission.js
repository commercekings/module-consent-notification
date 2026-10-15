/** Notification's storefront permission (CommerceKings_Notification/js/permission) as the visitor's consent choice. */
define(['CommerceKings_Consent/js/consent/provider'], function (consent) {
    'use strict';

    return {
        hasPermission: function (purpose) {
            return consent.hasConsent(purpose);
        },

        onChange: function (purpose, callback) {
            consent.onChange(purpose, callback);
        }
    };
});
