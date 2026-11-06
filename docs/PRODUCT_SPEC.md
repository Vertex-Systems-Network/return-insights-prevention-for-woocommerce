
# Product Specification — Draft for Review

**Stage:** Pre-development concept; no runtime code exists.  
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

This model provides reason capture without building another return-management workflow. Approval of this proposal remains part of P6.

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

Separate the event kind from its source. Candidate event kinds: confirmed physical return, refund-only, exchange, warranty/repair, cancellation, unknown. Initial reason groups should distinguish product issues (fit/size, appearance, quality/damage, not as described, wrong item/variation) from order/fulfilment issues (delivery, duplicate/unwanted order, changed mind, other/unknown). Merchants can customize the catalog; stable IDs prevent translations from splitting analysis.

## Measurement rules

- Free reports show reason counts, sample size, date range, and mapping coverage.
- A product/variation return rate is only shown when a physical return quantity and denominator are supported.
- Refund data alone is labeled as a refund signal/ratio, not a physical return rate.
- A reason attached to a refund spanning multiple items is order/refund-level unless item-level reason is explicitly captured.
- Recommendations are hypotheses. Before/after comparison reports association, not proof of causation.

## Intelligence behavior

Keep raw reason separate from normalized category. Clustering must be explainable, editable, and reversible. Mark low samples. Rank issues by evidence and potential impact, not raw counts alone. External AI processing is off by default and requires merchant opt-in and a privacy review.

## Success measures

Reason completeness and item mapping; time to review a strong pattern; actions started/completed; comparable post-action rate change with samples; performance/database overhead; merchant adoption and paid conversion. Do not claim prevented returns without evidence.

## Decisions required for P6

- D1 approve or revise the recommended capture model and omit/retain customer form in MVP.
- D2 finalize event kinds and when a refund can be treated as return evidence.
- D3 finalize default reason taxonomy and translation rules.
- D4 select rate denominator, cohort/window, and maturity rules.
- D5 set minimum sample and alert thresholds.
- D6 decide deterministic/local clustering vs optional external AI and exact data sent.
- D7 approve Free/Pro boundaries, exports, retention, and $79/year pricing.
- D8 prioritize specific integrations after API/demand review.
- D9 choose storage after volume/query estimates.
