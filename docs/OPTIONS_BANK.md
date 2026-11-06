
# Deduplicated Options Bank — Draft

Each outcome has one canonical ID and owner. UI labels, integrations, and reports map to these IDs; do not add duplicate options under new names.

## Merchant capabilities

| ID | Capability | Tier | Boundary / acceptance |
|---|---|---|---|
| CAP-01 | Canonical reason taxonomy | Free | Stable IDs; enable/disable/order/labels; translated labels do not split analytics. |
| CAP-02 | Reason-only event capture | Free | Captures a reason without an RMA workflow; records provenance. |
| CAP-03 | Product/order-line association | Free | Link product/variation when source provides it; otherwise mark unknown. |
| CAP-04 | Basic reason summary | Free | Counts/date range; show sample size and data coverage. |
| CAP-05 | Basic product/category summary | Free | Counts only; never imply a rate. |
| CAP-06 | Product/variation rates | Pro | Expose numerator, denominator, period, eligibility. |
| CAP-07 | Reason normalization/clustering | Pro | Explain membership; allow correction/splitting; preserve raw reasons. |
| CAP-08 | Evidence-threshold alerts | Pro | Suppress low-sample and duplicate alerts. |
| CAP-09 | Corrective-action tracking | Pro | Affected items, hypothesis, owner, status, dates, measurement window. |
| CAP-10 | Before/after action report | Pro | Comparable windows and samples; association, not causation. |
| CAP-11 | Export | Gate | Decide fields, privacy, and tier boundary; no PII by default. |
| CAP-12 | Optional source adapters | Gate | Only stable APIs/hooks; no replacement of source workflow. |
| CAP-13 | Correction and deduplication | Free | Authorized edits; idempotent imports. |
| CAP-14 | Retention/export/erasure | Free | Merchant-controlled lifecycle; WordPress privacy integration. |
| CAP-15 | Data coverage indicator | Free | Expose missing item mapping, source, or denominator. |

## Settings

| ID | Setting | Tier | Rule |
|---|---|---|---|
| SET-01 | Capture-source enablement | Free | Explicit toggles; no hidden collection. |
| SET-02 | Reason catalog | Free | Stable identity separate from translated display text. |
| SET-03 | Event types and eligibility | Gate | Distinguish physical return/refund/exchange/warranty/cancel. |
| SET-04 | Rate definition | Gate | Document denominator and period; no implicit metric changes. |
| SET-05 | Minimum sample | Pro | Surface threshold in report/alert. |
| SET-06 | Alert channel/frequency | Pro | Proposed in-product/email; deduplicate and rate-limit. |
| SET-07 | Clustering mode | Pro | Local/deterministic first; external AI needs opt-in decision. |
| SET-08 | Retention | Free | Set safe default and merchant override. |
| SET-09 | Import mapping/idempotency | Gate | Per-adapter IDs and field mapping. |
| SET-10 | Roles/capabilities | Free | Least privilege. |

## System inventory

| ID | System | Single responsibility |
|---|---|---|
| SYS-01 | Capture adapters | Convert source inputs to canonical events. |
| SYS-02 | Validation/normalization | Validate references and map source reason/provenance. |
| SYS-03 | Event repository | Store canonical events and idempotency data. |
| SYS-04 | Reason catalog | Own stable IDs and localized labels. |
| SYS-05 | Metrics/aggregation | Compute counts and explicitly defined rates. |
| SYS-06 | Pattern service | Explain repeat patterns without mutating raw events. |
| SYS-07 | Alert service | Threshold checks, dedupe, delivery. |
| SYS-08 | Corrective-action service | Store interventions and outcome windows. |
| SYS-09 | React admin app | Reports/settings/actions with confidence and access states. |
| SYS-10 | Privacy/lifecycle service | Export, erase, retention, uninstall. |
| SYS-11 | WooCommerce/localization compatibility | HPOS-safe APIs and optional language bridges. |
| SYS-12 | License/entitlement service | Central Free/Pro feature gating. |

## Deferred

RMA status/refund/exchange/store-credit/shipping workflows; automated product changes; external AI with event/order data; predictive avoided-return claims; cross-store benchmarks; carrier/ERP/CRM/warehouse integrations without evidence.

## Deduplication check

Before adding a feature, identify its existing canonical ID and whether it changes source, setting, report, or UI only. Extend the ID if the outcome is the same; create a new ID only for a distinct user outcome.
