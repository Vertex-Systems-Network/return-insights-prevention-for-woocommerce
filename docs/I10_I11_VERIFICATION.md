# I-10 Integration and I-11 Release Verification

**Review date:** 2026-11-07  
**I-10 status:** Approved V1 integration implementation complete; WPML/Polylang runtime matrix is an I-11 verification gate.  
**I-11 status:** Staging smoke checks cover Free read-only screens and fail-closed Pro presentation after the REST query-validation fix; the broader runtime/release gate remains open.

**Latest verification pass:** Re-ran available repository checks on 2026-11-06. Composer JSON/PSR-4 mapping, no-custom-SPL-loader scan, browser-ready JavaScript syntax, whitespace, and merge-marker scans pass. GitHub reports no combined CI statuses for the reviewed commit. These checks do not replace PHP parsing or WordPress/WooCommerce runtime verification.

**Integration correction (2026-11-07):** Corrected reason-label precedence so WPML/Polylang translate the registered source label first, then an explicit merchant locale override wins. Hook detection now distinguishes `false` from a valid priority of `0`. This is source-level correction only; no active WPML/Polylang site was available to test the path.

**Privacy erasure hardening (2026-11-07):** User-attributed rows are now anonymized in bounded batches of up to 100 per plugin table per callback. The eraser verifies whether actor references remain and returns `done=false` to let WordPress resume; database errors are logged without user/order identifiers and reported as retryable. GitHub Actions run `42385970130` passed PHP lint, Composer packaging, and ZIP validation; artifact `13579790771` includes this source. Privacy callback runtime, retries, and large-store behavior remain unverified.

**Refund observer persistence hardening (2026-11-07):** Native refund event lookup, insert, update, and delete-update failures now emit WooCommerce logs with operation names only; no refund/order/customer identifiers are included. CI run `42386157013` passed PHP lint and test ZIP packaging; artifact `13580105362` includes the change. No failure-injection test or live refund mutation was run.

**Authorized staging smoke check (2026-11-07):** On `https://wpessential.org/el-test/`, WordPress 7.1.4 and WooCommerce 11.2.2 were observed. The user installed/activated Return Insights; the admin shell and Reason Catalog loaded. Overview and History first returned `unknown_fields` because strict GET-query validation rejected WordPress apiFetch's `_locale=user` transport parameter. A query-only validator now ignores `_locale` while JSON body validators remain strict. Corrected artifact run `42377569578` passed and was installed over the existing 0.1.0 package. Post-fix Overview returned the selected-period zero counts and empty-period message, History returned the intended no-records empty state, and Capture Sources returned its no-records empty state. Product Trends rendered the intended Pro upgrade gate; Alerts and Corrective Actions remained behind the shared Pro notice while no entitlement provider is configured. Reason Catalog showed the canonical reason list. The capture screen's read-only lookup for existing order #49 also returned one line item and quantity. WooCommerce System Status confirms PHP 8.3.36, HPOS enabled, `OrdersTableDataStore`, and all five Return Insights tables. Its environment type is reported as `production` despite the `/el-test/` URL; subsequent checks remained read-only, and no event was written. This is limited runtime evidence, not full I-11 acceptance. The active-plugin list contained neither WPML nor Polylang; the observed labels therefore verify only the absent-plugin fallback, not active integration behavior.

## I-10: WPML and Polylang

`includes/Multilingual.php` is an optional integration boundary. It has no hard dependency on either product and checks for their public APIs before calling them. Merchant-entered canonical reason labels are registered for string translation in the admin context. REST reason labels, event history, summary reason names, and Pro pattern names use the integration resolver. Canonical reason IDs and stored event data do not change with translated labels. If neither integration is active, the plugin uses its stored locale override or source label.

No RMA/return-management adapter was added: no adapter or source API was approved for this release scope. WooCommerce order and refund access remains through WooCommerce CRUD objects. The source adapter boundary remains optional and does not make another plugin a dependency.

| Case | Source-level behavior | Runtime evidence |
|---|---|---|
| WPML absent | WPML hook checks fail closed; core label fallback remains | Pass, limited: no WPML plugin listed as active; core reason labels loaded in Catalog |
| WPML active | Registers merchant labels through `wpml_register_single_string`; reads translated value through `wpml_translate_single_string` when available | Not run; requires WPML String Translation |
| Polylang absent | `function_exists` guard leaves core behavior intact | Pass, limited: no Polylang plugin listed as active; core reason labels loaded in Catalog |
| Polylang active | Registers labels through `pll_register_string`; reads through `pll__` | Not run |
| Both plugins present | WPML integration is preferred when its translation filter is registered; otherwise Polylang is used | Not run; simultaneous operation is not a support claim |
| WooCommerce Multilingual / Polylang for WooCommerce | No custom product-translation SQL/API; product and order access stays on WooCommerce CRUD boundary | Not run |

Stable reason IDs are the analytics key; translated text is display-only. Merchants may still provide explicit locale overrides in the reason catalog, which take precedence over optional engine translations. Optional multilingual integration does not translate historical event identities or create duplicate reasons.

## I-11: Audit performed

This pass checked PSR-4/bootstrap boundaries, the v1→v3 migration path by source inspection, Pro route entitlement and capability guards, REST input/error paths, WooCommerce CRUD boundaries, bounded scans/storage, multilingual API guards, privacy export/erasure registration, README/backlog claims, and browser-ready JavaScript syntax. Source hardening includes migration verification, reason-option normalization, report/history query failures, manual event write failures, privacy hooks, and WooCommerce privacy-operation error logging without user/order identifiers.

| Check | Result | Evidence / limit |
|---|---|---|
| Browser-ready admin JavaScript syntax | Pass | `node --check assets/js/admin.js` |
| Patch whitespace/conflict-marker check | Pass | `git diff --check`; merge-marker search |
| Composer PSR-4 path | Pass | Manifest maps `WPEssential\\ReturnInsights\\` to `includes/` |
| Custom SPL loader | Pass | No `spl_autoload_register`/`spl_autoload_call` in plugin PHP |
| Build workflow | Pass | Run `42377569578` passed all-PHP lint, Composer PSR-4 autoload generation, package validation, and artifact upload; artifact `13575829765`, SHA-256 `a692cc44d3ec245c948eb97e39c901c836e1f9ec2087fcf18065800f0fc20642` for outer artifact archive. |
| PHP parse/lint | Pass | GitHub Actions run `42375227945` runs `php -l` on every PHP source before packaging; the earlier artifact did not include this gate. |
| Composer autoloader generation | Pass for test artifact | GitHub Actions generated the PSR-4 loader in staging and package validation checked required autoload files; marketplace/release checks remain separate. |
| WordPress/WooCommerce activation + admin shell + schema presence | Pass, limited | Updated package loaded on WP 7.1.4/WC 11.2.2; System Status lists all five plugin tables. Migration contents/upgrade and write CRUD remain unverified. |
| HPOS and legacy order storage | HPOS partial pass; legacy not verified | System Status reports HPOS enabled and OrdersTableDataStore active; read-only order line-item lookup passed. Refund lifecycle/HPOS writes and legacy-mode fixture still needed. |
| WPML/Polylang active/absent cases | Absent fallback pass, active cases unverified | Staging had neither plugin active and canonical labels loaded; active String Translation/Polylang API behavior still needs supported plugin versions |
| Staging REST smoke: Overview, History, Capture Sources, Reason Catalog | Pass after fix | Overview returned selected-period summary/empty state; History/Sources returned intended empty states; Catalog loaded canonical reason rows. No synthetic records were created. |
| Staging Pro UI gates | Partial pass | Product Trends showed the Pro upgrade notice; Alerts and Corrective Actions remained behind the shared Pro notice. The commercial entitlement provider is absent, so Pro REST permission/data behavior is not verified. |
| Read-only WooCommerce order item lookup | Pass, limited | Capture screen lookup of existing staging order #49 returned 1 line item with ordered quantity via WooCommerce CRUD. No capture was saved; WooCommerce System Status later confirmed HPOS active, while legacy-mode behavior remains unverified. |
| Keyboard smoke check | Partial pass | Tab reached skip links and the first plugin tab; Enter opened Overview and its report rendered. Full focus-order review remains. |
| Visual, RTL, contrast and screen-reader review | Not verified | Needs full rendered review across supported WP/WC versions |
| Refund observer database failures | Source and PHP-lint pass; runtime pending | Lookup/insert/update/delete-update failures log safe operation labels only. No live refund write or DB failure injection was run. |
| Privacy eraser batching | Source and PHP-lint pass; runtime pending | Up to 100 matching rows per plugin table per callback; residual references are counted, and WordPress is asked to resume until clear. No live erasure request was executed. |
| Security/privacy release criteria | Source implementation present; runtime check pending | WordPress privacy exporter/eraser covers user-attributed activity and order-linked insights. Order lookup/update database failures now emit a WooCommerce error log entry with no user/order identifiers. Exercise export, erasure, and failure paths in runtime. |
| Pro licensing/entitlement | Incomplete | Free core defaults Pro to fail-closed; commercial entitlement provider remains unimplemented |

## Gate decision

**Staging incidents and verification (2026-11-07):** The earlier fatal at `includes/RestController.php:538` was caused by a stray REST fragment and was removed; all PHP lint checks passed in the build workflow. After the user installed that artifact, the admin shell loaded but overview/history exposed the `_locale=user` allowlist bug. The fix is committed and build run `42377569578` passed. The corrected package was uploaded and WordPress reported `Plugin updated successfully`; Overview, History, and Capture Sources now render their expected empty-data states without plugin errors. A separate `ob_end_flush()` notice still appears from WordPress core `wp-includes/functions.php:5581`; it is unrelated to the plugin route fix. No synthetic data was entered.

I-10 implementation is complete for the approved V1 scope: guarded WPML/Polylang reason-label adapters are implemented and no RMA adapter is in scope without approved demand/API/privacy review. WPML/Polylang absent/active runtime scenarios remain unverified and are tracked in I-11. I-11 cannot be marked complete until PHP parsing, generated Composer loader, upgrade migration, API permission and CRUD flows, HPOS/legacy behavior, privacy exporter/eraser runtime behavior, rendered accessibility checks, and release compatibility evidence are recorded. No CLI/build tooling or third-party dependency was added to the repository for this pass.

## Official integration references

- [WPML: Make user-input texts translatable](https://wpml.org/documentation/support/translation-for-texts-by-other-plugins-and-themes/) — register dynamic strings, translate them through String Translation, and re-register on admin loads to support late activation.
- [WPML: Current language hook](https://wpml.org/wpml-hook/wpml_current_language/) — supported language-context hook; the current implementation does not alter query language context.
- [Polylang: Function reference](https://polylang.pro/documentation/support/developers/function-reference/) — guard optional APIs and use `pll_register_string`/`pll__` for dynamic labels.
- [Polylang: Developer how-to](https://polylang.pro/documentation/support/developers/developpers-how-to/) — dynamic string registration and current language/locale APIs.
- [WordPress: Exporter registration](https://developer.wordpress.org/reference/hooks/wp_privacy_personal_data_exporters/) and [eraser registration](https://developer.wordpress.org/reference/hooks/wp_privacy_personal_data_erasers/) — callback contracts used for staff-attributed plugin activity.
- [WooCommerce: `wc_get_orders()` and order queries](https://developer.woocommerce.com/docs/features/orders/wc-get-orders/) — customer orders must be accessed through WooCommerce query/CRUD APIs.
- [WooCommerce privacy exporter code reference](https://woocommerce.github.io/code-reference/files/woocommerce-includes-class-wc-privacy-exporters.html) — order-specific export filter and personal-data field shape.
- [WooCommerce privacy eraser code reference](https://woocommerce.github.io/code-reference/files/woocommerce-includes-class-wc-privacy-erasers.html) — per-order anonymization lifecycle hook used to remove this plugin's order linkage.


## 2026-11-07 post-fix staging smoke details

- Installed package: `wpessential-return-insights-prevention-woocommerce-0.1.0.zip` (artifact `13575829765`, workflow run `42377569578`). ZIP SHA-256 verified locally: `6bbfc01bb5683513e4f35adfe797d7d1330e56674e583603467a756098ffc915`.
- WordPress confirmed the existing plugin replacement completed successfully; the Return Insights page remained reachable.
- Overview: request completed; 0 active records, 0 quantity, mapping 0/0, expected empty-period message.
- History: request completed; expected no-insights empty state.
- Capture Sources: request completed; expected no-records empty state.
- Reason Catalog: loaded canonical reason rows. No writes or artificial records were created.
- Not exercised: creating an insight against a real order, refund ingestion, Pro endpoints/entitlement, HPOS vs legacy fixtures, permissions, WPML/Polylang, privacy export/erasure, accessibility, and release submission checks.


**Additional read-only UI check (2026-11-07):** Reason Catalog loaded canonical labels and stable IDs after package update. Product Trends displayed its Pro upsell; Alerts and Corrective Actions showed the shared Pro gate. No records were written. These observations do not verify server-side permissions or the absent production entitlement provider.


**Capture lookup (2026-11-07):** The admin entered existing staging order ID 49 and pressed **Find order items**. The UI returned a single line item (`Hat`, quantity 1), demonstrating the read-only order-item route against this runtime. The merchant did not save a return record. This does not verify the manual capture POST, idempotency, database writes, or HPOS mode.


**Runtime environment note (2026-11-07):** Although the URL is under `/el-test/`, WooCommerce System Status reports environment type `production`. Treat this as a potentially production-marked site; the checks after observing this stayed read-only. Confirm the host's environment designation before running write, refund, privacy-erasure, or storage-mode switch tests.


**Keyboard smoke check (2026-11-07):** With the staging admin loaded, Tab moved through the WordPress skip-to-main/toolbar links. Activating skip-to-main placed focus on the plugin Overview button; pressing Enter opened the Overview and its REST data rendered. This does not constitute a full accessibility audit.


**Optional integration absent-mode check (2026-11-07):** WooCommerce System Status lists active plugins and contained neither WPML nor Polylang. Return Insights Reason Catalog rendered canonical labels successfully, confirming a limited no-integration fallback path. No active WPML String Translation or Polylang runtime is available on this site.
