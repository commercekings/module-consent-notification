# Changelog

## Unreleased

- Daily cleanup (`commercekings_consent_notification_stale_cleanup`, 03:05) of subscriptions whose consent was
  withdrawn, expired or deleted by record retention; only stored consent rows decide, never the current configuration.
  A type with its own permission keeps subscriptions without a consent row (their references are no consent ids).
- Requires Magento 2.4.4 or later (`magento/framework ^103.0.4`): Magento 2.4.0–2.4.3 do not run on PHP 8.1.
- Requires a consent banner (`commercekings/consent-source`, provided by `module-consent-vanilla`); sets
  `commercekings_consent_notification/general/enabled`, which features use to show their consent switch only in opt-in.
- `ConsentPermission::isOptOut()` is false: features' notices default to hidden with consent.
- **Consent permission** for Notification subscriptions: a valid `commercekings/module-consent` consent for the
  type's purpose and website, its id stored on the subscription and its SQL guard in every write and send check.
- **Withdrawal** removes the consent's subscriptions, registered for every subscription type's purpose.
- **Subscriber** (`ConsentSubscriber`): the browser the consent was given in, through Consent's public
  `ConsentOwnershipInterface`, and Consent's signed withdrawal link as the unsubscribe link.

No public release has been tagged or published yet.
