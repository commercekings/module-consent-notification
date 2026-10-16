# CommerceKings_ConsentNotification

The bridge between `commercekings/module-consent` and `commercekings/module-notification`. Install it when
notifications (e.g. abandoned cart reminders) must need a consent; leave it out for opt-out notifications – then no
consent tables exist.

```sh
composer require commercekings/module-consent-notification:^0.1
bin/magento setup:upgrade
```

## What it does

- **Subscribing needs a consent** for the notification's purpose, on the cart's website. The consent id is stored
  on the subscription, and the consent check is part of the same database write, so a withdrawal at that moment wins.
- **Withdrawing removes subscriptions** of every notification type – a feature registers only its type.
- **Only the consenting browser is the subscriber**, so a feature (e.g. the cart restore link) hands data back only
  there.
- **Rechecks at send time** and gives the email Consent's signed unsubscribe link (confirmation page and
  one-click).
- **In the storefront**, features subscribe only when the visitor's banner choice allows it, and their opt-out
  notices are off by default (the banner informs instead).

No tables, no settings. Without it, Notification and its features run opt-out.

## More

[Compatibility](COMPATIBILITY.md) · [Changes](CHANGELOG.md)
