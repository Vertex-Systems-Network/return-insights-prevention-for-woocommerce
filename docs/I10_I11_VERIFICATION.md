# I-10 Integration and I-11 Release Verification

**Review date:** 2026-11-09  
**I-10 status:** Approved V1 integration implementation complete; WPML/Polylang runtime matrix is an I-11 verification gate.  
**I-11 status:** Limited runtime smoke checks are evidenced on `wpessential.org/el-test`; the separate TasteWP site `quirkybrake.s2-tastewp.com` has WooCommerce active but Return Insights installation was blocked before completion. Runtime/release gate remains open; no overall percentage is claimed here. The README reports gate status without an aggregate percentage.

**Latest verification pass:** On 2026-11-09, a source audit hardened manual-event database read paths: failed idempotency/row lookups now return controlled errors, quantity-limit aggregation fails closed when its query errors, and create/update verify their persisted row before serialization. GitHub Actions run [42637830397](https://github.com/Vertex-Systems-Network/wpessential-return-insights-prevention-woocommerce/actions/runs/42637830397) passed PHP 8.3 lint, Composer PSR-4 packaging, and ZIP validation; artifact `13683022555` was uploaded (SHA-256 `0801dea18585d7ff19d8199bff45e17f8061351b5754550f8226378ce146ea0b`, expires 2026-11-23). This is CI/package evidence only and does not close I-11 runtime gates.

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


## 2026-11-08 edit-flow staging verification

- A staging save exposed a PATCH body mismatch: the React edit handler sent immutable `order_id` and `order_item_id` fields that the REST update schema correctly rejects. The client now sends only `event_kind`, `quantity`, `reason_id`, and `occurred_at` for PATCH.
- GitHub Actions run `42434063755` passed PHP lint and test ZIP packaging. Artifact `13598489437` (outer ZIP SHA-256 `3e658121233d99705ba84b9ba5ddfc1b0badb5d0b3205e1cb67e9d0d6b098396`; inner plugin ZIP SHA-256 `bc93479bdbc36e8f4b3abd7f23b9d81cb66de6caad4f21644eca726a7ca8d4ff`) was installed over the existing 0.1.0 package; WordPress displayed “Plugin updated successfully.”
- On the updated package, manual capture for existing order #49/item #1 (`Hat`, ordered quantity 1) succeeded. Editing its reason from `Other` to `Changed mind` succeeded and persisted in History, confirming the PATCH route fix. The record was then soft-deleted with a test audit note. History showed “No return insights yet.” No WooCommerce order, refund, or customer record was modified.
- This is a narrow staging smoke check, not full CRUD/security/release acceptance. It does not verify authorization boundaries, DB failures, migration upgrades, refund observer lifecycle, legacy order storage, active WPML/Polylang, privacy callbacks, Pro entitlement, visual/accessibility coverage, or marketplace readiness. The unrelated WordPress core `ob_end_flush()` notice remains visible.


**Quantity upper-bound UI check (2026-11-08):** On the capture form for order #49/item #1 (`Hat`, ordered quantity 1), entering quantity 2 triggered the browser's native constraint message, “Value must be less than or equal to 1.” The request was not submitted; History remained empty. Source inspection confirms the REST validator independently checks previously recorded quantity plus the proposed amount against the ordered quantity, but a direct malformed-request runtime test was not performed. This verifies only the form constraint and does not close API authorization or server-boundary testing.


## 2026-11-08 order lookup regression verification

- Fixed a stale-context window in the React capture flow. Editing the order ID clears the previously loaded order and selected item immediately; lookup also clears context before the request begins. Product, reason, type, quantity, date, and Save controls stay disabled while lookup is in progress.
- GitHub Actions run [42439188546](https://github.com/Vertex-Systems-Network/wpessential-return-insights-prevention-woocommerce/actions/runs/42439188546) completed successfully; artifact `13600439437` was installed over the existing plugin. WordPress displayed “Plugin updated successfully.”
- On staging, invalid order ID `999999999` returned “Order not found,” no previous Hat item remained selectable, and Save was disabled. A subsequent lookup of order `49` returned one Hat line item with ordered quantity 1 and enabled the form. History remained at “No return insights yet.” No insight was saved, and no WooCommerce order or refund was changed.
- The admin page also displays two WordPress core `ob_end_flush()` notices from `wp-includes/functions.php:5581`; this is outside the plugin source and remains an environment issue to investigate separately.
- I-11 is now 70%. This closes only a narrow client-side stale-state regression; it does not prove direct REST authorization, database failure handling, refund observer lifecycle, legacy order storage, privacy callbacks, active WPML/Polylang, Pro entitlement, full accessibility, or marketplace release readiness.


## 2026-11-09 TasteWP runtime preparation

- Site: `https://quirkybrake.s2-tastewp.com` (credentials were supplied by the user; not stored in this report).
- Signed-in wp-admin dashboard showed WordPress 7.1.4. The initial Installed Plugins screen showed no plugins. The official WordPress Plugin Directory listing offered WooCommerce 11.2.2. It installed; the activation redirect rendered a WooCommerce `_load_textdomain_just_in_time` notice and an error-style screen, but a fresh Installed Plugins page subsequently showed WooCommerce 11.2.2 active and the WooCommerce admin menu present.
- The current Return Insights build from artifact `13600439437` was selected in the WordPress plugin ZIP upload form. The browser security policy blocked clicking the final **Install Now** button, so installation and plugin runtime checks on this site are **not verified**. No workaround or alternate upload path was attempted.
- No orders, products, refunds, customer records, or plugin insight rows were created or modified. I-11 stays open; WooCommerce environment preparation alone is not a plugin runtime pass.

## 2026-11-09 source audit follow-up

Manual event persistence and validation were hardened after a read-through of the current main-branch source. The idempotency lookup now returns a controlled 500 response on database errors instead of proceeding as if the record were absent. `validate_event()` now returns a controlled error if the quantity sum cannot be read; it no longer treats a failed query as zero. `find_event()` distinguishes database errors from a legitimate missing event, and create/update verify the saved row before serializing it. This is source-level verification only. It does not close concurrency/race testing, hostile REST request tests, or database failure injection.

The local workspace checkout at `/workspace/scratch/15c66e332e50/plugin-src` is older than the GitHub `main` source and has uncommitted edits; it was not used to publish this change or to build an artifact. Continue using GitHub `main` as the source of truth and reconcile that working copy before any local release build.


## 2026-11-10 execution protocol correction

- Added `docs/EXECUTION_PROTOCOL.md` as the authoritative contributor workflow: atomic acceptance items, explicit evidence classes, status vocabulary, blocker handling, and closure gates for I-11/I-12.
- Updated `AGENTS.md` and the reusable AI task prompt in `docs/AI_BACKLOG.md`: a “continue” message now directs the agent to execute the next unblocked acceptance item in the same work session; a status-only response is insufficient while work can proceed.
- Corrected the latest build record: run `42637830397` passed PHP lint and produced artifact `13683022555`. The artifact is not staging-install evidence.
- Environment records are separate: earlier limited runtime checks were performed on `wpessential.org/el-test`; on TasteWP, the final installation action was blocked before plugin installation. No alternate route was attempted and no site data changed.
- I-11 remains open for the explicit gates above; no percentage in this evidence log is treated as completion proof. Next unblocked task: reconcile README and backlog dashboards to this ledger, then continue the highest-priority release check permitted by the documented environment constraints.


## 2026-11-10 privacy-export failure-path audit

- **Finding:** `Privacy::export_user_data()` previously converted a failed database read into a synthetic “Export status” data row and returned `done=true`. That could make an incomplete export look successful and place an operational error message in the exported personal-data report.
- **Fix:** The callback now logs a generic operation label and returns `WP_Error` on query failure. It no longer marks failed output complete or inserts synthetic data. WordPress core's AJAX exporter explicitly recognizes `WP_Error` and sends an error response: [export AJAX handler](https://developer.wordpress.org/reference/functions/wp_ajax_wp_privacy_export_personal_data/) (reviewed 2026-11-10).
- **CI evidence:** The first commit's run [42703690672](https://github.com/Vertex-Systems-Network/wpessential-return-insights-prevention-woocommerce/actions/runs/42703690672) caught an invalid namespace escape during PHP lint; the immediately following commit corrected it. Follow-up run [42703752886](https://github.com/Vertex-Systems-Network/wpessential-return-insights-prevention-woocommerce/actions/runs/42703752886) completed successfully. PHP 8.3 lint, Composer PSR-4 package generation, ZIP build, and artifact upload all passed. Artifact `13712606784`, GitHub artifact archive digest `sha256:48cbef0c3726d26e02385bcf38b2b8bcb005e061b7be4be32c7ba90dfdd9865f`, expires 2026-11-23.
- **Limit:** This is source and CI verification only. No database failure injection or WordPress privacy export request was run. The privacy-export runtime acceptance item remains open.


## 2026-11-10 REST permission callback source audit

Compared every registered route in `includes/RestController.php` with the approved capability policy in `docs/P6_TECHNICAL_CONTRACTS.md`.

| Route group | Expected and observed callback | Static result | Runtime result |
|---|---|---|---|
| Reasons catalog and individual reasons | GET: `can_view`; POST/PATCH: `can_manage` | Pass | Role matrix pending |
| Order-item lookup | GET: `can_manage` | Pass | Unauthorized-role test pending |
| Summary, sources, event list/detail | GET: `can_view` | Pass | Unauthorized-role test pending |
| Manual event create/edit/soft-delete | POST/PATCH/DELETE: `can_manage` | Pass | REST permission test pending |
| Patterns, clusters, alert list | `can_view_patterns`: view capability plus fail-closed Pro entitlement | Pass | Pro entitlement/runtime test pending |
| Cluster override | `can_manage_clusters`: manage capability plus Pro entitlement | Pass | Runtime test pending |
| Alert mutation | `can_manage_alerts`: manage capability plus Pro entitlement | Pass | Runtime test pending |
| Corrective-action reads | `can_view_actions`: view capability plus Pro entitlement | Pass | Runtime test pending |
| Corrective-action writes/evidence | `can_manage_actions`: manage capability plus Pro entitlement | Pass | Runtime test pending |

Every registered route inspected has an explicit permission callback; the read/write split and Pro gates match the approved contract. This is source-level evidence only. I did not send unauthorized requests or alter role capabilities on either site. Remaining executable acceptance cases are: anonymous/no-capability denial, read-only capability access, manager mutation access, object/order lookup access, Pro-disabled denial, and Pro-entitled access. Run these on a clearly non-production test site when the package can be installed through an allowed flow.
