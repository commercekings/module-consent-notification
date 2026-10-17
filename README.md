# <a href="https://commercekings.net"><picture><source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/commercekings/module-consent-notification/main/.github/commercekings-dark.svg"><img align="right" width="152" src="https://raw.githubusercontent.com/commercekings/module-consent-notification/main/.github/commercekings-light.svg" alt="Built by CommerceKings. Ready to sell more? Talk to us"></picture></a>Consent for notifications

Works with Magento 2.4+ · Luma and Hyvä · PHP 8.1–8.5

[![License](https://badgen.net/github/license/commercekings/module-consent-notification)](LICENSE) [![Release](https://badgen.net/github/release/commercekings/module-consent-notification)](https://github.com/commercekings/module-consent-notification/releases) [![Checks](https://badgen.net/github/checks/commercekings/module-consent-notification/main?label=checks)](https://github.com/commercekings/module-consent-notification/actions)

**Switch every CommerceKings notification to opt-in with one install.**

- **One switch.** Install it and every notification needs a consent first – no code, no settings.
- **Unsubscribe in one click.** Every email gets a signed, scanner-safe unsubscribe link, plus optional RFC 8058
  headers that mail clients such as Gmail can show as a button.
- **Withdrawal removes the subscription.** A shopper says no, and nothing more is sent.
- **Only the right browser.** A restored cart goes back only to the browser that consented.

The bridge between `commercekings/module-consent` and `commercekings/module-notification`. Leave it out and
notifications run opt-out, with no consent tables.

## Installation

```sh
composer require commercekings/privacy-bundle:^0.1
bin/magento setup:upgrade
```

The privacy bundle brings this bridge with our cookie banner. The bridge needs a consent banner – Composer refuses to
install it without one. With your own banner (any package that provides `commercekings/consent-source`):
`composer require commercekings/module-consent-notification:^0.1 vendor/your-banner`.

## What it does

- **Race-safe.** The consent check is part of the same database write as the subscription, so a withdrawal at that
  moment wins; it is checked again at send time.
- **In the storefront**, features subscribe only when the banner allows it; their opt-out notices are off by default
  (the banner informs instead).

It adds no tables.

## More

[Compatibility](COMPATIBILITY.md) · [Changes](CHANGELOG.md)

## Disclaimer

These modules help you ask for, record and respect your shoppers' choices – they don't make your store compliant on
their own. You are responsible for your store's privacy: what you collect, the wording you show, your privacy policy
and whether your setup meets the laws that apply to you (e.g. GDPR, ePrivacy, email marketing rules). Get legal advice
if you are unsure.
