# Compatibility and Release Requirements

**Planning baseline selected:** 2026-11-06  
**Scope revised:** 2026-11-11  
**Release verification:** Not performed. This document defines planned support, not test results or marketplace approval.

## V1 platform requirements

| Requirement | Planned V1 baseline | Basis / caveat |
|---|---|---|
| WordPress | 7.0+ planning floor; validate on 7.0 and 7.1 series | WordPress.org listed 7.1.4 as latest on 2026-11-06. Refresh current major series at release. |
| WooCommerce | 11.1+ planning floor; validate on 11.1 and 11.2 series | WooCommerce 11.2.2 was stable on 2026-11-06. Refresh current major series at release. |
| PHP | 8.3+ | Verify every declared supported version at implementation/release review. |
| Database | MySQL 8.0+ or MariaDB 10.11+ | Selected to align with WordPress.org server recommendations. |
| HTTPS | Required for supported production setup | WordPress/WooCommerce guidance. |
| WordPress memory | 256 MB+ recommended | Hosting recommendation, not plugin activation gate. |
| Order storage | HPOS and legacy storage supported | Use WooCommerce CRUD APIs; never assume legacy tables. |
| Admin localization | WordPress gettext APIs and stable text domain | Respect locale and RTL layout; no vendor-specific translation integration. |
| React admin | WordPress-bundled wp.element, wp.components, wp.apiFetch; browser-ready JavaScript | No JSX/transpiler/npm/CLI/build pipeline under current approved constraint. |

## Explicit V1 integration boundary

V1 does not claim dedicated compatibility, integration, or runtime certification for WPML, WooCommerce Multilingual (WCML), or Polylang. These products and their WooCommerce extensions are not required dependencies and are excluded from the release test matrix. Standard WordPress gettext localization remains in scope.

Existing multilingual adapter source may remain in the repository, but its presence is not a supported integration promise and does not create a release gate. Any future adapter proposal requires a separately recorded scope decision, public-API review, demand/privacy assessment, and its own acceptance matrix.

## Rolling Marketplace support policy

Woo Marketplace submission guidance requires compatibility with the two latest major release series of WordPress and WooCommerce. At the 2026-11-06 review, the planning matrix was WordPress 7.1/7.0 and WooCommerce 11.2/11.1. Refresh these targets before implementation/release, use the latest patch of each declared series, record test evidence, and update plugin metadata.

Product target is PHP 8.3+. Marketplace and platform requirements can change; reconcile before coding and submission.

## Required compatibility behavior

- Support HPOS and legacy order storage through WooCommerce public CRUD APIs.
- Handle variations, partial quantities, deleted products, and unknown product mapping.
- Respect WordPress locale, gettext conventions, and RTL layout.
- Test privacy exporter/eraser behavior, timezone, and daylight-saving transitions.
- V1 supports per-site activation; network-wide activation is out of scope.
- Third-party RMA adapters are not approved for V1; their absence must not break WooCommerce admin.
- State exact PHP, WordPress, and WooCommerce versions tested; do not infer compatibility from activation alone.

## Official sources reviewed 2026-11-06

- [WordPress requirements](https://wordpress.org/about/requirements/)
- [WordPress release archive](https://wordpress.org/download/releases/)
- [WooCommerce server recommendations](https://woocommerce.com/document/server-requirements/)
- [WooCommerce release archive](https://developer.woocommerce.com/releases/)
- [Woo Marketplace submission guidelines](https://developer.woocommerce.com/docs/woo-marketplace/submitting-your-product)
- [WooCommerce PHP/WordPress update guidance](https://woocommerce.com/document/update-php-wordpress/)

## Release verification gate

Before public release, document a compatibility matrix across declared PHP, WordPress, WooCommerce, and HPOS/legacy cases. Include standard gettext and RTL behavior in general WordPress admin UI checks. Do not include WPML, WCML, or Polylang-specific cases unless a future approved scope change adds them. Keep compatibility claims only where test evidence exists.
