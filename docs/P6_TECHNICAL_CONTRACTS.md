# P6 Technical Contracts — REST and Security/Privacy

**Status:** Proposed contract for owner review; no endpoints or code implemented.  
**Reviewed:** 2026-11-06  
**Depends on:** Explicit P6 approval and final privacy, metric, and package decisions.

This document makes the engineering boundary concrete enough to estimate and review implementation. Values marked “proposed” remain subject to approval.

## REST namespace and shared rules

**Proposed namespace:** `wpessential/return-insights/v1`. Reserve this namespace for this plugin; version it only for incompatible API changes.

- Admin requests use the WordPress REST cookie-auth flow with a REST nonce. A nonce helps prevent CSRF; it is not a substitute for capability checks.
- Every route defines a `permission_callback`. Read and write capabilities are checked server-side on every request.
- Proposed plugin capabilities: `view_return_insights` and `manage_return_insights`. Map them deliberately to WooCommerce roles during implementation; do not use UI visibility as authorization.
- Every request field has a schema, strict validation, and sanitization only where transformation is appropriate. Reject invalid IDs, enums, date ranges, quantities, and unknown fields.
- Use WooCommerce public CRUD APIs to verify order/refund/order-item/product references and the actor’s permitted access. Never trust client-supplied product, variation, or order relationships.
- Return only fields needed by the current screen. Never expose customer name, email, billing/shipping address, payment details, authentication data, or full order objects through the insights API.
- Use server-side pagination with deterministic ordering; cap page size (proposed maximum 100). No unbounded event collection.
- Store timestamps in UTC and return ISO-8601 UTC values plus the site timezone identifier for display. Date-filter semantics use the store’s calendar timezone and convert explicitly at query boundaries.
- Use consistent errors: stable machine-readable `code`, translated human `message`, HTTP status, and field errors when relevant. Do not return SQL, stack traces, filesystem paths, or raw exception messages.
- Mutation endpoints require idempotency for retryable event writes and use optimistic conflict handling for concurrent edits.
- License gating is enforced server-side for Pro data/actions; expiry cannot delete or hide merchant-owned event data.

## Proposed route contract

| Route | Methods | Capability | Purpose / schema summary |
|---|---|---|---|
| `/summary` | GET | `view_return_insights` | Query: `date_from`, `date_to`, `timezone`, optional `reason_id`, `product_id`, `variation_id`, `event_kind`. Response: metric version, period, event counts, explicitly named refund ratio/confirmed-return rate only when eligible, coverage, sample sizes, freshness. |
| `/patterns` | GET | `view_return_insights` | Query: shared filters, `page`, `per_page`, `orderby`, `order`. Response: reason/product/variation pattern, evidence count, source scope, comparison context, data-quality flags, pagination. |
| `/events` | GET, POST | GET: view; POST: manage | GET returns a paged event list and provenance. POST accepts `event_kind`, `order_id`, `order_item_id`, `quantity`, `reason_id`, optional bounded `source_phrase`, `occurred_at`, and an idempotency key. Server derives/validates product relationships. |
| `/events/(?P<id>[a-zA-Z0-9_-]+)` | GET, PATCH, DELETE | GET: view; mutations: manage | Read or correct permitted manual insight records. Delete is a soft/retention-aware action with audit metadata; no mutation of WooCommerce refunds/orders. Immutable source facts remain distinguishable from merchant corrections. |
| `/reasons` | GET, POST | GET: view; POST: manage | Read catalog or create a canonical reason. POST accepts stable-safe label, group, localized labels, active state, and order. Server generates canonical ID; labels cannot overwrite identity. |
| `/reasons/(?P<id>[a-zA-Z0-9_-]+)` | GET, PATCH | GET: view; PATCH: manage | Update labels/order/active state. No destructive merge of history. A future merge operation requires a separate audited flow. |
| `/sources` | GET | `view_return_insights` | Source health, last success/error, event counts, mapping/coverage warnings. No arbitrary source reconfiguration through this endpoint. |
| `/actions` | GET, POST | Pro entitlement + manage | List/create corrective actions. POST accepts linked pattern IDs, hypothesis, owner, status, planned review date, and measurement-window definition. |
| `/actions/(?P<id>[a-zA-Z0-9_-]+)` | GET, PATCH | Pro entitlement; GET view, PATCH manage | Read/update action fields. Status transitions create history; before/after result carries metric version, coverage, sample sizes, and non-causal wording. |
| `/settings` | GET, PATCH | GET: view; PATCH: manage | Read/update allowlisted settings only. Never expose secrets. Validate retention, source toggles, locale and alert settings against an explicit schema. |

**Contract details still requiring decisions:** exact route permission mapping, event deletion behavior, allowed status transitions, REST pagination headers, metric date/cohort fields, retention fields, and the Free/Pro package handshake.

## Core entity response shapes (conceptual)

These are field inventories for review, not JSON Schema or frozen payloads.

### Insight event

- `id`, `event_kind`, `source_type`, `source_scope`, `occurred_at`
- `order_ref` (authorized display-safe order reference), `order_item_id`, optional `refund_id`
- optional `product_id`, `variation_id`, display labels resolved server-side
- `quantity`, `reason_id`, localized `reason_label`
- `mapping_state`, `confidence`, `created_at`, `updated_at`
- No customer identity or full order payload

### Metric value

- `key`, `value`, `numerator`, `denominator`, `unit`
- `event_kind`, `cohort_start`, `cohort_end`, `maturity_policy`
- `metric_definition_version`, `sample_size`, `coverage`, `quality_flags`
- If not eligible: `value` is null with a stable reason such as `insufficient_denominator`, `incomplete_mapping`, or `immature_cohort`.

## Threat model and controls

| Asset / threat | Example failure | Required design control |
|---|---|---|
| Insights REST data | Unauthenticated or low-privilege user reads merchant data | Per-route `permission_callback`, least-privilege capabilities, auth/nonce handling, deny by default |
| Object-level access | User changes an order ID and reads/records against an unrelated order | Load references via WooCommerce APIs; verify relationships and actor capability for every object |
| Request forgery | Logged-in admin is tricked into an unwanted mutation | REST nonce for cookie auth plus capability checks and intent-specific routes |
| Injection / stored XSS | Reason text renders as executable markup | Bound text lengths, validate enums/IDs, parameterized queries, context-aware escaping; treat imported free text as untrusted |
| SQL/data corruption | Filter values alter custom-table queries or malformed writes corrupt metrics | Strict schema validation, prepared SQL, transaction boundaries where supported, schema-versioned migrations, idempotency keys |
| Duplicate/replayed events | Refund hooks or retries create inflated counts | Unique source identity, idempotent upsert, tested repeated-delivery behavior |
| Background job abuse/failure | Repeated admin action floods Action Scheduler or jobs run forever | Capability checks at enqueue boundary, bounded batches, locks/idempotency, retry cap/backoff, observable failed/lagging state, cancellable backfill |
| Privacy leakage | Raw reason text contains a name or sensitive complaint; order ref is linkable | Minimize fields, mark raw phrase optional and bounded, retention policy, privacy exporter/eraser, no PII in logs or analytics responses |
| Cross-store leakage | Multisite query omits blog/site prefix or tenant scope | Decide multisite support explicitly; scope every query and scheduled job to current site; test isolation before claiming support |
| License downgrade | Expired Pro hides/deletes stored data or blocks Free ingestion | Preserve shared event data; disable Pro actions/analytics gracefully; never erase on expiry |
| External provider exposure | Store event text or IDs sent to AI/telemetry | No external AI or analytics transmission in V1; any future provider needs separate opt-in and privacy/security approval |
| Supply-chain / release | Bundled dependency or build output contains vulnerable/unlicensed code | Inventory licenses/dependencies; reproducible documented release process; scan and review artifacts before submission |

## Privacy lifecycle rules

- Default analytics storage contains no customer identity and no full order payload.
- Keep order/refund/item IDs only as needed to support provenance, corrections, and privacy lookup. Treat these linked IDs as personal-data-adjacent references.
- Raw free-text refund reasons are optional, length-bounded, and governed by an approved retention window. Prefer canonical reason IDs after normalization.
- Privacy export includes only the affected person’s linked insight records and merchant-provided content legally needed for export; do not expose unrelated aggregate data.
- Erasure removes or anonymizes linked identifiers and raw phrases where required. Retain aggregate-only metrics only when they cannot reasonably re-identify a person and applicable policy permits.
- Logs and error references contain event IDs and technical codes, not order/customer payloads.
- Deactivation preserves records. Uninstall behavior is disclosed and requires an explicit retain/delete choice; delete is irreversible and confirmed.
- Backups and server logs are outside direct plugin control; document their independent retention limitations.

## Official references reviewed 2026-11-06

- [WordPress custom endpoints](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/) — route callbacks and permission callbacks.
- [WordPress REST authentication](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/) — cookie auth and REST nonce behavior.
- [WordPress REST schema](https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/) — argument validation and schema.
- [WordPress nonces](https://developer.wordpress.org/apis/security/nonces/) — nonce behavior and limitations.
- [WordPress input security](https://developer.wordpress.org/apis/security/sanitizing/) — validation preferred over broad sanitization.
- [WordPress personal-data eraser](https://developer.wordpress.org/plugins/privacy/adding-personal-data-eraser-to-your-plugin/).
- [WooCommerce HPOS recipe book](https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/recipe-book/).

## Acceptance evidence to collect during implementation

For each route, test anonymous, insufficient-role, allowed-role, invalid input, boundary values, object relationship mismatch, nonce failure, and Pro entitlement states. Exercise duplicate hook delivery, repeated POST idempotency, malformed source phrases, pagination limits, timestamp/timezone boundaries, privacy export/erase, and multisite isolation if multisite is supported. These are planned verification cases; none have been run because no endpoints exist.
