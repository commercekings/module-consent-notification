# Changelog

## Unreleased

First release. Features:

- **Consent permission** for Notification subscriptions: a valid `commercekings/module-consent` consent for the
  type's purpose and website, its id stored on the subscription and its SQL guard in every write and send check.
- **Withdrawal** removes the consent's subscriptions, registered for every subscription type's purpose.
- **Subscriber** (`ConsentSubscriber`): the browser the consent was given in, through Consent's public
  `ConsentOwnershipInterface`, and Consent's signed withdrawal link as the unsubscribe link.

No public release has been tagged or published yet.
