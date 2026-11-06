
# Compatibility and Release Requirements

**Planning baseline selected:** 2026-11-06  
**Release verification:** Not yet performed; this repository contains no runtime plugin code.

## V1 platform requirements

| Requirement | Planned V1 minimum | Basis / caveat |
|---|---|---|
| WordPress | 6.9+ | Current WooCommerce 10.8+ server guidance. |
| WooCommerce | 10.8+ | Planning floor aligned to current server-requirements guidance; recheck current stable before release. |
| PHP | 8.3+ | Current WordPress and WooCommerce recommendations. |
| Database | MySQL 8.0+ or MariaDB 10.6+ | WooCommerce 10.8+ guidance; WordPress recommends MariaDB 10.11+, also supported by this floor. |
| HTTPS | Required for supported production setup | Official WordPress/WooCommerce guidance. |
| WordPress memory | 256 MB+ recommended | WooCommerce host recommendation; not a plugin activation gate. |
| Order storage | HPOS and legacy storage supported | Use WooCommerce CRUD APIs; never assume legacy tables. |
| React admin | WordPress-bundled React/component packages; assets shipped built | Merchants do not need Node/npm/CLI. Build tool is a development concern after P6 approval. |
| WPML | Optional integration target; not a hard dependency | Test latest supported versions at release; no certification claim without evidence. |
| Polylang | Optional integration target; not a hard dependency | Test latest supported versions at release; no certification claim without evidence. |

## Marketplace submission compatibility rule

WooCommerce Marketplace's current submission guidance requires support for the **two latest major releases of both WordPress and WooCommerce**. This moving rule takes precedence over the planning minimums above. At submission time, recalculate the supported/tested matrix, set plugin metadata, and record evidence for each required version. Do not treat the current 6.9+/10.8+ baseline as a guarantee of future Marketplace approval.

## Required compatibility behavior

- Work with WPML active, inactive, or absent; preserve one canonical reason ID across translated labels.
- Work with Polylang active, inactive, or absent; translated labels must not split analytics.
- Where relevant, test WooCommerce Multilingual and Polylang for WooCommerce.
- Support HPOS and legacy storage via WooCommerce public CRUD APIs.
- Handle product variations, partial quantities, and unknown product mapping.
- Respect WordPress locale and RTL layout.
- Test privacy exporter/eraser, store timezone, daylight-saving transitions, and multisite decision.
- If an optional RMA adapter is unavailable or disabled, it must not break WooCommerce admin.

## Localization implementation requirements

Use WordPress translation APIs and one stable text domain for shipped UI. Keep internal reason identity separate from translated display strings. Use WPML config/string registration or Polylang string APIs only for the relevant merchant-defined data; guard optional APIs. Define cross-language product grouping before implementing multilingual rollups.

## Official sources reviewed 2026-11-06

- [WordPress requirements](https://wordpress.org/about/requirements/)
- [WooCommerce server requirements](https://woocommerce.com/document/server-requirements/)
- [WooCommerce PHP/WordPress update guidance](https://woocommerce.com/document/update-php-wordpress/)
- [WPML WooCommerce compatibility](https://wpml.org/plugin/woocommerce-2/)
- [WPML plugin compatibility guidance](https://wpml.org/documentation/support/achieving-wpml-compatibility-for-your-themes-and-plugins/)
- [WPML language configuration files](https://wpml.org/documentation/support/language-configuration-files/)
- [Polylang developer guidance](https://polylang.pro/documentation/support/developers/developpers-how-to/)
- [Polylang WooCommerce settings and strings](https://polylang.pro/documentation/support/guides/translating-woocommerce-urls-and-strings/)
- [Woo Marketplace submission guidelines](https://developer.woocommerce.com/docs/woo-marketplace/submitting-your-product)

## Release verification gate

Before public release, run and document the compatibility matrix across declared PHP, WordPress, WooCommerce, HPOS, WPML, and Polylang cases. Keep a claim only where automated or documented manual verification exists. Standard gettext use alone is not certification.
