
# Product Specification — Draft for Review

**Stage:** Pre-development concept; no runtime code exists.  
**Last reviewed:** 2026-11-06

## Problem and outcome

Merchants may know an item was returned but lack dependable reason data at product/variation level, a way to distinguish meaningful patterns from noise, and a record of whether a correction helped.

The product loop is: **capture a reason → identify a repeated pattern → choose a product/content/operations correction → measure the result.**

## Scope

### In scope
- Structured reason events linked to order item, product, and variation when available.
- Free basic reports with counts and transparent sample/data coverage.
- Proposed Pro analysis for product/variation trends, reason clustering, alerts, and corrective-action tracking.
- Optional adapters to existing return workflows, after API feasibility and privacy review.
- Store-owned data and merchant-controlled retention/export/erasure behavior.

### Out of scope
- Replacing RMA workflows or approving/denying requests.
- Refunds, exchanges, store credit, shipping labels, carrier booking.
- Treating every refund as proof of a physical return.
- Sending customer/order data to external AI by default.

## Proposed tiers

| Capability | Free | Pro proposal |
|---|---:|---:|
| Configure stable reasons and translated labels | Yes | Yes |
| Capture structured reason event via agreed source(s) | Yes | Yes |
| Basic reason counts and date range | Yes | Yes |
| Basic product/category summary | Yes, counts only | Yes |
| Product/variation rate trends with visible denominator | — | Yes |
| Explainable, reviewable reason clustering | — | Yes |
| Sample-aware, deduplicated alerts | — | Yes |
| Corrective-action records and before/after measurement | — | Yes |
| Price | Free | Proposed $79/year |

Pricing and exact limits are not approved.

## Capture model — open product decision

Use a reason-only event, not an RMA request. Candidate sources are merchant-entered events, a first-party reason-only customer form, and adapters to selected return plugins. Each event keeps source/provenance; refunds are not automatically classified as physical returns. The plugin never controls approval, money movement, exchange, or shipping.

**D1:** Choose MVP capture sources. Evaluate low friction, reason completeness, privacy, and development cost. Do not promise universal RMA-plugin compatibility.

## Intelligence behavior

- Keep raw reason separate from normalized category; clustering must not overwrite source data.
- Explain cluster membership and let merchants correct/split clusters.
- Mark low samples and distinguish counts from rates.
- Rank issues by evidence and potential impact, not raw count alone.
- Recommendations should be specific, e.g. review sizing details for a variation with repeated size-related reasons.
- Measure comparable post-action periods and report association, not proven causation.

## Success measures

Reason completeness and item mapping; time to review a strong pattern; actions started/completed; comparable return-rate change with sample size; database/performance overhead; merchant adoption and paid conversion. Do not claim prevented returns without evidence.

## Decisions required for P6

- D1 capture sources and MVP reason-only journey.
- D2 definitions for physical return, refund-only, exchange, warranty, cancellation.
- D3 default taxonomy and translated-label identity.
- D4 return-rate denominator, period, and eligibility.
- D5 minimum sample and alert thresholds.
- D6 deterministic/local clustering vs optional external AI, opt-in and data sent.
- D7 Free/Pro boundaries, exports, retention, $79/year price.
- D8 integration priority based on APIs and demand.
- D9 storage choice after volume/query estimates.
