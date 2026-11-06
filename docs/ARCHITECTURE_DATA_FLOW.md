
# Architecture and Data Flow — Engineering Blueprint

**Status:** Blueprint and P6 decisions approved; I-02 foundation, I-03 manual capture/catalog, and I-04 refund observer implemented; runtime validation remains.  
**Reviewed:** 2026-11-06. 

## Source model

1. **Native refund observer:** observe WooCommerce refund creation and read the refund through WooCommerce data objects. The official API exposes refund reason and line-item/product/variation/quantity references. Use the documented post-save refund hook for observation.
2. **Manual per-item insight event:** permitted store staff records a confirmed return/exchange reason against an order item, even if no refund exists. This records insight only; it does not change return/order/refund status.
3. **Future adapters:** selected RMA integrations only after APIs and demand are confirmed. No universal adapter promise.

## Canonical event

See docs/DATA_MODEL.md. Core facts: event kind, source/provenance, unique source reference, order/refund/item/product/variation IDs when available, quantity, stable reason ID, optional raw reason, event time, attribution scope, confidence, and minimal audit metadata.

Do not copy customer name, email, address, payment information, or full order payload into analytics records.

## Attribution and idempotency

A refund may contain one free-text reason and multiple line items. Preserve the reason at refund scope unless it was explicitly assigned to each line. Never multiply an order-level reason into per-variation evidence. Manual per-item capture is the precise item-level source.

Use a stable external key for refund observation and a generated unique key for manual events. Repeated hook delivery or import retries must upsert/ignore the same source event, not create duplicates. Track source changes/deletions and provide an auditable correction path.

## Data flow

1. Hook observes refund creation or admin sends manual entry.
2. Validate capability, nonce, references, event kind, reason and quantity.
3. Normalize to stable reason IDs; preserve raw source only under the approved retention/privacy rules.
4. Write one small event transaction and enqueue any heavy aggregation.
5. Aggregate indexed event data and bounded WooCommerce sales data.
6. Pattern service generates explainable candidates without changing source events.
7. Alert service applies sample thresholds and deduplication.
8. React admin displays evidence, confidence, and corrective actions.
9. Privacy lifecycle exports or erases/anonymizes linked data as required.

## Metric contract

- **Reason-event count:** captured records by reason/event time; Free report.
- **Confirmed item return rate:** confirmed returned quantity divided by eligible sold quantity in the same purchase cohort; only show with adequate mapping and denominator.
- **Refund item ratio:** refunded quantity divided by eligible sold quantity; label as refund proxy, never as a physical-return rate.
- Exclude ambiguous refund-level reasons from variation-level attribution. Show sample size, data coverage, and metric definition/version.
- Use a 90-day default cohort maturity window, adjustable to 30/60/90/120/180 days in settings. This is an analytics heuristic, not a return policy. Exclude immature cohorts.
- P6 D4 approved: numerator is unique confirmed returned quantity; denominator is eligible sold quantity for the same product/variation purchase cohort. Exclude canceled/unfulfilled quantities; deduplicate events. Refund ratios are separate.

## WooCommerce and HPOS boundary

Use WooCommerce CRUD APIs and supported hooks for order/refund access. Support HPOS and legacy order storage. Do not query or write WooCommerce order tables directly. Keep product analytics SQL confined to the plugin's own indexed tables; obtain order/sales facts through WooCommerce-supported APIs or a bounded snapshot strategy.

## Performance plan

- Refund hook performs validation and a small idempotent write only; no dashboard-wide aggregation in the refund request.
- Use WooCommerce's Action Scheduler when available for incremental aggregation, explicit historical backfill, and retryable tasks.
- Do not backfill all historic refunds automatically at activation. Offer an explicit, resumable, cancellable date-range backfill.
- Use indexed date/reason/product/variation/source keys and pagination; avoid loading every event into React.
- Add cached/day-level rollups only after representative benchmarks show they are needed. Define data-volume and p95 targets before implementation.
- Instrument queue lag, failed jobs, query duration and event mapping coverage without collecting customer PII.

## React and REST

React admin uses WordPress bundled `wp.element`, `wp.components`, and `wp.apiFetch` globals via registered script handles; browser-ready JS, no JSX/build pipeline. Application sits under a dedicated WooCommerce page. REST endpoints require server-side capability checks, validation, sanitization, pagination and explicit schemas. Hiding a control in React is not authorization. Include loading, empty, error, denied, low-sample, stale-data and integration-unavailable states; every chart has a table/summary equivalent.

## Security/privacy

Minimize data and treat free-text refund reasons as potentially identifying. Raw phrase storage is off by default; if enabled, cap at 500 characters and purge after 90 days. Canonical event retention defaults to 24 months and is merchant-configurable. Resolve privacy export/erasure through the linked Woo order without storing customer email. On erasure, remove personal linkage while retaining only non-identifying aggregates where permitted. Deactivation preserves data. Uninstall retains data by default and offers explicit, confirmed deletion.

External AI remains off by default. Any future provider processing needs explicit opt-in, field minimization, disclosure, retention/cost policy and a security/privacy review.

## I-04 implementation boundary

`Refund_Observer` listens to WooCommerce's documented post-save `woocommerce_refund_created` hook and deletion hook. It reads the refund and parent order through `wc_get_order()` and stores one event at `refund` scope, with no item/product attribution, so a refund-level phrase is not multiplied into product/variation evidence. Exact active reason-catalog labels map in memory; unmatched text becomes `unknown`, and source free text is not stored. Schema v2 adds refund ID linkage. Runtime behavior on HPOS/legacy stores, duplicate hook delivery, and deletion lifecycle has not been verified.

## P6 decisions and remaining implementation validation

P6 approved indexed plugin-owned event/action tables, 24-month canonical event retention, optional 90-day raw-phrase retention, 90-day default cohort maturity, configurable heuristic alert thresholds, site-local data scoping, and V1 no network-wide multisite activation. Physical schema/indexes, benchmark targets, and test evidence remain implementation tasks.
