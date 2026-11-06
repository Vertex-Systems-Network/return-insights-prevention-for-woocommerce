# I-10 Integration and I-11 Release Verification

**Review date:** 2026-11-06  
**I-10 status:** Integration code implemented; WPML/Polylang runtime matrix remains unverified.  
**I-11 status:** Static audit completed for this pass; release/runtime gate remains blocked.

**Latest verification pass:** Re-ran available repository checks on 2026-11-06. Composer JSON/PSR-4 mapping, no-custom-SPL-loader scan, browser-ready JavaScript syntax, whitespace, and merge-marker scans pass. GitHub reports no combined CI statuses for the reviewed commit. These checks do not replace PHP parsing or WordPress/WooCommerce runtime verification.

## I-10: WPML and Polylang

`includes/Multilingual.php` is an optional integration boundary. It has no hard dependency on either product and checks for their public APIs before calling them. Merchant-entered canonical reason labels are registered for string translation in the admin context. REST reason labels, event history, summary reason names, and Pro pattern names use the integration resolver. Canonical reason IDs and stored event data do not change with translated labels. If neither integration is active, the plugin uses its stored locale override or source label.

No RMA/return-management adapter was added: no adapter or source API was approved for this release scope. WooCommerce order and refund access remains through WooCommerce CRUD objects. The source adapter boundary remains optional and does not make another plugin a dependency.

| Case | Source-level behavior | Runtime evidence |
|---|---|---|
| WPML absent | WPML hook checks fail closed; core label fallback remains | Not run |
| WPML active | Registers merchant labels through `wpml_register_single_string`; reads translated value through `wpml_translate_single_string` when available | Not run; requires WPML String Translation |
| Polylang absent | `function_exists` guard leaves core behavior intact | Not run |
| Polylang active | Registers labels through `pll_register_string`; reads through `pll__` | Not run |
| Both plugins present | WPML integration is preferred when its translation filter is registered; otherwise Polylang is used | Not run; simultaneous operation is not a support claim |
| WooCommerce Multilingual / Polylang for WooCommerce | No custom product-translation SQL/API; product and order access stays on WooCommerce CRUD boundary | Not run |

Stable reason IDs are the analytics key; translated text is display-only. Merchants may still provide explicit locale overrides in the reason catalog. Optional multilingual integration does not translate historical event identities or create duplicate reasons.

## I-11: Audit performed

This pass checked PSR-4/bootstrap boundaries, the v1→v3 migration path by source inspection, Pro route entitlement and capability guards, REST input/error paths, WooCommerce CRUD boundaries, bounded scans/storage, multilingual API guards, privacy export/erasure registration, README/backlog claims, and browser-ready JavaScript syntax. It also hardened migration verification, reason-option normalization, report/history query failures, manual event write failures, and privacy data hooks.

| Check | Result | Evidence / limit |
|---|---|---|
| Browser-ready admin JavaScript syntax | Pass | `node --check assets/js/admin.js` |
| Patch whitespace/conflict-marker check | Pass | `git diff --check`; merge-marker search |
| Composer PSR-4 path | Pass | Manifest maps `WPEssential\\ReturnInsights\\` to `includes/` |
| Custom SPL loader | Pass | No `spl_autoload_register`/`spl_autoload_call` in plugin PHP |
| GitHub commit CI status | No statuses reported | Reviewed commit has no combined CI statuses available |
| PHP parse/lint | Blocked | PHP executable unavailable |
| Composer autoloader generation | Blocked | Composer executable unavailable; release vendor files are not generated |
| WordPress/WooCommerce activation, migration, API and CRUD runtime | Blocked | No local PHP/database/WP/WC runtime; no live-site result claimed |
| HPOS and legacy order storage | Not verified | Needs runtime fixtures on both modes |
| WPML/Polylang active/absent cases | Not verified | Adapter source paths reviewed; compatible versions need runtime matrix |
| Visual, keyboard, RTL, contrast and screen-reader review | Not verified | Needs rendered wp-admin with supported WP/WC versions |
| Security/privacy release criteria | Source implementation present; runtime check pending | WordPress privacy exporter/eraser covers user-attributed plugin activity and attaches order insights to WooCommerce's order export/anonymization hooks. Verify execution and failure behavior in runtime. |
| Pro licensing/entitlement | Incomplete | Free core defaults Pro to fail-closed; commercial entitlement provider remains unimplemented |

## Gate decision

I-10 cannot be marked accepted until WPML and Polylang absent/active cases are exercised on supported WordPress/WooCommerce versions. I-11 cannot be marked complete until PHP parsing, generated Composer loader, activation/migration upgrade, API permission and CRUD flows, HPOS/legacy behavior, privacy exporter/eraser runtime behavior, rendered accessibility checks, and release compatibility evidence are recorded. No CLI/build tooling or third-party dependency was added to the repository for this pass.

## Official integration references

- [WPML: Make user-input texts translatable](https://wpml.org/documentation/support/translation-for-texts-by-other-plugins-and-themes/) — register dynamic strings, translate them through String Translation, and re-register on admin loads to support late activation.
- [WPML: Current language hook](https://wpml.org/wpml-hook/wpml_current_language/) — supported language-context hook; the current implementation does not alter query language context.
- [Polylang: Function reference](https://polylang.pro/documentation/support/developers/function-reference/) — guard optional APIs and use `pll_register_string`/`pll__` for dynamic labels.
- [Polylang: Developer how-to](https://polylang.pro/documentation/support/developers/developpers-how-to/) — dynamic string registration and current language/locale APIs.
- [WordPress: Exporter registration](https://developer.wordpress.org/reference/hooks/wp_privacy_personal_data_exporters/) and [eraser registration](https://developer.wordpress.org/reference/hooks/wp_privacy_personal_data_erasers/) — callback contracts used for staff-attributed plugin activity.
- [WooCommerce: `wc_get_orders()` and order queries](https://developer.woocommerce.com/docs/features/orders/wc-get-orders/) — customer orders must be accessed through WooCommerce query/CRUD APIs.
- [WooCommerce privacy exporter code reference](https://woocommerce.github.io/code-reference/files/woocommerce-includes-class-wc-privacy-exporters.html) — order-specific export filter and personal-data field shape.
- [WooCommerce privacy eraser code reference](https://woocommerce.github.io/code-reference/files/woocommerce-includes-class-wc-privacy-erasers.html) — per-order anonymization lifecycle hook used to remove this plugin's order linkage.
