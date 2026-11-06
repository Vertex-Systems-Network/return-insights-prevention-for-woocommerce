# WPEssential Return Insights & Prevention for WooCommerce

> Early implementation repository. P6 is complete. I-02 foundation, I-03 catalog/manual-capture REST data layer, I-04 native refund observer, and I-06 REST/React admin surfaces are implemented. I-05 Free reporting and I-02/I-03/I-04/I-06 are implemented. Pro insights and runtime/release verification remain.

## Product direction

Help WooCommerce merchants understand why items are returned, identify repeat product/variation issues, and track whether a corrective change helps. This is reason intelligence, not RMA/refund/exchange/carrier processing.

**Proposed tiers:** Free reason capture and basic reports. Pro proposed at $79/year for product/variation trends, reason clustering, alerts, and corrective-action tracking. Pricing and boundaries remain unapproved.

**Positioning reality:** Existing products already collect reasons and some advertise product/reason analytics. Proposed distinction is a focused prevention loop—evidence → correction → comparable measurement—not analytics alone.

## Roadmap

Planning estimates in UTC; reviewed 2026-11-06. P0–P7 track planning artifacts; runtime work packages are tracked separately below.

| Phase | Outcome | Status | Progress | Start | Target end |
|---|---|---|---|---|---|
| P0 Repository foundation | Product brief, scope guardrails, AI entry points | Complete | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| P1 Market/settings and platform research | Competitor workflows, config options, platform evidence | Complete | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| P2 Product scope and options bank | Capture model and deduplicated capability/system inventory | Approved at P6 | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| P3 Data-flow and engineering blueprint | Data model, attribution, metrics, privacy, performance | Blueprint complete; review/implementation validation pending | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| P4 React admin UX specification | Screen map, interaction and accessibility | Blueprint complete; visual design/API review pending | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| P5 Compatibility/release baseline | PHP, WP, WC, WPML, Polylang requirements | Planning baseline complete; implementation/release matrix pending | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| P6 Pre-development readiness gate | Resolve decisions and approve implementation scope | Complete; owner approval recorded | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| P7 Distribution and submission plan | WordPress.org Free + WooCommerce.com Paid tracks | Planning complete; actual submission blocked by release | ██████████ 100% | 2026-11-06 | 2026-11-06 |

**Planning artifact completion: 100%** across P0–P7. P6 is approved and implementation is authorized within the scoped backlog. Runtime work packages: 6/11 implemented (**55%**). Dates are estimates, not delivery promises. Keep planning progress separate from implementation progress. Update this table and change log whenever the plan changes.

## Implementation status

P6 approval is recorded. I-02 foundation, I-03 catalog/manual-capture API, I-04 refund observation, I-05 Free reports, I-06 REST/React admin, and I-07 Pro product/variation cohort insights are implemented (6/11, **55%**). Free reports provide date-filtered event/reason/quantity/source counts and item-scoped product/category totals. Pro patterns compare mature purchase cohorts, use exact item-level confirmed-return attribution, and suppress rates for incomplete mapping, fewer than 20 sold units, missing denominators, and returns above sold quantity. Runtime, visual, accessibility, compatibility, and commercial entitlement verification remain for I-11. React uses WordPress bundled packages without JSX/build tooling; no CLI is to be added until the plugin is final.

## Distribution plan

- **Free edition:** submit a complete, production-ready package to the WordPress.org Plugin Directory.
- **Paid Pro edition:** submit separately to the WooCommerce.com Marketplace.
- For a freemium product, Woo currently requires separate Free and Paid submissions; they may be combined on one product page after approval.
- Woo Marketplace submission also requires an approved vendor account, product/business details, test instructions, compatibility with the two latest major WordPress and WooCommerce releases, HPOS, accessibility, and required Quality Insights Toolkit checks.
- Re-evaluate version targets, licensing, listing name, pricing, vendor terms, and packages at release. The current compatibility baseline is a planning target, not proof of future marketplace approval.

Details and official source links: docs/DISTRIBUTION.md. No submission has been made.

## Runtime work-package dashboard

| Package | Status | Progress | Start | End |
|---|---|---:|---|---|
| I-02 Foundation | Complete | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| I-03 Catalog/manual capture API | Complete | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| I-04 Refund observation | Complete | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| I-06 REST + React admin | Implemented; I-11 verification pending | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| I-05 Free reports | Implemented; I-11 verification pending | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| I-07 Pro product/variation patterns | Implemented; entitlement provider + I-11 verification pending | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| I-08 Pro clustering and alerts | In progress; cluster and weekly alert backend/REST implemented; React review UI and alert coverage hardening pending | ██████░░░░ 55% | 2026-11-06 | 2026-11-10 |

Package progress means implementation scope is present; it does not mean runtime/release acceptance is verified. See `docs/IMPLEMENTATION_BACKLOG.md` for detailed evidence.

## Current gate

P6 passed on 2026-11-06. D1–D12 are approved and recorded in docs/P6_READINESS_GATE.md. I-02, I-03, I-04, I-05, I-06, and I-07 are implemented. I-08 Pro clustering and alerts are in progress (55%): deterministic clusters, reversible assignments, weekly Pro evaluation, threshold checks, stable deduplication, and alert list/dismiss/restore endpoints are implemented. React review UI, complete candidate coverage beyond the pattern endpoint’s top-20 rows, persistent audit history, and runtime verification remain. Keep CLI/build tooling, Composer/npm manifests, and package managers out of this repository until the plugin is final.

## Plan files

- docs/PRODUCT_SPEC.md — vision, scope, capture model, tiers, decisions.
- docs/MARKET_RESEARCH.md — competitor evidence, settings, and positioning.
- docs/OPTIONS_BANK.md — unique capabilities, systems, competitor-option disposition.
- docs/ARCHITECTURE_DATA_FLOW.md — data lifecycle and boundaries.
- docs/DATA_MODEL.md — logical schema, idempotency, migrations, privacy, query plan.
- docs/COMPATIBILITY.md — platform baselines and WPML/Polylang.
- docs/REACT_UX.md — React admin navigation, screen specs, states, and REST contract outline.
- docs/UX_FLOWS.md — merchant journeys and UX acceptance criteria.
- docs/DISTRIBUTION.md — WordPress.org and Woo Marketplace submission tracks.
- docs/P6_READINESS_GATE.md — owner decisions and implementation entry criteria.
- docs/P6_TECHNICAL_CONTRACTS.md — approved REST contract and security/privacy threat controls.
- docs/IMPLEMENTATION_BACKLOG.md — approved dependency-ordered implementation work.
- docs/AI_BACKLOG.md — agent tasks and provider handoff.
- AGENTS.md — canonical project instructions.
- CLAUDE.md, GEMINI.md, .cursor/rules/project.mdc, .github/copilot-instructions.md — provider entry points.

## Non-negotiable scope

1. Prevention insights, not return-request processing.
2. Free: structured reason collection and basic reports.
3. Pro proposal: product/variation trends, clustering, alerts, corrective-action tracking.
4. React-based, polished, accessible wp-admin interface.
5. HPOS-safe WooCommerce integration.
6. WPML and Polylang behavior specified and tested; neither is a hard dependency.
7. P6 is passed; implementation follows the approved backlog. No CLI/build tooling in this repo until the plugin is final.
8. AI agents read this README and AGENTS.md, make scoped changes, and update the dashboard in the same update.

## Research snapshot

Dated platform review (2026-11-06): WordPress 7.1.4 and WooCommerce 11.2.2 are the latest releases listed in the official release archives. Planned support matrix targets WordPress 7.1/7.0 series and WooCommerce 11.2/11.1 series, with PHP 8.3+, MySQL 8.0+ or MariaDB 10.11+, HTTPS. Woo Marketplace requires the two latest major WP and WC release series; refresh these moving targets before implementation and again at submission. WPML and Polylang are optional integrations. See docs/COMPATIBILITY.md for evidence and caveats.

Recommended MVP capture model: observe native WooCommerce refund records and allow per-item admin insight entry for non-refund returns. A refund is not proof that an item physically returned, and an order-level reason must not be guessed onto every line item.

P3 architecture specifies indexed custom event storage, source-level idempotency, bounded background aggregation, separate return/refund metrics, and privacy export/erasure. See docs/ARCHITECTURE_DATA_FLOW.md and docs/DATA_MODEL.md.

## Change log

- 2026-11-06: Completed product scope/options plan and proposed WooCommerce refund observation plus per-item admin capture.
- 2026-11-06: Added separate WordPress.org Free and WooCommerce.com Paid submission plan.
- 2026-11-06: Completed P3 logical data model, HPOS-safe ingestion, metric boundaries, privacy lifecycle, and performance plan. Added official design source links; no plugin implementation or CLI tooling added.
- 2026-11-06: Completed P4 React admin UX blueprint, merchant flow acceptance criteria, accessibility/state requirements, tier boundary, and REST contract outline. Overall planning readiness 86%; no plugin implementation or CLI tooling added.
- 2026-11-06: Completed P5 compatibility policy with dated WordPress 7.1/7.0 and WooCommerce 11.2/11.1 targets, PHP 8.3+, database floor, optional WPML/Polylang requirements, and release-time recheck rule. Planning artifact completion 88%; no plugin implementation or CLI tooling added.
- 2026-11-06: Prepared P6 decision register and ordered I-00–I-12 implementation backlog; aligned distribution guidance with the dated compatibility baseline.
- 2026-11-06: Added REST payload/permission contracts, privacy/security threat controls, approved no-build React approach, and Free-core/Pro-add-on packaging decision.
- 2026-11-06: Product owner delegated approval; D1–D12 approved, P6 marked complete, and planning artifact completion reached 100%. Runtime implementation is 0%; CLI/build tooling remains prohibited until the plugin is final.
- 2026-11-06: Implemented I-02 foundation files: plugin header/bootstrap, dependency checks, per-site activation guard, role capabilities, WooCommerce admin entry, non-destructive deactivation, and uninstall capability cleanup. Runtime work progress is 1/11 packages (9%); no runtime verification performed.
- 2026-11-06: Implemented I-03 reason catalog and manual-capture REST/data layer: initial dbDelta schema migration, canonical reason keys, custom reasons, protected reason/order/event routes, per-item validation, manual event idempotency, edit and audited soft-delete. Runtime work progress was 2/11 packages (18%). React capture UI, route runtime checks, and broader permission/accessibility verification remain; no CLI/build tools or tests were added.
- 2026-11-06: Implemented I-04 native refund observation with the WooCommerce post-save refund hook, CRUD object reads, schema v2 refund linkage, idempotent refund-scope event upsert, exact catalog-label mapping without retaining free text, and soft deletion on refund deletion. No physical-return inference, CLI/build tooling, or runtime tests were added.
- 2026-11-06: Implemented I-06 REST schemas and no-build React admin surfaces for overview counts, event history, manual item capture/edit/audited removal, reason catalog, and source counts/latest dates. No runtime tests, CLI, or build tooling were added.
- 2026-11-06: Implemented I-07 mature product/variation cohorts, bounded WooCommerce CRUD order reads, exact item/reason attribution, low-sample and incomplete-data rate suppression, fail-closed Pro entitlement, and the React patterns screen. Static source inspection only; runtime verification and a production entitlement provider remain.
- 2026-11-06: Opened I-08 with explicit alert/clustering acceptance criteria and a dated runtime-dashboard row.
- 2026-11-06: Added the deterministic reason-cluster service, reversible reason assignments, weekly Pro alert evaluation, threshold checks, stable cohort-independent dedupe keys, and alert read/dismiss/restore REST endpoints. React review UI, complete candidate coverage, append-only audit history, and runtime validation remain. Local workspace lacks PHP, WP-CLI, MySQL, and Docker. A test ZIP was built from the current main branch; submission on the authorized WP test site was blocked by the browser URL security policy, so install/activation was not completed.
