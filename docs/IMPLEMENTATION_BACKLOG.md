# Implementation Backlog — Approved, Ordered Work

**Status:** I-00/I-01 design packages and I-02 foundation, I-03 catalog/manual-capture API, I-04 refund observation, I-05 Free reports, I-06 REST/React admin, and I-07 Pro patterns, I-08 clustering/alerts, and I-09 corrective-action tracking implementations are present. I-10 approved V1 integration implementation is complete; its WPML/Polylang runtime verification is tracked in I-11, along with release verification and the production Pro entitlement provider.
**Start condition:** P6 approval recorded in `P6_READINESS_GATE.md` on 2026-11-06.  
**Repository constraint:** Do not add CLI/build tooling to this repository until the plugin is final. The user authorized `composer.json` only for the PSR-4 autoload mapping; add no external dependencies and run no Composer command before final packaging. React uses WordPress-bundled globals without JSX or a build step.

Each runtime item must be scoped with named files, acceptance evidence, dependencies, and a README progress update before work begins.

## Sequence

| ID | Work package | Main scope | Depends on | Exit evidence |
|---|---|---|---|---|
| I-00 | Lock detailed technical decisions | Final event taxonomy, rate cohort/maturity rules, thresholds, retention/export fields, package model, REST schemas/permissions | P6 approval | Complete: approved decisions recorded consistently across P6, product, data, UX, privacy, compatibility specs |
| I-01 | Security and privacy design | Threat model, capabilities/roles, REST permission matrix, nonce/auth assumptions, validation, minimization, export/erase, retention/uninstall | I-00 | Complete: threat/privacy controls in `P6_TECHNICAL_CONTRACTS.md`; verification remains for implementation |
| I-02 | Plugin foundation | Naming/slug/constants, activation/deactivation behavior, PHP/WP/WC dependency checks, role capabilities, translation domain, WooCommerce admin entry point, safe uninstall capability cleanup | I-00, I-01 | Foundation files use Composer PSR-4 mapping (`WPEssential\ReturnInsights\` → `/includes/`) with case-matched class/file names. Plugin bootstrap and uninstall directly require `vendor/autoload.php`; no custom SPL loader. No third-party dependencies; Composer generation is reserved for final package preparation. Activation/upgrade now records schema v3 only after all five tables and required columns verify; failed upgrades prevent feature services from loading and show a capability-gated admin notice. Static inspection complete; vendor generation and runtime class-load, activation/dependency/role/migration checks remain for I-11. |
| I-03 | Reason catalog and manual capture | Stable reason keys, translated labels, first versioned event-table migration, per-item merchant capture, validation/audit/provenance | I-02 | Implemented: versioned event table/dbDelta migration, canonical reason IDs and editable label translations, per-item Woo order lookup through CRUD API, manual confirmed-return/exchange POST with idempotency, protected event listing/get/edit/soft-delete, and server-derived product/variation IDs. Malformed stored catalog values and non-scalar translation labels are handled safely; database failures in reports/history and manual-event update/delete now return explicit REST errors. Static source inspection only; runtime permission, migration, and quantity-edge verification remains for I-11. |
| I-04 | Native refund observation | Supported WooCommerce refund hooks/objects, idempotent ingestion, refund-scope attribution, HPOS + legacy behavior | I-02, I-03 | Implemented: post-save `woocommerce_refund_created` observer reads WooCommerce refund/order objects through CRUD APIs, stores one refund-scope `refund_only` event per refund with a unique source key, maps only exact active catalog labels without persisting raw phrase text, and soft-deletes the linked event when the refund is deleted. Source code inspected; HPOS/legacy and lifecycle runtime checks remain for I-11. |
| I-06 | REST contract and React admin | Permission-protected endpoints, pagination/errors, overview/history/capture/catalog/source screens | I-00, I-01, WordPress-bundled script handles | Implemented: REST schemas/unknown-field rejection; permission-aware no-build React screen; source counts/latest dates; paginated/filterable history; per-item capture/edit/audited soft-delete; reason catalog. Static source review only; runtime and visual/accessibility verification is I-11. |
| I-05 | Basic reporting (Free) | Counts, date filters, reason/source breakdown, item-scoped product/category counts, coverage, empty/low-data states | I-03, I-04, I-06 | Implemented: `GET /summary` provides store-timezone date filtering, event and quantity totals, reason/event-kind/source counts, mapped/unmapped coverage, top 10 item-scoped products/categories, and period metadata. React overview renders date filters, tables, loading/empty/error states. No return rates or causality claims. Static source review only; I-11 runtime, visual, accessibility, and performance checks remain. |
| I-07 | Pro product/variation insights | Aggregation, eligible denominators, comparable cohorts, sample-aware pattern output | I-04, I-05 | Implemented: `GET /patterns` compares mature cohorts and exact item/reason matches. Completed/refunded order line-item quantities with completion dates provide an MVP proxy; refund-only and exchange events are excluded. Each scan is capped at 500 orders. Incomplete mapping, fewer than 20 sold units, missing denominator, or returns above sales suppress rates. Entitlement defaults fail-closed; provider and runtime verification remain. |
| I-08 | Pro clustering and alerts | Deterministic local clusters of reason patterns; merchant reversible overrides; Pro in-product alert lifecycle; weekly persistence; deduplication, dismissal, audit, and visible evidence | I-07 | Implementation complete: deterministic cluster service, reversible override/reset endpoints, weekly Pro-gated evaluation, two-week persistence, threshold evidence and persisted suppression reporting, dedupe key, and alert read/status endpoints are present. The React UI reports scan status and suppression causes. Bounded storage: at most 2,000 alert states, 50 actions per alert, at most 2,000 candidate rows per scan, plus one compact latest-scan record; all options are non-autoloaded. Static JS syntax/localization checks pass. Live PHP/WordPress runtime, visual/accessibility and volume verification are assigned to I-11. |
| I-09 | Corrective-action tracking | Hypothesis, owner, statuses, dates, measurement windows, before/after view | I-07, I-08 | **Implementation complete:** Pro-gated `/actions` list/create/read/update and `/actions/{id}/evidence`; action/target/history/evidence schema v3; transaction-protected create/update; validated targets/owners/dates/windows; bounded audit/evidence snapshots; baseline required before a non-overlapping follow-up cohort; React create/status/history/evidence controls with non-causal language. Static JS syntax and diff checks pass. PHP/WordPress/database runtime and visual/accessibility checks remain in I-11. No runtime result claimed. |
| I-10 | Optional multilingual/integration compatibility | WPML/Polylang integration and any approved RMA source adapters | I-03 to I-06, demand/API review | Implementation complete for approved V1 scope: guarded WPML String Translation and Polylang reason-label adapters preserve stable reason IDs and WooCommerce CRUD boundaries. Real absent/active runtime checks are tracked in I-11; no RMA source/API was approved. |
| I-11 | Release hardening | Current compatibility matrix, security/accessibility/performance/privacy checks, migration/rollback, docs, package boundaries | I-02 to I-10 | Static audit and source hardening completed for this pass. Release gate remains blocked on PHP/Composer/WP/WC runtime, privacy exporter/eraser runtime behavior, HPOS/legacy, accessibility, and compatibility evidence; see `docs/I10_I11_VERIFICATION.md`. |
| I-12 | Submission preparation | WordPress.org Free and WooCommerce.com Paid artifacts/listings, QIT and reviewer responses | I-11 | Separate packages validated and submission checklists complete |

## I-08 implementation acceptance details

### Implemented slice

- `includes/ClusterService.php` groups stable reasons locally and deterministically; original reason IDs/labels remain visible as membership evidence. Merchant overrides are stored separately in a non-autoloaded option, and an empty cluster key restores the deterministic default.
- `GET /clusters` is Pro-gated and returns clusters plus cohort coverage; rates stay unavailable when coverage is incomplete. `PATCH /clusters/{reason_id}` is manager-capability-gated and validates catalog reason IDs. No source event is rewritten.
- `includes/AlertService.php` schedules weekly WordPress Cron evaluation, uses full bounded pattern output (up to 2,000 rows; above that it fails closed), applies 5-return/80%-mapping/20-sold-unit thresholds, requires two adjacent ISO weeks, and stores deduplicated alert state. `/alerts` lists and `/alerts/{id}` dismisses/restores with actor/time/note history (last 50 actions). The option-backed storage remains subject to volume review.
- `/alerts` also returns the most recent scan summary. The React Alerts screen presents scan status, coverage-block reason/mapping percentage, and counts of candidates withheld by return-volume, sample-size, or rate-availability gates. Mapping below 80% blocks evaluation; at or above 80%, the pattern service's own incomplete-coverage/rate status still suppresses affected candidates. Static syntax/localization checks pass; live WordPress runtime and persisted-state behavior remain unverified.



- Use local deterministic normalization only. Cluster membership must be visible alongside each original reason ID and event counts; raw event/reason records remain unchanged.
- Store merchant-approved cluster overrides separately from source events. Every override must be reversible to the deterministic default and must not rewrite prior event history.
- Alert evaluation is weekly and Pro-gated. The approved defaults are ≥5 confirmed returned units, ≥80% item mapping, ≥20 eligible sold units in each mature comparison cohort, and persistence across two distinct weekly refreshes. Persist and explain scan-level and candidate-level suppression outcomes in the Alerts UI.
- Alert identity must be deterministic across refreshes for the same product, variation, cluster, metric definition, and cohort window. Dismissal/restore must preserve audit actor, timestamp, and note; do not email or expose customer data.
- Do not claim statistical significance or causation. Alert thresholds are operational heuristics and must remain configurable without creating duplicate settings with existing SET-05/06/07 options.

## I-07 implementation references

- `includes/PatternService.php` builds current and preceding mature purchase windows (90-day default; supported maturity/cohort selections: 30/60/90/120/180 days) using WooCommerce CRUD APIs. It scans at most 500 orders per cohort; over-limit cohorts are unavailable rather than partial.
- Eligible quantity currently uses completed/refunded order line-item quantities with a completion date as a documented MVP proxy, not extension-specific fulfillment quantities. Only exact item/order/product/variation matches to active manual `confirmed_return` events enter the numerator. Refund observations and exchanges are excluded.
- Rates are suppressed for incomplete mapping, fewer than 20 sold units, missing denominator, or returned quantity above sold quantity. Comparison changes appear only when both rates are available. Output is capped at 20 patterns.
- `GET /patterns` is gated by `wpessential_return_insights_pro_entitled`, default false. The Free core has no commercial entitlement provider; runtime verification remains.

## I-05 implementation references

- `includes/RestController.php` registers `GET /summary`, validates calendar dates, converts store-local inclusive dates into UTC query bounds, and aggregates only active plugin event records.
- The summary separates event counts from summed recorded quantities and groups reasons, event kinds, sources, item-scoped products, and assigned product categories. Category memberships can overlap; category totals are not additive across categories.
- Mapping coverage reports mapped/unmapped event counts and a percentage with its denominator. It is not a return rate.
- `assets/js/admin.js` adds a date range filter, overview cards, and accessible table equivalents. `includes/Admin.php` supplies localized labels and the default last-30-days period.
- Sales denominators, mature purchase cohorts, rates, confidence, and Pro pattern outputs remain outside I-05.

## I-06 implementation references

- `includes/Admin.php` registers the WooCommerce submenu, scopes JS/CSS to that screen, and localizes site date, permissions, and translation strings.
- `assets/js/admin.js` renders overview, capture, history, catalog, and source-summary areas with WordPress bundled globals.
- `assets/css/admin.css` styles the UI, focus indicators, responsive layout, and reduced-motion behavior.
- `includes/RestController.php` implements `/sources`, REST schemas, pagination/filtering, local date serialization, and strict field checks.
- RTL, accessibility, and visual behavior have not been verified in a rendered WP/WC environment; record that evidence in I-11.

## I-02 implementation references

- [Plugin header requirements](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/)
- [Activation and deactivation hooks](https://developer.wordpress.org/plugins/plugin-basics/activation-deactivation-hooks/)
- [Role and capability guidance](https://developer.wordpress.org/plugins/users/roles-and-capabilities/)
- [WordPress submenu API](https://developer.wordpress.org/reference/functions/add_submenu_page/)
- [WooCommerce HPOS recipe book](https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/recipe-book/)

Activation and upgrade run the forward-only schema migration after dependency checks. Schema v2 added the refund reference/index; schema v3 adds four InnoDB tables for corrective actions, targets, status history, and evidence snapshots. Deactivation preserves records. No historic backfill runs. Runtime migration and rollback verification remain in I-11.

## I-04 official implementation references

- [WooCommerce refund creation code reference](https://woocommerce.github.io/code-reference/files/woocommerce-includes-wc-order-functions.html) — `wc_create_refund()` persists the refund before firing `woocommerce_refund_created` with the refund ID and arguments.
- [WooCommerce HPOS documentation](https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/) — use WooCommerce CRUD objects across HPOS and legacy storage.

## Execution rules for AI coding tools

1. Read `README.md`, `AGENTS.md`, this backlog, and the assigned package specs before changing files.
2. Work on one I-ID at a time unless the owner explicitly combines tightly coupled work.
3. Verify current WordPress/WooCommerce APIs against primary documentation; never invent hooks or internal APIs.
4. Use test fixtures, not real merchant/customer data. Do not disclose secrets or credentials.
5. Do not add CLI/build tooling to the repo before the plugin is final. The sole exception is the owner-authorized `composer.json` PSR-4 mapping; do not add dependencies or run Composer commands before final package preparation.
6. Do not send customer/order data to external AI. External AI remains out of V1 unless a separately approved privacy/security design changes scope.
7. Do not claim a release compatibility or performance result without recorded evidence.
8. Update README roadmap/change log with every meaningful repository change.
9. Report exact files changed, acceptance evidence, open risks, and work not performed.

## I-09 implementation references

- `includes/ActionService.php` stores Pro corrective actions separately from source events. It caps actions at 1,000, targets at 10 per action, status history at 50, and evidence snapshots at 20 per action.
- Action and target changes plus status history use a database transaction; corrective-action tables are explicitly InnoDB. Before/after snapshots store the metric-definition version, cohort dates, returned/sold quantities, rate when eligible, mapping coverage, and eligible-order count. They carry no causal claim.
- `/actions` and its detail/evidence routes are server-side entitlement- and capability-gated. The React screen supports creating a hypothesis and targets, updating status, reviewing status history, and capturing baseline then follow-up evidence.
- Static review is not live acceptance. Composer vendor generation, PHP parsing, database migration/runtime, Pro entitlement, browser UI, accessibility, and supported-version compatibility are pending in I-11.

## I-10/I-11 implementation and verification references

- `includes/Multilingual.php` contains optional WPML/Polylang adapters for merchant-defined reason labels; APIs are guarded and never define canonical reason identity.
- `docs/I10_I11_VERIFICATION.md` records the API references, audit checks, results, and outstanding release gate evidence. I-10 runtime integrations and I-11 runtime/accessibility/privacy gates remain open.
