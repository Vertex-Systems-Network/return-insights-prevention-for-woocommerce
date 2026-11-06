
# Deduplicated Options Bank — Approved V1 Baseline

Each outcome has one canonical ID and owner. UI labels, integrations, and reports map to these IDs; do not add duplicate options under new names.

## Merchant capabilities

| ID | Capability | Tier | Boundary / acceptance |
|---|---|---|---|
| CAP-01 | Canonical reason taxonomy | Free | Stable IDs; enable/disable/order/labels; translations do not split analytics. |
| CAP-02 | Reason-only event capture | Free | MVP sources: native refund observation plus manual per-item event; no RMA workflow. |
| CAP-03 | Product/order-line association | Free | Link product/variation/quantity when available; otherwise mark unknown or refund-level. |
| CAP-04 | Basic reason summary | Free | Counts/date range; show sample size and data coverage. |
| CAP-05 | Basic product/category summary | Free | Counts only; never imply a rate. |
| CAP-06 | Product/variation rates | Pro | Show numerator, denominator, event kind, period, and eligibility. |
| CAP-07 | Reason normalization/clustering | Pro | Explain membership; allow correction/splitting; preserve raw reason. |
| CAP-08 | Evidence-threshold alerts | Pro | Suppress low-sample and duplicate alerts. |
| CAP-09 | Corrective-action tracking | Pro | Affected items, hypothesis, owner, status, dates, measurement window. |
| CAP-10 | Before/after action report | Pro | Comparable windows/samples; association, not causation. |
| CAP-11 | Export | Free | Merchant data export; omit customer identity and full order payload by default. |
| CAP-12 | Optional source adapters | Gate | Stable APIs/hooks only; no replacement of source workflow. |
| CAP-13 | Correction and deduplication | Free | Authorized edits; idempotent imports. |
| CAP-14 | Retention/export/erasure | Free | Merchant-controlled lifecycle; WordPress privacy integration. |
| CAP-15 | Data coverage indicator | Free | Show missing item mapping, source, denominator, and attribution scope. |

## Competitor option crosswalk

OBS IDs are research observations, not new plugin features. They prevent a familiar RMA setting from being accidentally copied into this product.

| ID | Competitor option/system observed | Our disposition | Existing owner if reused |
|---|---|---|---|
| OBS-01 | Return window and eligible order statuses | Exclude from our workflow; capture adapter may read provenance only | CAP-12 |
| OBS-02 | Product/category/payment-method eligibility and sale-item exclusions | Exclude; return policy controls | CAP-12 only if needed as source metadata |
| OBS-03 | Partial return, item/quantity selection | Reuse item/quantity data for analytics; do not approve/process request | CAP-03 |
| OBS-04 | Custom return reasons and reason-for-exchange | Reuse canonical reason taxonomy; no separate exchange workflow | CAP-01 |
| OBS-05 | Return/exchange guidelines and terms page | Exclude; not needed for analysis | None |
| OBS-06 | Auto-approve/reject and status transitions | Exclude; duplicates RMA processing | None |
| OBS-07 | Refund method, shipping refund, store credit, restocking fee | Exclude; money movement/policy outside scope | None |
| OBS-08 | Email/SMS status notifications | RMA status notifications excluded; insight alerts map to CAP-08 | CAP-08 |
| OBS-09 | Warranty/repair/complaint claim workflow | Exclude from MVP; event kind remains distinct | CAP-02 / D2 |
| OBS-10 | Attachments/photo proof | Defer; privacy/storage burden requires evidence | Gate |
| OBS-11 | Guest/customer return portal | Deferred; reason-only capture is not a return portal | CAP-02 / D1 |
| OBS-12 | Return dashboards, reason/product analytics | Reuse basic summaries, differentiate with corrective loop | CAP-04–CAP-10 |
| OBS-13 | AI assistant that files/manages return requests | Exclude; separate from insight analysis and coding agents | None |
| OBS-14 | CSV export | Evaluate as data portability, not an RMA workflow | CAP-11 |
| OBS-15 | WooCommerce refund reason and line-item records | Reuse as refund evidence; preserve refund-level scope and never infer physical return | CAP-02 / CAP-03 |

## Settings

| ID | Setting | Tier | Rule |
|---|---|---|---|
| SET-01 | Capture-source enablement | Free | Explicit toggles; no hidden collection. |
| SET-02 | Reason catalog | Free | Stable identity separate from translated display text. |
| SET-03 | Event kinds | Free | Approved canonical kinds: confirmed return, refund-only, exchange, warranty/repair, cancellation, unknown. |
| SET-04 | Rate definition | Pro | Confirmed returned quantity / eligible sold quantity, same cohort; 90-day maturity default, selectable 30/60/90/120/180 days. Refund ratio remains distinct. |
| SET-05 | Minimum sample | Pro | Alert heuristic defaults: 5 confirmed return units, 80% item mapping, 20 eligible sold units per cohort, persistence across two weekly refreshes. Explain and allow configuration. |
| SET-06 | Alert channel/frequency | Pro | In-product only in V1; deduplicate, rate-limit, show evidence. Email deferred. |
| SET-07 | Clustering mode | Pro | Local deterministic and explainable in V1; no external AI data processing. |
| SET-08 | Retention | Free | Canonical events 24 months default; raw phrases disabled by default, max 500 characters and 90-day purge if enabled. Merchant may configure. |
| SET-09 | Import mapping/idempotency | Free | Native refund source and manual entry only in V1; unique source keys prevent duplicates. |
| SET-10 | Roles/capabilities | Free | Least privilege. |
| SET-11 | Attribution scope/confidence | Free | Preserve refund-level vs per-item source; do not silently infer. |

## System inventory

| ID | System | Single responsibility |
|---|---|---|
| SYS-01 | Capture adapters | Convert native refunds/manual/external inputs to canonical events. |
| SYS-02 | Validation/normalization | Validate references and map reason/provenance/scope. |
| SYS-03 | Event repository | Store canonical events and idempotency data. |
| SYS-04 | Reason catalog | Own stable IDs and localized labels. |
| SYS-05 | Metrics/aggregation | Compute counts and explicitly defined rates. |
| SYS-06 | Pattern service | Explain repeat patterns without mutating raw events. |
| SYS-07 | Alert service | Threshold checks, dedupe, delivery. |
| SYS-08 | Corrective-action service | Store interventions and outcome windows. |
| SYS-09 | React admin app | Reports/settings/actions with confidence/access states. |
| SYS-10 | Privacy/lifecycle service | Export, erase, retention, uninstall. |
| SYS-11 | WooCommerce/localization compatibility | HPOS-safe APIs and optional language bridges. |
| SYS-12 | License/entitlement service | Central Free/Pro feature gating. |

## Deferred

RMA status/refund/exchange/store-credit/shipping workflows; customer return portal; automated product changes; external AI with event/order data; predictive avoided-return claims; cross-store benchmarks; carrier/ERP/CRM/warehouse integrations without evidence.

## Deduplication check

Before adding a feature, identify its existing canonical ID and whether it changes source, setting, report, or UI only. Extend that ID for the same outcome. Create a new ID only for a distinct merchant outcome.
