# WPEssential Return Insights & Prevention for WooCommerce

> Pre-development planning repository. No working plugin code exists here yet.

## Product direction

Help WooCommerce merchants understand why items are returned, identify repeat product/variation issues, and track whether a corrective change helps. This is reason intelligence, not RMA/refund/exchange/carrier processing.

**Proposed tiers:** Free reason capture and basic reports. Pro proposed at $79/year for product/variation trends, reason clustering, alerts, and corrective-action tracking. Pricing and boundaries remain unapproved.

**Positioning reality:** Existing products already collect reasons and some advertise product/reason analytics. Proposed distinction is a focused prevention loop—evidence → correction → comparable measurement—not analytics alone.

## Roadmap

Planning estimates in UTC; reviewed 2026-11-06. Progress describes planning artifacts, not plugin code. No implementation phase has started.

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

**Planning artifact completion: 100%** across P0–P7. P6 is approved and implementation is authorized within the scoped backlog. Runtime plugin implementation remains 0%. Dates are estimates, not delivery promises. Keep planning progress separate from implementation progress. Update this table and change log whenever the plan changes.

## Implementation status

P6 approval is recorded. Runtime plugin implementation has not started (**0%**). I-00 technical decisions and I-01 security/privacy design are complete as planning work; I-02 plugin foundation is the next runtime package. Follow `docs/IMPLEMENTATION_BACKLOG.md` in order. The React plan uses WordPress bundled packages without JSX/build tooling, and no CLI is to be added until the plugin is final.

## Distribution plan

- **Free edition:** submit a complete, production-ready package to the WordPress.org Plugin Directory.
- **Paid Pro edition:** submit separately to the WooCommerce.com Marketplace.
- For a freemium product, Woo currently requires separate Free and Paid submissions; they may be combined on one product page after approval.
- Woo Marketplace submission also requires an approved vendor account, product/business details, test instructions, compatibility with the two latest major WordPress and WooCommerce releases, HPOS, accessibility, and required Quality Insights Toolkit checks.
- Re-evaluate version targets, licensing, listing name, pricing, vendor terms, and packages at release. The current compatibility baseline is a planning target, not proof of future marketplace approval.

Details and official source links: docs/DISTRIBUTION.md. No submission has been made.

## Current gate

P6 passed on 2026-11-06. D1–D12 are approved and recorded in docs/P6_READINESS_GATE.md. I-00/I-01 design work is complete; I-02 plugin foundation is next in docs/IMPLEMENTATION_BACKLOG.md. Keep CLI/build tooling, Composer/npm manifests, and package managers out of this repository until the plugin is final.

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
