# Logical Data Model and Lifecycle — P3 Blueprint

**Status:** Approved logical model; I-03 event table, I-04 schema v2 refund linkage, I-05 Free count reports, I-07 cohort aggregation, and I-09 corrective-action schema v3 implemented. Runtime validation remains.
**Reviewed:** 2026-11-06.

## Storage decision

Use plugin-owned custom tables for growing event/action records and keep small settings/reason catalogs in non-autoloaded WordPress options. Benchmark indexes and volume before freezing physical schema. The event data is relational and analytics-heavy: it must filter/group by time, reason, product, variation, event kind, and source. WordPress recommends post meta when it is practical; this event workload is the reason to evaluate indexed tables and benchmark rather than defaulting to meta queries.

Use the site-specific WordPress table prefix. Include a schema-version option and forward-only, resumable migrations. Use WordPress table creation/update conventions such as dbDelta; do not require site owners to run SQL or commands. I-02 activation creates no feature tables; the first schema migration is paired with I-03 when event storage is implemented.

## Logical records

### 1. Insight event

One record for an observed refund or a manually entered per-item return insight.

| Field group | Required logical values |
|---|---|
| Identity | Internal event ID; unique idempotency key; source type and source record ID |
| Classification | Event kind: confirmed return, refund-only, exchange, warranty/repair, cancellation, unknown |
| WooCommerce references | Order ID, refund ID, order-item ID, product ID, variation ID where available; refund-scope observations use zero sentinels for item/product IDs |
| Reason | Stable reason ID; raw source phrase is not persisted in V1; attribution scope: item, refund, or order |
| Quantity | Decimal quantity and unit context where source supports it |
| Time | Occurrence timestamp stored in UTC; display using site timezone; source timestamp retained |
| Evidence | Source confidence, mapping state, import/manual origin |
| Audit | Created/updated timestamps and permitted staff actor ID |

Never store customer name, email, address, payment details, or a duplicated order payload in this table. Treat source free text as potentially personal.

### 2. Corrective action

Action title/hypothesis, affected reason/product/variation targets, owner, status, start/completion date, measurement window, and a reference to the metric definition used. Store target associations separately so many products/reasons can be linked without JSON-only searching.

### 3. Alert state

Rule/metric version, affected scope, threshold, observed sample, first/last seen, delivery state, and a dedupe key. Do not store a copy of the full event set in an alert row.

### 4. Reason catalog and clustering

Small reason definitions and translated labels can live in WordPress options and translation-plugin string registries. Stable IDs remain language-independent. Pro clusters need persistent records only for merchant-confirmed merges/splits; raw source reasons remain unchanged. Keep derived suggestions separate from merchant-approved mappings.

## Keys and indexes

Plan for:
- Unique key on source type + source record identity for refund/import idempotency.
- Indexes for occurred-at + event kind; product + variation + time; reason + time; and order/refund references for deletion/privacy lookup.
- Unique dedupe key for alert state.
- Foreign-key-like application references from actions/clusters to event/reason/product IDs; do not assume WooCommerce tables share the plugin's lifecycle or storage engine.

I-03 implements the event table using the site prefix and dbDelta; I-04 advances it to schema v2 with a refund ID column/index. I-09 advances to schema v3 with four InnoDB tables for actions, targets, status history, and evidence snapshots. Event-table keys include unique event UUID/source key and indexes on item-kind-deletion, order-time, refund ID, reason-time, and product-variation-time. Runtime migration/benchmark verification remains in I-11. WooCommerce order/item data is read through public CRUD APIs; no direct WooCommerce order-table SQL.

## Source synchronization

- `woocommerce_refund_created` writes/upserts one refund-scope event keyed by WooCommerce refund ID. Exact active reason-label matches map to stable reason IDs in memory; unmatched phrases map to `unknown`; the source phrase is not persisted. This event is marked `refund_only`, never as a confirmed physical return.
- `woocommerce_refund_deleted` soft-deletes the linked event with an audit note.
- Updates to refund reason/line items refresh the same source event and preserve audit history only as needed.
- Refund deletion removes or marks the linked observation according to merchant retention choice, while respecting approved 24-month default retention and privacy erasure.
- Manual entry receives a generated UUID/idempotency key and is corrected through an authorized admin action.
- No historic backfill on activation. Explicit backfill is date-bounded, batched, resumable, cancellable, and reports unmapped/ambiguous records.

## I-08 cluster persistence boundary

Cluster defaults are computed deterministically from stable reason IDs. Merchant overrides live separately in a non-autoloaded option and can be reset to the computed default; source reason/event rows are never rewritten. Cluster results preserve member reason IDs/labels and inherit source cohort coverage. Weekly alert state is currently held in a bounded non-autoloaded option; each alert retains its latest 50 dismiss/restore actions with actor, UTC timestamp, status, and note. Candidate sets above 2,000 rows fail closed. Volume benchmarks and WordPress runtime verification remain.

## I-07 cohort implementation boundary

I-07 reads WooCommerce orders through CRUD APIs, not direct order-table SQL. Its bounded MVP denominator is native line-item quantity in completed/refunded orders with a completion date; this is a proxy because partial fulfillment is not modeled. The numerator is active item-attributed `confirmed_return` quantity only, with exact event order/item/product/variation mapping. Refund-only and exchange rows are excluded. Two adjacent equally sized mature purchase-date cohorts are compared; defaults are 90-day maturity and window, with supported choices 30/60/90/120/180. Each cohort scan is capped at 500 orders. Incomplete mapping, scan overflow, fewer than 20 sold units, missing denominator, and return quantity above sales suppress the rate. The threshold is a minimum-volume gate, not a confidence interval. No new event table is introduced.

## Metric data boundaries

Free count reports use event time and stable reason ID; I-05 filters by inclusive store-local dates and shows count/quantity groupings, mapping coverage, and item-scoped product/category summaries. Product/variation rates require a denominator and adequate mapping:
- Confirmed return rate uses confirmed returned quantity against eligible sold quantity in the same purchase cohort.
- Refund item ratio uses refund quantities and is labeled as a refund proxy.
- Do not assign a refund-level reason to every line item without explicit attribution.
- Store metric-definition version, maturity/window policy, sample size and data coverage with cached/alert results.

## Privacy and retention lifecycle

- Resolve customer-related export/erasure through WooCommerce order references and supported order APIs; do not persist customer email solely for lookup.
- On erasure, delete or anonymize order/refund/item links and raw reason text as required; retain only non-identifying aggregates if the merchant's policy allows.
- Retain canonical event records for 24 months by default, configurable by the merchant. Raw source phrases are disabled by default; if enabled, cap at 500 characters and purge after 90 days.
- Deactivation preserves data. Uninstall defaults to retaining data and provides a clearly described, explicit, confirmed delete option.
- WordPress privacy exporter/eraser source integration is implemented in `includes/Privacy.php`: actor-attributed activity is paged through the WordPress exporter, order-linked insights are appended to WooCommerce order exports, actor references are anonymized, and event order/refund/item links are removed when WooCommerce anonymizes an order. Runtime behavior and privacy review remain required before release.

## Migrations and Free/Pro boundaries

The Free core owns shared event and reason schema. Pro adds only Pro-specific action/alert/curated-cluster data and must not create a second copy of event facts. Both packages use coordinated schema versions and must tolerate supported Free/Pro update order. If Pro is deactivated or expires, preserve data and keep Free event capture/report access intact.


## Official design references reviewed 2026-11-06

- [WordPress: Creating Tables with Plugins](https://developer.wordpress.org/plugins/creating-tables-with-plugins/) — options vs post meta/custom tables, dbDelta, schema upgrades.
- [WooCommerce: HPOS extension recipe book](https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/recipe-book/) — CRUD/API boundary and HPOS compatibility.
- [WooCommerce: Order refunds API](https://developer.woocommerce.com/docs/apis/rest-api/v1/order-refunds/) — refund reason and line-item references.
- [WooCommerce code reference: refund creation hooks](https://woocommerce.github.io/code-reference/files/woocommerce-includes-wc-order-functions.html) — post-save observer boundary.
- [WordPress: Adding a personal data eraser](https://developer.wordpress.org/plugins/privacy/adding-personal-data-eraser-to-your-plugin/) — erasure integration.
- [Action Scheduler](https://actionscheduler.org/) and [usage guidance](https://actionscheduler.org/usage/) — bounded, traceable background work.


## I-03 physical schema implemented

`includes/Installer.php` defines schema version 3 and the site-prefixed event table plus four action tables. The event table stores event UUID, unique source idempotency hash, source type/record key, kind, order/refund/item/product/variation IDs, stable reason key, decimal quantity, item attribution, mapping state, UTC timestamps, actor IDs, soft-delete timestamp, and deletion audit note; it deliberately has no free-text phrase/customer fields. Action tables store action metadata, product/variation/reason targets, bounded status audit entries, and before/after metric snapshots without customer data. Action writes are transaction-protected; runtime migration behavior and supported database versions still require I-11 verification.
