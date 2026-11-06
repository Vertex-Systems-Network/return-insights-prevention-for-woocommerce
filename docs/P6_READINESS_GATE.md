# P6 — Pre-Development Readiness Gate

**Status:** Recommendations consolidated; explicit product-owner approval pending.  
**Reviewed:** 2026-11-06  
**Implementation status:** Not authorized by this document. No plugin runtime code, CLI, package manifest, or build tooling is added.

This gate turns the planning documents into concrete defaults and an ordered implementation scope. Approval is a product-owner decision; an AI agent must not mark it approved on the owner's behalf.

## Recommended V1 decisions

| ID | Decision | Recommended V1 default | Current evidence / remaining choice |
|---|---|---|---|
| D1 | Capture scope | Observe native WooCommerce refund records and permit authorized manual per-item return-insight entry. No customer return portal or RMA workflow in V1. | Matches `PRODUCT_SPEC.md` and the prevention-only scope. Approve or revise. |
| D2 | Event meaning | Keep confirmed physical return, refund-only, exchange, warranty/repair, cancellation, and unknown as distinct event kinds. Native refund records create refund-only evidence; do not infer a physical return. | Matches data/metric boundaries in P3. Approve or revise event kinds and transition rules. |
| D3 | Reasons | Start with editable canonical reason keys grouped into product/fit/quality/listing issues and order/fulfilment issues. Keep display and translated labels separate from stable keys; include other/unknown. | Exact seed list can be finalized in a follow-up spec after approval. Approve taxonomy principle or specify additions. |
| D4 | Rates | Keep counts as the default everywhere. Show a confirmed-return rate only with a confirmed-return numerator and eligible sold-unit denominator over a stated, comparable cohort/window. Show refund ratio separately. Suppress a rate when attribution, cohort, or denominator is incomplete. | Product-level denominator is planned; exact maturity window and cohort rules require approval before rate implementation. |
| D5 | Alerts | Pro alerts only. Require a minimum sample and coverage, deduplicate repeated notices, and provide a reason/evidence trail. Keep thresholds configurable and disabled until a baseline can be evaluated. | Avoid inventing a universal statistically meaningful threshold before merchant data exists. Choose initial threshold method during API/analytics design. |
| D6 | Clustering / AI | V1 uses local, deterministic normalization/clustering with explainable membership, merchant correction, and reversible results. External AI is off and excluded from V1. | Safer privacy default; matches `AGENTS.md`. Revisit only through separate consent, data-minimization, and vendor review. |
| D7 | Tiers / price | Free: reason capture, catalog, source/data coverage, basic reason counts and filters. Pro proposal: product/variation trends, clustering, alerts, corrective actions. Keep $79/year as an unapproved pricing proposal; do not hard-code price. Provide data export and retention controls without using license expiry to hide or delete data. | Confirm the proposed tier boundary and price; define export fields/retention in implementation spec. |
| D8 | Integrations | V1 directly supports WooCommerce native refund evidence and manual insight entry. Defer third-party RMA adapters until official API feasibility, demand, data quality, and privacy are reviewed per adapter. | Current research does not justify promising broad adapter compatibility. |
| D9 | Storage / processing | Keep settings and reason catalog in WP options where appropriate; use versioned indexed event/action tables for growing data; queue bounded aggregation through Action Scheduler. Use WooCommerce CRUD and public APIs for order/product data. | Matches P3 proposal. Validate volume assumptions, migrations, and indexes before implementation. |
| D10 | Free/Pro packaging | Plan separate Free and Paid submission artifacts for WordPress.org and WooCommerce.com; keep the exact shared-code/add-on dependency model open until a policy and update-flow review confirms it. Free must remain usable independently. | Distribution plan requires separate submissions; package architecture still needs product-owner decision. |
| D11 | Compatibility | Plan PHP 8.3+, rolling support for the current and previous required WordPress/WooCommerce release series at submission, HPOS and legacy storage, optional WPML/Polylang integrations. Refresh versions before coding and submission. | Dated matrix and official evidence in `COMPATIBILITY.md`; runtime test matrix remains unrun. |
| D12 | Admin UI / build | React admin UX following WordPress/Woo patterns. Ship compiled assets. Do not add CLI/build tooling to this repository until the plugin is final, per project direction; select a compliant build/release process before React implementation. | UX is specified; toolchain and distribution of build artifacts need approval without violating the no-CLI constraint. |

## Implementation entry criteria

The P6 gate passes only after the owner approves/revises D1–D12 and these implementation constraints are recorded:

- Product remains prevention intelligence, not an RMA/refund processing system.
- Refund observations are never silently presented as confirmed physical returns.
- Counts, refund ratios, and confirmed-return rates remain distinct and show scope, denominator, time window, and coverage.
- Customer identity and full order payload are not duplicated into plugin analytics storage.
- No external AI service receives store data by default; V1 excludes external AI.
- Server-side capability checks, input validation, REST permissions, privacy exporter/eraser, retention, uninstall, and migration behavior are specified before their implementation tasks.
- HPOS and legacy order storage use public WooCommerce CRUD/API surfaces.
- WPML and Polylang remain optional; translated labels cannot split canonical reason identity.
- Free and Paid editions have distinct responsibilities and a documented update/dependency model before release packaging.
- The agreed React build/distribution approach honors the no-CLI-in-repository-until-final constraint.
- Every agent task follows `AGENTS.md`, names files and acceptance evidence, and updates README progress in the same update.
- No runtime plugin code, CLI, Composer/npm manifest, or build script is authorized until this gate has explicit owner approval.

## Gate review checklist

| Review area | Evidence | Gate status |
|---|---|---|
| Product scope and capture model | `PRODUCT_SPEC.md`, `OPTIONS_BANK.md` | Recommendation ready; owner approval pending |
| Data model, privacy, event boundaries | `ARCHITECTURE_DATA_FLOW.md`, `DATA_MODEL.md` | Blueprint ready; detailed retention/schema validation remains |
| React UX and user journeys | `REACT_UX.md`, `UX_FLOWS.md` | Blueprint ready; visual/API review remains |
| Compatibility | `COMPATIBILITY.md` | Dated baseline ready; tests await implementation |
| Distribution | `DISTRIBUTION.md` | Separate submission tracks planned; no packages/listings created |
| Security / threat model | This gate + implementation tasks | Baseline constraints set; detailed threat review remains before capture/API coding |
| Implementation sequence | `IMPLEMENTATION_BACKLOG.md` | Ordered; blocked pending gate approval |
| Owner decision | D1–D12 | Pending |

## Approval record

- **Decision:** Pending.
- **Approver:** Product owner.
- **Approved/revised decision IDs:** Pending.
- **Date:** Pending.
- **Scope/version approved:** Pending.

When the owner approves, record the decision IDs and date here, update the README phase status, and only then begin the first authorized implementation task. Approval does not waive the separate instruction to keep CLI/build tooling out of the repository until the plugin is final.
