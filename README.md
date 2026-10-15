# CommerceKings_ConsentNotification

Connects `commercekings/module-consent` to `commercekings/module-notification`. Install it for
**consent-based notifications** (setup 1: a consent banner, a recorded consent per subscription). Leave it out for
**opt-out notifications** (setup 2): Notification then allows every subscription until it is removed, and no
consent tables exist.

```bash
composer require commercekings/module-consent-notification
bin/magento module:enable CommerceKings_ConsentNotification
bin/magento setup:upgrade
```

## What it does

- **Subscribing needs a consent.** Replaces Notification's `SubscriptionPermissionInterface` with
  `Model\ConsentPermission`: a subscription needs a valid consent (`ConsentInterface::getCurrent()`) for its type's
  purpose on the target's website. The consent id is stored on the subscription, and Consent's SQL guard is part of
  the subscribe INSERT and of every send-time check, so a withdrawal at the same moment wins.
  - no consent → a guest's subscription to a single type is removed (as with an empty email);
  - a consent of another website or purpose → refused, nothing changes.
- **Withdrawal removes subscriptions.** `Plugin\WithdrawalPoolPlugin` registers `Model\WithdrawalHandler` (name
  `notification_subscriptions`) for the purpose of every Notification subscription type, so a feature module
  registers only its subscription type. A handler registered for the same purpose and name in `di.xml` wins.
- **Who subscribed.** The visitor is identified by the browser the consent was given in (Consent's browser id
  cookie against the consent record), so features may hand a subscriber's data back to that browser only – e.g.
  abandoned cart's restore link.

It adds no tables and no settings.

## Tests

`Test/Unit` covers the permission (granted, absent, refused, SQL condition, browser identification) and the
withdrawal registration. Install the source checkout in a Magento development project and run them with PHPUnit;
the CommerceKings sandbox runs them with every package.
