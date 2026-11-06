# P6 Technical Contracts — REST and Security/Privacy

**Status:** I-03 catalog/order/manual-event routes, I-04 refund observer, and I-06 REST/React admin surfaces are implemented. Future reporting/pattern routes and runtime verification remain pending.  
**Reviewed:** 2026-11-06  
**P6 status:** Approved 2026-11-06; I-02/I-03/I-04/I-06 implementation is present. I-11 runtime verification remains pending.

This document records approved constraints and the I-06 REST surface. Listed current routes are implemented unless marked planned; static source review is not runtime verification.

## I-06 implemented routes

All routes use `/wp-json/wpessential/return-insights/v1`. Reads require `view_return_insights`; mutations and order-item lookup require `manage_return_insights`.

| Route | Behavior | Current contract |
|---|---|---|
| `GET /reasons` | Read catalog | Optional `include_inactive`; inactive entries are returned only to managers. |
| `GET /reasons/{id}` | Read one reason | Canonical ID and labels. |
| `POST /reasons` | Create reason | Label, group, active state, translated labels; unknown fields rejected. |
| `PATCH /reasons/{id}` | Update reason | Partial reason fields; unknown fields rejected. |
| `GET /orders/{id}` | Lookup order line items | WooCommerce CRUD order/item/product data; manager capability required. |
| `GET /events` | List evidence | `page`, `per_page` (default 20, max 100), optional `order_id`; stable newest-first sort and total headers. |
| `GET /events/{id}` | Read one event | Provenance plus store-local event date. |
| `POST /events` | Record manual insight | Confirmed return or exchange, order/item, quantity, active reason, local date, idempotency key; server derives product/variation IDs. |
| `PATCH /events/{id}` | Edit manual event | Event kind, quantity, reason, date. Observed refund records are immutable. |
| `DELETE /events/{id}` | Soft-delete manual event | Required audit reason; observed refund records are immutable. |
| `GET /sources` | Coverage summary | Active event count and latest date by source only; not health/error monitoring. |

The UI uses WordPress `wp.element`, `wp.components`, and `wp.apiFetch`; the registered script dependencies are scoped to the WooCommerce admin screen. apiFetch applies same-origin REST root and nonce middleware when enqueued with the bundled script handle. See [apiFetch documentation](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-api-fetch/) and [script dependency documentation](https://developer.wordpress.org/reference/functions/wp_enqueue_script/).

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

## Planned reporting and Pro route extensions

The following target routes are **not implemented in I-06**; they remain future backlog scope. Actual current endpoints are listed above.

| Route | Methods | Capability | Purpose / schema summary |
|---|---|---|---|
| `/summary` | GET | `view_return_insights` | Planned I-05: date-windowed counts and definitions/coverage; rate fields only after the approved cohort rules are implemented. |
| `/patterns` | GET | `view_return_insights` | Planned I-07: sample-aware reason/product/variation patterns and quality flags. |
| `/events` | GET, POST | GET: view; POST: manage | GET returns a paged event list and provenance. POST accepts `event_kind` (`confirmed_return` or `exchange`), `order_id`, `order_item_id`, `quantity`, `reason_id`, site-local date `occurred_at` (`YYYY-MM-DD`), and an idempotency key. I-03 stores no source phrase. Server derives product relationships. |
| `/events/{id}` | GET, PATCH, DELETE | GET: view; mutations: manage | Implemented route uses numeric event IDs. Reads are view-gated; mutations are manager-gated, and refund observations are immutable. |
| `/reasons` | GET, POST | GET: view; POST: manage | Read catalog or create a canonical reason. POST accepts a label and group; server generates a stable-safe canonical ID. PATCH can change label, group, translations, and active state without changing ID. Catalog `order` is not yet implemented. |
| `/reasons/(?P<id>[a-zA-Z0-9_-]+)` | GET, PATCH | GET: view; PATCH: manage | Update labels/translations/active state. Catalog ordering and destructive merge are not implemented; any future merge requires a separate audited flow. A future merge operation requires a separate audited flow. |
| `/sources` | GET | `view_return_insights` | Implemented basic coverage summary: active record counts and latest event date by source. Source errors, health, and retry status are not provided. |
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

## I-04 refund observer boundary

The observer uses `woocommerce_refund_created` after WooCommerce persists a refund, loads refund/order objects via `wc_get_order()`, and writes one `refund_only` event at `refund` attribution scope with a unique source key based on site and refund ID. The schema stores `refund_id`; item/product/variation IDs use zero sentinels so the refund reason is not assigned to line items. An exact active catalog label can map to a stable reason ID in memory; unmatched text becomes `unknown`, and raw phrase text is not stored. `woocommerce_refund_deleted` soft-deletes linked events. The observer does not infer physical returns. Runtime hook, HPOS/legacy, concurrency, and deletion checks remain pending.

## I-03/I-06 implementation boundary

I-03 implements reason/order/manual-event routes; I-04 adds refund observation; I-06 adds `/sources`, request schemas, strict unknown-field rejection, pagination, and the React screens described above. Manual event writes validate the order-item relationship and derive product/variation IDs server-side; deletion is soft and requires an audit reason. Current screen/API implementation was inspected statically only. Runtime permission, nonce, migration, quantity edge, HPOS/legacy, timezone, accessibility, and privacy exporter/eraser verification remain unperformed and are release-hardening work in I-11 where applicable. Optimistic conflict handling for simultaneous edits remains future work.

## I-11 acceptance evidence to collect

These are planned runtime verification cases, not completed results.

For each route, test anonymous, insufficient-role, allowed-role, invalid input, boundary values, object relationship mismatch, nonce failure, and Pro entitlement states. Exercise duplicate hook delivery, repeated POST idempotency, malformed source phrases, pagination limits, timestamp/timezone boundaries, privacy export/erase, and multisite isolation if multisite is supported. These are planned verification cases; endpoints now exist for the I-03 scope, but no runtime tests or verification have been run.
