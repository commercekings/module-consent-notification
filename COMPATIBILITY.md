# Compatibility and verification

The first-release runtime target is Magento Open Source 2.4.9 with PHP 8.5 and Luma.
The Composer PHP range is a dependency constraint, not a claim that every Magento/PHP
combination has passed runtime testing.

**Minimum: Magento Open Source 2.4.4**, enforced by `magento/framework: ^103.0.4`. Magento 2.4.0–2.4.3
run only on PHP 7.3/7.4; this package needs PHP 8.1.

| Magento Open Source | PHP | Runtime verification |
| --- | --- | --- |
| 2.4.9 | 8.5 | Passed locally: Composer, PHP lint and package unit tests |
| 2.4.4–2.4.8 | 8.1–8.4 | Unverified; use a matching Magento dependency set and services |

The shared sandbox also runs Notification's and abandoned cart's SQL integration tests and the browser
checkout, reminder, withdrawal and restore checks with this module installed. Its database is MariaDB 11.8.
These checks do not certify other themes, checkout customizations or Adobe Commerce-specific features.

The GitHub workflow checks package metadata, PHP syntax on 8.1–8.5 and Git archive contents.
The workflow does not install Magento or run runtime tests. Latest results are available in
[GitHub Actions](https://github.com/commercekings/module-consent-notification/actions).

For runtime tests, install the source checkout in a compatible Magento development project
and run the suites in `Test/`. Distribution archives omit `Test/`; source checkouts retain it.
Keep package constraints and the verified matrix aligned before each release.

Supported Magento service/version combinations are listed in
[Adobe's system requirements](https://experienceleague.adobe.com/en/docs/commerce-operations/installation-guide/system-requirements).
