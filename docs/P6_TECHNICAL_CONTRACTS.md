# P6 Technical Contracts — REST and Security/Privacy

**Status:** Approved contract; I-03 implements catalog, order lookup, and manual-event routes. Remaining routes and runtime schema verification are pending.  
**Reviewed:** 2026-11-06  
**P6 status:** Approved on 2026-11-06; runtime implementation remains pending.

This document defines the approved engineering boundary. Concrete JSON schemas and code are delivered in the corresponding implementation tasks.

## REST namespace and shared rules

**Approved namespace:** `wpessential/return-insights/v1`. Reserve this namespace for this plugin; version it only for incompatible API changes.

- Admin requests use the WordPress REST cookie-auth flow with a REST nonce. A nonce helps prevent CSRF; it is not a substitute for capability checks.
- Every route defines a `permission_callback`. Read and write capabilities are checked server-side on every request.
- Plugin capabilities: `view_return_insights` and `manage_return_insights`; map read to WooCommerce `view_woocommerce_reports` and write to `manage_woocommerce` for Administrator/Shop Manager, with explicit grant controls. Do not use UI visibility as authorization.
- Every request field has a schema, strict validation, and sanitization only where transformation is appropriate. Reject invalid IDs, enums, date ranges, quantities, and unknown fields.
- Use WooCommerce public CRUD APIs to verify order/refund/order-item/product references and the actor’s permitted access. Never trust client-supplied product, variation, or order relationships.
- Return only fields needed by the current screen. Never expose customer name, email, billing/shipping address, payment details, authentication data, or full order objects through the insights API.
- Use server-side pagination with deterministic ordering; cap page size (maximum 100). No unbounded event collection.
- Store timestamps in UTC and return ISO-8601 UTC values plus the site timezone identifier for display. Date-filter semantics use the store’s calendar timezone and convert explicitly at query boundaries.
- Use consistent errors: stable machine-readable `code`, translated human `message`, HTTP status, and field errors when relevant. Do not return SQL, stack traces, filesystem paths, or raw exception messages.
- Mutation endpoints require idempotency for retryable event writes and use optimistic conflict handling for concurrent edits.
- License gating is enforced server-side for Pro data/actions; expiry cannot delete or hide merchant-owned event data.

## Approved route contract

| Route | Methods | Capability | Purpose / schema summary |
|---|---|---|---|
| `/summary` | GET | `view_return_insights` | Query: `date_from`, `date_to`, `timezone`, optional `reason_id`, `product_id`, `variation_id`, `event_kind`. Response: metric version, period, event counts, explicitly named refund ratio/confirmed-return rate only when eligible, coverage, sample sizes, freshness. |
| `/patterns` | GET | `view_return_insights` | Query: shared filters, `page`, `per_page`, `orderby`, `order`. Response: reason/product/variation pattern, evidence count, source scope, comparison context, data-quality flags, pagination. |
| `/events` | GET, POST | GET: view; POST: manage | GET returns a paged event list and provenance. POST accepts `event_kind` (`confirmed_return` or `exchange`), `order_id`, `order_item_id`, `quantity`, `reason_id`, site-local date `occurred_at` (`YYYY-MM-DD`), and an idempotency key. I-03 stores no source phrase. Server derives product relationships. |
| `/events/(?P<id>[a-zA-Z0-9_-]+)` | GET, PATCH, DELETE | GET: view; mutations: manage | Read or correct permitted manual insight records. Delete is a soft/retention-aware action with audit metadata; no mutation of WooCommerce refunds/orders. Immutable source facts remain distinguishable from merchant corrections. |
| `/reasons` | GET, POST | GET: view; POST: manage | Read catalog or create a canonical reason. POST accepts a label and group; server generates a stable-safe canonical ID. PATCH can change label, group, translations, and active state without changing ID. Catalog `order` is not yet implemented. |
| `/reasons/(?P<id>[a-zA-Z0-9_-]+)` | GET, PATCH | GET: view; PATCH: manage | Update labels/order/active state. No destructive merge of history. A future merge operation requires a separate audited flow. |
| `/sources` | GET | `view_return_insights` | Source health, last success/error, event counts, mapping/coverage warnings. No arbitrary source reconfiguration through this endpoint. |
| `/actions` | GET, POST | Pro entitlement + manage | List/create corrective actions. POST accepts linked pattern IDs, hypothesis, owner, status, planned review date, and measurement-window definition. |
| `/actions/(?P<id>[a-zA-Z0-9_-]+)` | GET, PATCH | Pro entitlement; GET view, PATCH manage | Read/update action fields. Status transitions create history; before/after result carries metric version, coverage, sample sizes, and non-causal wording. |
| `/settings` | GET, PATCH | GET: view; PATCH: manage | Read/update allowlisted settings only. Never expose secrets. Validate retention, source toggles, locale and alert settings against an explicit schema. |

**Approved defaults for implementation:** view vs manage capabilities as above; manual event delete is soft-delete with audit reason; action status sequence proposed → in progress → monitoring → completed/paused; collection responses use `X-WP-Total` and `X-WP-TotalPages`; metric cohort maturity defaults to 90 days; retention is in `DATA_MODEL.md`; Free core plus Pro add-on handshake requires matching major schema version. Refine field-level JSON schemas in I-06 without changing these boundaries.

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
| Cross-site leakage | Multisite query omits site scope | V1 supports per-site activation only; scope every query and scheduled job to the current site; no network-wide activation; test site isolation |
| License downgrade | Expired Pro hides/deletes stored data or blocks Free ingestion | Preserve shared event data; disable Pro actions/analytics gracefully; never erase on expiry |
| External provider exposure | Store event text or IDs sent to AI/telemetry | No external AI or analytics transmission in V1; any future provider needs separate opt-in and privacy/security approval |
| Supply-chain / release | Bundled dependency or build output contains vulnerable/unlicensed code | Inventory licenses/dependencies; reproducible documented release process; scan and review artifacts before submission |

## Privacy lifecycle rules

- Default analytics storage contains no customer identity and no full order payload.
- Keep order/refund/item IDs only as needed to support provenance, corrections, and privacy lookup. Treat these linked IDs as personal-data-adjacent references.
- Raw free-text refund reasons are off by default; if merchant enables storage, length limit is 500 characters and purge after 90 days. Prefer canonical reason IDs after normalization.
- Privacy export includes only the affected person’s linked insight records and merchant-provided content legally needed for export; do not expose unrelated aggregate data.
- Erasure removes or anonymizes linked identifiers and raw phrases where required. Retain aggregate-only metrics only when they cannot reasonably re-identify a person and applicable policy permits.
- Logs and error references contain event IDs and technical codes, not order/customer payloads.
- Deactivation preserves records. Uninstall defaults to retaining records; provide an explicit, confirmed delete-data option and clearly explain its irreversible effect.
- Backups and server logs are outside direct plugin control; document their independent retention limitations.

## Official references reviewed 2026-11-06

- [WordPress custom endpoints](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/) — route callbacks and permission callbacks.
- [WordPress REST authentication](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/) — cookie auth and REST nonce behavior.
- [WordPress REST schema](https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/) — argument validation and schema.
- [WordPress nonces](https://developer.wordpress.org/apis/security/nonces/) — nonce behavior and limitations.
- [WordPress input security](https://developer.wordpress.org/apis/security/sanitizing/) — validation preferred over broad sanitization.
- [WordPress personal-data eraser](https://developer.wordpress.org/plugins/privacy/adding-personal-data-eraser-to-your-plugin/).
- [WooCommerce HPOS recipe book](https://developer.woocommerce.com/docs/features/orders/high-performance-order-storage/recipe-book/).
- [WordPress JavaScript without a build process](https://developer.wordpress.org/block-editor/getting-started/fundamentals/javascript-in-the-block-editor/) — access packages through the `wp` global when opting out of a build process.

## I-03 implementation boundary

Implemented routes: `GET/POST /reasons`, `GET/PATCH /reasons/{id}`, `GET /orders/{id}`, `GET/POST /events`, `GET/PATCH/DELETE /events/{id}`. Every route has a capability callback. Order lookup returns only order number and line-item/product labels/IDs/ordered quantity; no customer identity is returned. Event writes are manual-only, validate that the order item belongs to the order, derive product/variation IDs server-side, convert the site-local calendar date to UTC, and use a unique source hash for retries. Manual deletion is soft and requires an audit reason. The UI, JSON Schema definitions/unknown-field rejection, strict concurrent-edit handling, explicit remaining-quantity guard, runtime checks, and privacy exporter/eraser are not implemented yet.

## Acceptance evidence to collect during implementation

For each route, test anonymous, insufficient-role, allowed-role, invalid input, boundary values, object relationship mismatch, nonce failure, and Pro entitlement states. Exercise duplicate hook delivery, repeated POST idempotency, malformed source phrases, pagination limits, timestamp/timezone boundaries, privacy export/erase, and multisite isolation if multisite is supported. These are planned verification cases; none have been run because no endpoints exist.
