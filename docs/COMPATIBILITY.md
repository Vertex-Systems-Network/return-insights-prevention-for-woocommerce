# Compatibility and Release Requirements

**Planning baseline selected:** 2026-11-06  
**Release verification:** Not performed; this repository contains no runtime plugin code.  
**Baseline policy:** Prefer the current secure WordPress/WooCommerce server recommendations. For Marketplace readiness, target the two latest major release series and refresh the matrix at every submission. The dated series below are planning targets, not test results or a future approval guarantee.

## V1 platform requirements

| Requirement | Planned V1 baseline | Basis / caveat |
|---|---|---|
| WordPress | 7.0+ planning floor; validate on 7.0 and 7.1 series | WordPress.org listed 7.1.4 as latest on 2026-11-06. Marketplace requires two latest major releases; re-evaluate at release. |
| WooCommerce | 11.1+ planning floor; validate on 11.1 and 11.2 series | WooCommerce 11.2.2 was stable on 2026-11-06; latest two series at review were 11.2 and 11.1. |
| PHP | 8.3+ | Woo host guidance recommends 8.3+; stricter product choice than Woo Marketplace’s PHP 7.4 minimum. Verify all supported PHP versions when implementation begins and at release. |
| Database | MySQL 8.0+ or MariaDB 10.11+ | Meets WordPress.org recommendation; WooCommerce 10.8+ server guidance allows MariaDB 10.6+, but product chooses the stricter WordPress recommendation. |
| HTTPS | Required for supported production setup | Official WordPress/WooCommerce guidance. |
| WordPress memory | 256 MB+ recommended | WooCommerce host recommendation; not a plugin activation gate. |
| Order storage | HPOS and legacy storage supported | Use WooCommerce CRUD APIs; never assume legacy tables. |
| React admin | WordPress-bundled React/component packages; shipped built assets | Merchants need no Node/npm/CLI. Build-tool selection is a P6 decision. |
| WPML | Optional integration target; not a hard dependency | Test exact supported version with WooCommerce Multilingual at release; no certification claim without evidence. |
| Polylang | Optional integration target; not a hard dependency | Test exact supported version and relevant WooCommerce integration at release; no certification claim without evidence. |

**Important distinction:** WordPress/WooCommerce server recommendations describe the hosting environment, while this table sets the plugin’s planned support floor. A floor does not replace a version-by-version test matrix.

## Rolling Marketplace support policy

Woo Marketplace submission guidance currently requires compatibility with the two latest major releases of both WordPress and WooCommerce. At this review date, the planning matrix is WordPress 7.1 and 7.0 series, and WooCommerce 11.2 and 11.1 series. Use the latest patch of each series at implementation/release review, record test evidence, and update plugin metadata accordingly. This moving policy takes precedence over the dated planning floor. See the official submission guide for current requirements.

Woo Marketplace currently lists PHP 7.4+ as its minimum and strongly recommends PHP 8.3+. This product deliberately plans PHP 8.3+; it does not claim to support PHP 7.4. WordPress/WooCommerce release requirements can change, so reconcile the product floor before coding and release.

## Required compatibility behavior

- Work with WPML active, inactive, or absent; preserve one canonical reason ID across translated labels.
- Work with Polylang active, inactive, or absent; translated labels must not split analytics.
- Where relevant, test WooCommerce Multilingual and Polylang’s WooCommerce integration.
- Support HPOS and legacy storage through WooCommerce public CRUD APIs.
- Handle product variations, partial quantities, deleted products, and unknown product mapping.
- Respect WordPress locale and RTL layout.
- Test privacy exporter/eraser, store timezone, daylight-saving transitions, and the multisite decision before release.
- If an optional RMA adapter is unavailable or disabled, it must not break WooCommerce admin.
- State the exact plugin/platform versions tested; do not infer compatibility from activation alone.

## Localization implementation requirements

Use WordPress translation APIs and one stable text domain for shipped UI. Keep internal reason identity separate from translated display strings. Use WPML config/string registration or Polylang string APIs only for relevant merchant-defined data; guard optional APIs. Define cross-language product grouping before implementing multilingual rollups.

## Official sources reviewed 2026-11-06

- [WordPress requirements](https://wordpress.org/about/requirements/) — PHP 8.3+, MySQL 8.0+ or MariaDB 10.11+, HTTPS.
- [WordPress release archive](https://wordpress.org/download/releases/) — 7.1.4 latest listed at review.
- [WooCommerce server recommendations](https://woocommerce.com/document/server-requirements/) — WooCommerce 10.8+ server guidance: WordPress 6.9+, PHP 8.3+, MySQL 8.0+ or MariaDB 10.6+, HTTPS, 256 MB.
- [WooCommerce release archive](https://developer.woocommerce.com/releases/) — 11.2.2 stable on 2026-11-04; 11.2 and 11.1 current series at review.
- [Woo Marketplace submission guidelines](https://developer.woocommerce.com/docs/woo-marketplace/submitting-your-product) — two latest major WordPress/WooCommerce releases and PHP policy.
- [WooCommerce PHP/WordPress update guidance](https://woocommerce.com/document/update-php-wordpress/)
- [WPML WooCommerce compatibility](https://wpml.org/plugin/woocommerce-2/)
- [WPML plugin compatibility guidance](https://wpml.org/documentation/support/achieving-wpml-compatibility-for-your-themes-and-plugins/)
- [WPML language configuration files](https://wpml.org/documentation/support/language-configuration-files/)
- [Polylang developer guidance](https://polylang.pro/documentation/support/developers/developpers-how-to/)
- [Polylang WooCommerce settings and strings](https://polylang.pro/documentation/support/guides/translating-woocommerce-urls-and-strings/)

## Release verification gate

Before public release, document a compatibility matrix across declared PHP, WordPress, WooCommerce, HPOS/legacy storage, WPML, and Polylang cases. Keep a compatibility claim only where automated or documented manual verification exists. Standard gettext use or a successful activation alone is not certification.
