# Logical Data Model and Lifecycle — P3 Blueprint

**Status:** Approved logical model; not database code.  
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
| WooCommerce references | Order ID, refund ID, order-item ID, product ID, variation ID where available |
| Reason | Stable reason ID; optional source phrase; attribution scope: item, refund, or order |
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

Exact SQL types/index order follow representative performance benchmarks and supported MySQL/MariaDB versions. P6 approved custom indexed tables; no direct WooCommerce order-table SQL.

## Source synchronization

- Refund-created hook writes/upserts the refund observation.
- Updates to refund reason/line items refresh the same source event and preserve audit history only as needed.
- Refund deletion removes or marks the linked observation according to merchant retention choice, while respecting approved 24-month default retention and privacy erasure.
- Manual entry receives a generated UUID/idempotency key and is corrected through an authorized admin action.
- No historic backfill on activation. Explicit backfill is date-bounded, batched, resumable, cancellable, and reports unmapped/ambiguous records.

## Metric data boundaries

Free count reports use event time and stable reason ID. Product/variation rates require a denominator and adequate mapping:
- Confirmed return rate uses confirmed returned quantity against eligible sold quantity in the same purchase cohort.
- Refund item ratio uses refund quantities and is labeled as a refund proxy.
- Do not assign a refund-level reason to every line item without explicit attribution.
- Store metric-definition version, maturity/window policy, sample size and data coverage with cached/alert results.

## Privacy and retention lifecycle

- Resolve customer-related export/erasure through WooCommerce order references and supported order APIs; do not persist customer email solely for lookup.
- On erasure, delete or anonymize order/refund/item links and raw reason text as required; retain only non-identifying aggregates if the merchant's policy allows.
- Retain canonical event records for 24 months by default, configurable by the merchant. Raw source phrases are disabled by default; if enabled, cap at 500 characters and purge after 90 days.
- Deactivation preserves data. Uninstall defaults to retaining data and provides a clearly described, explicit, confirmed delete option.
- WordPress privacy exporter/eraser integration is required before release.

## Migrations and Free/Pro boundaries

The Free core owns shared event and reason schema. Pro adds only Pro-specific action/alert/curated-cluster data and must not create a second copy of event facts. Both packages use coordinated schema versions and must tolerate supported Free/Pro update order. If Pro is deactivated or expires, preserve data and keep Free event capture/report access intact.


## Official design references reviewed 2026-11-06

- [WordPress: Creating Tables with Plugins](https://developer.wordpress.org/plugins/creating-tables-with-plugins/) — options vs post meta/custom tables, dbDelta, schema upgrades.
- [WooCommerce: HPOS extension recipe book](https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/recipe-book/) — CRUD/API boundary and HPOS compatibility.
- [WooCommerce: Order refunds API](https://developer.woocommerce.com/docs/apis/rest-api/v1/order-refunds/) — refund reason and line-item references.
- [WooCommerce code reference: refund creation hooks](https://woocommerce.github.io/code-reference/files/woocommerce-includes-wc-order-functions.html) — post-save observer boundary.
- [WordPress: Adding a personal data eraser](https://developer.wordpress.org/plugins/privacy/adding-personal-data-eraser-to-your-plugin/) — erasure integration.
- [Action Scheduler](https://actionscheduler.org/) and [usage guidance](https://actionscheduler.org/usage/) — bounded, traceable background work.
