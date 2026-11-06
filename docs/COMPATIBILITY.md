
# Compatibility and Release Requirements — Draft

**Checked:** 2026-11-06. These are platform recommendations and candidate test targets, not final plugin header values.

## Evidence and proposed baseline

| Area | Current official guidance | Planning interpretation |
|---|---|---|
| WordPress | Recommends PHP 8.3+, MySQL 8.0+ or MariaDB 10.11+, HTTPS. Older PHP/DB may run but are EOL. | Target current supported WordPress; set plugin minimum at release-policy decision. |
| WooCommerce | Server guidance for 10.8+ lists WP 6.9+, PHP 8.3+, MySQL 8.0+ or MariaDB 10.6+, HTTPS, WP memory 256 MB. | Candidate testing baseline, then verify current stable WC before release. |
| PHP | Current WooCommerce guidance recommends 8.3+. | Test PHP 8.3+ plus newest supported version; test prior supported version if feasible. |
| WPML | WooCommerce compatibility uses WooCommerce Multilingual; custom extension strings/config may need registration. Current compatibility page lists WC 11.2 tested. | Optional, not hard dependency; gettext first and config/registration where needed. |
| Polylang | Developer docs describe pll_register_string for plugin-defined strings; WooCommerce settings translation uses its WooCommerce integration. | Optional, guarded APIs only for merchant-defined strings or language-aware data. |

Recommended, minimum, tested, and compatible versions are not interchangeable. Set Requires at least, Requires PHP, and WC requires at least based on the release support policy, not host recommendations alone.

## Required test matrix

- Current stable WordPress/WooCommerce at release.
- HPOS and legacy order storage; CRUD APIs only.
- Products, variations, partial quantities.
- WPML active/inactive/absent; cross-language canonical reason identity.
- Polylang active/inactive/absent; same stable reason identity.
- WooCommerce Multilingual and Polylang for WooCommerce where relevant.
- Declared PHP/database versions and WordPress privacy exporter/eraser.
- Store timezone, daylight-saving boundaries, multisite decision, RTL UI.
- Existing RMA plugin present/absent/disabled; adapter failure must not block WooCommerce admin.

## Localization rules

Use stable text domain and WordPress translation APIs. Internal reason identity is separate from translated label. A translated label must roll up to the same canonical reason. Define cross-language product grouping through WPML/Polylang APIs only when needed. Keep event locale explicit and support RTL without hard-coded left/right layout.

## Official sources reviewed 2026-11-06

- [WordPress requirements](https://wordpress.org/about/requirements/)
- [WooCommerce server requirements](https://woocommerce.com/document/server-requirements/)
- [WooCommerce PHP/WordPress update guidance](https://woocommerce.com/document/update-php-wordpress/)
- [WPML WooCommerce compatibility](https://wpml.org/plugin/woocommerce-2/)
- [WPML plugin compatibility guidance](https://wpml.org/documentation/support/achieving-wpml-compatibility-for-your-themes-and-plugins/)
- [WPML language configuration files](https://wpml.org/documentation/support/language-configuration-files/)
- [Polylang developer guidance](https://polylang.pro/documentation/support/developers/developpers-how-to/)
- [Polylang WooCommerce settings and strings](https://polylang.pro/documentation/support/guides/translating-woocommerce-urls-and-strings/)

## Release gate

Before release, record exact tested versions and build date. Keep compatibility claims only where CI or documented manual verification exists. Standard translation functions alone do not certify WPML/Polylang compatibility.
