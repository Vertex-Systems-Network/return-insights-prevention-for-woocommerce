
# Architecture and Data Flow — Proposal

Conceptual only; no implementation decisions are final.

## Canonical event candidates

Internal event ID and idempotency key; event kind; source type/reference; order and order-item IDs if available; product and variation IDs if available; canonical reason ID and optional source phrase; quantity; event timestamp and store timezone; capture locale; source confidence; minimal audit metadata.

Do not copy customer name, email, address, payment information, or full order payload into the analytics record without an approved use case.

## Proposed flow

1. Capture adapter accepts merchant entry, reason-only form, or supported external source.
2. Validation checks capability/nonce or scoped token, order ownership where relevant, valid item/quantity, reason, and input size.
3. Normalization maps source reasons to stable internal IDs while preserving provenance.
4. Idempotency prevents duplicate events on retries.
5. Repository stores canonical events behind a storage abstraction. Compare custom tables vs custom post type/meta after estimating data volume and query needs.
6. Aggregation computes counts and rates from explicit versioned definitions.
7. Pattern service groups evidence without rewriting source events.
8. Alert service evaluates thresholds; React admin presents evidence and corrective actions.
9. Privacy lifecycle supports export, erasure, retention, and uninstall choices.

## WooCommerce boundary

Use public WooCommerce CRUD APIs and supported hooks. Support HPOS and legacy storage. A WooCommerce refund is evidence of a refund, not proof that goods physically returned. Optional adapters need version checks, defensive parsing, provenance, and idempotency. Never rely on direct queries against legacy order tables.

## Metric definitions to decide

- Reason event count: captured events matching filters.
- Item return rate: returned quantity / sold quantity for the same item and comparable period.
- Order return incidence: orders with a return event / orders containing the item in that period.

Choose one primary rate and document eligibility, partial quantities, incomplete periods, zero denominator, and saved metric version. Never mix counts and rates.

## React admin proposal

React app inside a dedicated WooCommerce admin page. Use WordPress component/data conventions after implementation approval. Serve data from permission-protected REST endpoints with pagination and aggregates. Server capability checks are authoritative. Include loading, empty, error, denied, low-sample, and stale-data states; charts need equivalent tables/summaries.

## Privacy/security

Validate/sanitize input and escape output; use capability checks and REST permission callbacks; verify customer ownership or narrow, expiring tokens; rate-limit public capture; integrate WordPress privacy exporter/eraser where applicable. Deactivation must not silently erase data. External AI is off by default; any future opt-in requires data minimization, provider/cost/retention disclosure, and privacy review.

## Engineering decisions for P3

Storage/migrations; event source contracts; taxonomy and translation identity; metric denominator/time windows; clustering explainability; REST shape/pagination; performance and database growth budgets.
