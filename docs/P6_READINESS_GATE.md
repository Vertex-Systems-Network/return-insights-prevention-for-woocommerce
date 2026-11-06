# P6 — Pre-Development Readiness Gate

**Status:** Approved; P6 readiness gate passed.  
**Reviewed:** 2026-11-06  
**Implementation status:** P6 authorizes scoped runtime implementation. No plugin runtime code or CLI was added in this approval update; the separate no-CLI-until-final constraint remains active.

The product owner explicitly delegated approval and final choices in ChatGPT on 2026-11-06 (“Aproval ha jo krna ha tumne karna ha”). The following defaults are recorded as approved. Reopen a decision only through a documented change request.

## Recommended V1 decisions

| ID | Decision | Recommended V1 default | Current evidence / remaining choice |
|---|---|---|---|
| D1 | Capture scope | Observe native WooCommerce refund records and permit authorized manual per-item return-insight entry. No customer return portal or RMA workflow in V1. | Matches `PRODUCT_SPEC.md` and the prevention-only scope. Approve or revise. |
| D2 | Event meaning | Keep confirmed physical return, refund-only, exchange, warranty/repair, cancellation, and unknown as distinct event kinds. Native refund records create refund-only evidence; do not infer a physical return. | Matches data/metric boundaries in P3. Approve or revise event kinds and transition rules. |
| D3 | Reasons | Seed keys: fit/size; quality/defect; damage; appearance/color; not as described; wrong item/variation; missing part/accessory; delivery delay; delivery damage; duplicate order; changed mind; ordered by mistake; other; unknown. Labels are editable/translatable; stable keys remain immutable. | Approved default taxonomy; additions/retirements are versioned and never rewrite event history. |
| D4 | Rates | Confirmed return rate = unique confirmed returned quantity / eligible sold quantity for the same product/variation purchase cohort. Cohort matures 90 days after order date by default; merchant may select 30/60/90/120/180 days. Refund quantity ratio is separate. Suppress rates when mapping, denominator, cohort, or maturity is incomplete. | Approved; 90-day default is a product heuristic, not a legal return period or statistical guarantee. API must show denominator, window, metric version, and coverage. |
| D5 | Alerts | Pro in-product alerts only in V1. Initial heuristic: at least 5 confirmed returned units and 80% item mapping in a mature 90-day cohort, at least 20 eligible sold units in both current and comparison cohorts, and the pattern persists across two weekly refreshes. Deduplicate and show why an alert fired. | Approved as configurable launch defaults; label as operational heuristic, never statistical significance. No email/push until separately specified. |
| D6 | Clustering / AI | V1 uses local, deterministic normalization/clustering with explainable membership, merchant correction, and reversible results. External AI is off and excluded from V1. | Safer privacy default; matches `AGENTS.md`. Revisit only through separate consent, data-minimization, and vendor review. |
| D7 | Tiers / price / retention | Free: capture, catalog, source/coverage, basic reason counts/filters, privacy export/erase. Pro: product/variation trends, clustering, alerts, actions. $79/year remains a proposed launch price and must be market-validated before listing. Keep canonical events 24 months by default; optional raw source phrase is off by default and, if enabled, purged after 90 days. Merchant can shorten/extend retention. | Approved product defaults; these retention values are product policy proposals, not legal advice. License expiry never hides/deletes event data. |
| D8 | Integrations | V1 directly supports WooCommerce native refund evidence and manual insight entry. Defer third-party RMA adapters until official API feasibility, demand, data quality, and privacy are reviewed per adapter. | Current research does not justify promising broad adapter compatibility. |
| D9 | Storage / processing | Keep settings and reason catalog in WP options where appropriate; use versioned indexed event/action tables for growing data; queue bounded aggregation through Action Scheduler. Use WooCommerce CRUD and public APIs for order/product data. | Matches P3 proposal. Validate volume assumptions, migrations, and indexes before implementation. |
| D10 | Free/Pro packaging | One repository produces two independent ZIPs: a complete Free core for WordPress.org and a Pro add-on for WooCommerce.com that requires Free. Free works independently; Pro stores only Pro-specific actions/alerts and reuses the Free event store. Version handshake fails safely; license expiry never deletes data. | Approved architecture. Woo Freemium guidance supports two submissions and an additional plugin for premium functionality; verify final vendor requirements at submission. |
| D11 | Compatibility | Plan PHP 8.3+, rolling support for the current and previous required WordPress/WooCommerce release series at submission, HPOS and legacy storage, optional WPML/Polylang integrations. Refresh versions before coding and submission. | Dated matrix and official evidence in `COMPATIBILITY.md`; runtime test matrix remains unrun. |
| D12 | Admin UI / build | Use WordPress-provided React via the `wp.element` global, WordPress components via `wp.components`, and REST requests via `wp.apiFetch`; enqueue registered package handles. Write browser-ready JavaScript without JSX/transpilation and no npm/CLI/build tooling in the repository. | Approved no-build approach. Reassess only if maintainability/accessibility review shows it cannot meet the UX bar; any change must preserve the no-CLI-until-final constraint. |

## Implementation entry criteria

P6 passed on 2026-11-06 after owner delegation and recording approved decisions D1–D12. These constraints govern implementation:

- Product remains prevention intelligence, not an RMA/refund processing system.
- Refund observations are never silently presented as confirmed physical returns.
- Counts, refund ratios, and confirmed-return rates remain distinct and show scope, denominator, time window, and coverage.
- Customer identity and full order payload are not duplicated into plugin analytics storage.
- No external AI service receives store data by default; V1 excludes external AI.
- Server-side capability checks, input validation, REST permissions, privacy exporter/eraser, retention, uninstall, and migration behavior are specified before their implementation tasks.
- HPOS and legacy order storage use public WooCommerce CRUD/API surfaces.
- WPML and Polylang remain optional; translated labels cannot split canonical reason identity.
- Free and Paid editions have distinct responsibilities and a documented update/dependency model before release packaging.
- React uses WordPress-bundled globals and no build tool or CLI is added to the repository until the plugin is final.
- Every agent task follows `AGENTS.md`, names files and acceptance evidence, and updates README progress in the same update.
- No runtime plugin code, CLI, Composer/npm manifest, or build script is authorized until this gate has explicit owner approval.

## Gate review checklist

| Review area | Evidence | Gate status |
|---|---|---|
| Product scope and capture model | `PRODUCT_SPEC.md`, `OPTIONS_BANK.md` | D1–D3 approved; implementation acceptance evidence pending |
| Data model, privacy, event boundaries | `ARCHITECTURE_DATA_FLOW.md`, `DATA_MODEL.md`, `P6_TECHNICAL_CONTRACTS.md` | D4–D9 approved; implementation and migration evidence pending |
| React UX and user journeys | `REACT_UX.md`, `UX_FLOWS.md` | UX contract approved; UI implementation/accessibility validation pending |
| Compatibility | `COMPATIBILITY.md` | Dated baseline ready; tests await implementation |
| Distribution | `DISTRIBUTION.md` | D10 approved; packages/listings still require release readiness and submission |
| Security / threat model | `P6_TECHNICAL_CONTRACTS.md` | Design controls approved; verification remains an implementation/release task |
| REST contract and route controls | `P6_TECHNICAL_CONTRACTS.md` | Approved design contract; concrete JSON schemas finalized per I-06 |
| Implementation sequence | `IMPLEMENTATION_BACKLOG.md` | I-00/I-01 design complete; runtime tasks authorized in dependency order |
| Owner decision | D1–D12 | Approved by owner delegation on 2026-11-06 |

## Approval record

- **Decision:** Approved; use D1–D12 defaults recorded above.
- **Approver:** Product owner, via explicit delegation to the project agent in ChatGPT.
- **Approved/revised decision IDs:** D1–D12 approved; D10 and D12 finalized by delegated judgment.
- **Date:** 2026-11-06.
- **Scope/version approved:** V1 pre-development scope and I-00–I-12 implementation backlog.

P6 is complete. Runtime work may proceed in dependency order. CLI/build tooling remains prohibited in this repository until the plugin is final.
