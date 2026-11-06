
# Product Specification — Approved V1 Scope

**Stage:** P6-approved V1 product scope; no runtime code exists.  
**Last reviewed:** 2026-11-06

## Problem and outcome

Merchants may know an item was returned but lack dependable reason data at product/variation level, a way to distinguish meaningful patterns from noise, and a record of whether a correction helped.

The product loop is: **capture a reason → identify a repeated pattern → choose a product/content/operations correction → measure the result.**

## Scope

### In scope
- Structured reason events linked to order item, product, and variation when known.
- Free basic reports with counts and transparent sample/data coverage.
- Proposed Pro analysis for product/variation trends, reason clustering, alerts, and corrective-action tracking.
- Optional adapters to existing return workflows, after API feasibility and privacy review.
- Store-owned data and merchant-controlled retention/export/erasure behavior.

### Out of scope
- Replacing RMA workflows or approving/denying requests.
- Refunds, exchanges, store credit, shipping labels, carrier booking.
- Treating every refund as proof of a physical return.
- Sending customer/order data to external AI by default.

## Recommended MVP capture model

1. **Observe WooCommerce refunds:** ingest the native refund reason and available line-item/product/variation/quantity references through supported WooCommerce objects/hooks. This is a refund signal, not proof of a physical return.
2. **Manual per-item insight event:** let a permitted store user record a confirmed return or exchange reason per order item, including cases with no WooCommerce refund. This records an insight only; it does not approve or process the return.
3. **Do not ship a customer RMA portal in MVP.** Consider a reason-only customer form or specific RMA adapters after demand and integration APIs are audited.
4. Preserve source type and attribution scope. If one refund reason covers multiple line items, keep the reason at refund scope unless the merchant explicitly maps it to items. Do not silently duplicate it across variations.

This model provides reason capture without building another return-management workflow. The product owner approved it at P6 on 2026-11-06.

## Proposed tiers

| Capability | Free | Pro proposal |
|---|---:|---:|
| Stable reason catalog and translated labels | Yes | Yes |
| Native refund observation and manual per-item reason entry | Yes | Yes |
| Basic reason counts/date filters and data coverage | Yes | Yes |
| Basic product/category summaries | Counts only | Yes |
| Product/variation trends and qualified rate metrics | — | Yes |
| Explainable, reviewable reason clustering | — | Yes |
| Sample-aware, deduplicated alerts | — | Yes |
| Corrective-action tracking and comparable before/after report | — | Yes |
| Price | Free | Proposed $79/year |

Pricing and exact limits remain proposals.

## Reason and event model

Approved event kinds: confirmed physical return, refund-only, exchange, warranty/repair, cancellation, unknown. Seed reason keys: fit/size; quality/defect; damage; appearance/color; not as described; wrong item/variation; missing part/accessory; delivery delay; delivery damage; duplicate order; changed mind; ordered by mistake; other; unknown. Merchant-facing labels can be edited and translated while canonical IDs remain stable. Retirement never rewrites event history.

## Measurement rules

- Free reports show reason counts, sample size, date range, and mapping coverage.
- Confirmed-return rate = unique confirmed returned quantity divided by eligible sold quantity in the same product/variation purchase cohort. Cohort maturity defaults to 90 days after purchase; merchants can select 30/60/90/120/180 days. Suppress immature or incompletely mapped cohorts.
- Refund data alone is labeled as a refund signal/ratio, not a physical return rate.
- A reason attached to a refund spanning multiple items is order/refund-level unless item-level reason is explicitly captured.
- Recommendations are hypotheses. Before/after comparison reports association, not proof of causation.

## Intelligence behavior

Keep raw reason separate from normalized category. Clustering must be explainable, editable, and reversible. Mark low samples. Rank issues by evidence and potential impact, not raw counts alone. V1 clustering uses local deterministic grouping and is explainable/reversible. External AI processing is excluded from V1; any later proposal requires a separate privacy/security review and explicit owner approval.

## Success measures

Reason completeness and item mapping; time to review a strong pattern; actions started/completed; comparable post-action rate change with samples; performance/database overhead; merchant adoption and paid conversion. Do not claim prevented returns without evidence.

## Approved P6 decisions

Approved by product-owner delegation on 2026-11-06; full record is in `docs/P6_READINESS_GATE.md`.

- Capture: native refund observation plus authorized manual per-item insight entry; no customer return portal or RMA workflow in V1.
- Event kinds: confirmed return, refund-only, exchange, warranty/repair, cancellation, unknown. A WooCommerce refund remains refund-only evidence.
- Seed reasons: fit/size; quality/defect; damage; appearance/color; not as described; wrong item/variation; missing part/accessory; delivery delay; delivery damage; duplicate order; changed mind; ordered by mistake; other; unknown. Stable reason IDs are separate from translated labels.
- Rates: confirmed returned quantity divided by eligible sold quantity in the same product/variation purchase cohort, with 90-day maturity by default and merchant-selectable 30/60/90/120/180 days. Refund ratios remain separate. Suppress incomplete/immature metrics.
- Alerts: Pro, in-product only; heuristic defaults require at least 5 confirmed returned units, 80% item mapping, at least 20 eligible sold units in each mature 90-day comparison cohort, and persistence across two weekly refreshes. These are configurable operational thresholds, not significance claims.
- Clustering: local deterministic grouping, explainable and reversible; external AI off and out of V1.
- Tiers: Free capture/catalog/coverage/counts/privacy tools; Pro product/variation trends, clustering, alerts/actions. $79/year stays a proposed price pending listing/business review.
- Retention: canonical events 24 months by default; raw phrases off by default, and 500-character maximum/90-day purge if enabled. Uninstall retains data unless the merchant explicitly confirms deletion.
- Integrations: no third-party RMA adapters in V1; evaluate individually after demand/API/privacy review.
- Storage: plugin-owned indexed event/action tables, options for small settings/catalog, bounded Action Scheduler work; physical schema follows benchmarks.
- Packaging: one repo generates independent Free core and Pro add-on ZIPs; Pro requires Free, shares event data, and stores Pro-only records. Separate WordPress.org and Woo Marketplace submissions.
- Compatibility: PHP 8.3+, current and prior required WordPress/WooCommerce series at implementation and submission, HPOS/legacy support, WPML/Polylang optional, per-site multisite activation only.
- Admin/build: React via WordPress bundled `wp.element`, `wp.components`, and `wp.apiFetch`; browser-ready JS without JSX or build tooling. No CLI/build tooling in this repo until plugin final.
