
# Architecture and Data Flow — Proposal

Conceptual only; no implementation decisions are final.

## Recommended source model

1. **Native refund observer:** observe WooCommerce refund creation and read the refund through WooCommerce data objects. The official API exposes refund reason plus line-item references, product/variation IDs, and quantities. The WooCommerce code reference documents the woocommerce_refund_created action after a refund is saved.
2. **Manual per-item event:** authorized store staff records a confirmed return/exchange reason against an order item, even if no refund exists. This is an insight record only; it does not change order, refund, exchange, or return status.
3. **Future adapters:** only add selected RMA integrations after their APIs and demand are confirmed. No universal adapter promise.

Sources: [WooCommerce order refund API](https://developer.woocommerce.com/docs/apis/rest-api/v1/order-refunds/) · [WooCommerce code reference for refund actions](https://woocommerce.github.io/code-reference/files/woocommerce-includes-wc-order-functions.html)

## Canonical event candidates

Internal event ID and idempotency key; event kind; source type/reference; refund/order/order-item IDs when available; product/variation IDs and quantity when available; canonical reason ID and optional source phrase; event timestamp and store timezone; capture locale; attribution scope (refund-level or per-item); source confidence; minimal audit metadata.

Do not copy customer name, email, address, payment information, or full order payload into analytics records without an approved use case.

## Attribution rule

A native refund can contain one free-text reason and multiple line items. Preserve the refund-level reason and the referenced line items. If the reason is not explicitly assigned to each item, do not multiply it into per-variation evidence. The admin per-item event is the source for precise item-level reason attribution. Distinguish refund evidence from confirmed physical return.

## Proposed flow

1. Capture adapter observes a refund or receives manual staff entry.
2. Validation checks capability/nonce, valid WooCommerce references, event kind, reason, quantity, and input bounds.
3. Normalization maps source reasons to stable internal IDs while preserving raw text/provenance.
4. Idempotency prevents duplicate refund processing/import retries.
5. Repository stores canonical events behind a storage abstraction. Compare custom tables vs custom post type/meta after estimating volume and query needs.
6. Aggregation computes counts/rates from explicit, versioned definitions.
7. Pattern service groups evidence without rewriting source events.
8. Alert service evaluates thresholds; React admin presents evidence and corrective actions.
9. Privacy lifecycle supports export, erasure, retention, and uninstall choices.

## WooCommerce boundary

Use public WooCommerce CRUD APIs and supported hooks. Support HPOS and legacy storage. A refund is evidence of money returned, not proof that goods physically returned. Never rely on direct queries against legacy order tables.

## Metric definitions to decide

- Reason event count: captured events matching filters.
- Confirmed item return rate: confirmed returned quantity / sold quantity for the same product/variation cohort, if complete denominator data exists.
- Refund item ratio: refunded quantity / sold quantity, clearly labeled as a refund proxy.

Choose the primary rate and document cohort, maturity/window, partial quantity, incomplete periods, zero denominator, and metric-definition version. Never mix counts and rates.

## React admin proposal

React app inside a dedicated WooCommerce admin page. Use WordPress component/data conventions after implementation approval. Serve data from permission-protected REST endpoints with pagination and aggregates. Server capability checks are authoritative. Include loading, empty, error, denied, low-sample, and stale-data states; charts need equivalent tables/summaries.

## Privacy/security

Validate/sanitize input and escape output; use capability checks and REST permission callbacks; verify staff permissions; rate-limit any later public capture; integrate WordPress privacy exporter/eraser where applicable. Deactivation must not silently erase data. External AI is off by default; any future opt-in requires data minimization, provider/cost/retention disclosure, and privacy review.

## Engineering decisions for P3

Storage/migrations; per-item reason field UX; source contracts/idempotency; taxonomy and translation identity; metric cohort and maturity policy; clustering explainability; REST shape/pagination; performance and database growth budgets.
