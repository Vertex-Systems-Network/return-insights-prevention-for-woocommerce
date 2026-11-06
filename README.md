
# WPEssential Return Insights & Prevention for WooCommerce

> Pre-development planning repository. No working plugin code exists here yet.

## Product direction

Help WooCommerce merchants understand why items are returned, identify repeat product or variation issues, and track whether a corrective change helps. The plugin records and analyzes reasons; it does not replace RMA, refunds, exchanges, or carrier workflows.

**Proposed tiers:** Free reason capture and basic reports. Pro proposed at $79/year for product/variation trends, reason clustering, alerts, and corrective-action tracking. Tier limits and pricing remain unapproved.

**Positioning reality:** Existing products already collect return reasons and some advertise product/reason analytics. The proposed distinction is a focused prevention loop—evidence → corrective action → comparable measurement—not analytics alone.

## Roadmap

Planning estimates in UTC; last reviewed 2026-11-06. Percentages describe planning artifacts only, not plugin code. No implementation phase has started.

| Phase | Outcome | Status | Progress | Start | Target end |
|---|---|---|---|---|---|
| P0 Repository foundation | Product brief, scope guardrails, AI entry points | Complete | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| P1 Market and platform research | Competitor evidence and platform baselines | Complete | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| P2 Product scope and options bank | Deduplicated capability/system inventory | Draft ready for review | █████████░ 90% | 2026-11-06 | 2026-11-07 |
| P3 Data-flow and engineering blueprint | Storage, capture, privacy, reporting boundaries | Draft ready for review | ████████░░ 80% | 2026-11-06 | 2026-11-08 |
| P4 React admin UX specification | Screen map, interaction and accessibility | In progress | ██████░░░░ 60% | 2026-11-06 | 2026-11-09 |
| P5 Compatibility and release requirements | PHP, WP, WC, WPML, Polylang matrix | Draft ready for review | ████████░░ 75% | 2026-11-06 | 2026-11-08 |
| P6 Pre-development readiness gate | Resolve decisions and approve implementation scope | Not started | ░░░░░░░░░░ 0% | 2026-11-09 | 2026-11-10 |

**Overall planning readiness: 72%**, equally weighted across these planning phases. Dates are estimates, not commitments. Keep planning progress separate from implementation progress. Update this table and the change log whenever the plan changes.

## Current gate

Before implementation, resolve the open decisions in Product Specification, review the Options Bank, and approve Compatibility. Do not add plugin code, CLI tooling, Composer/npm manifests, or build scripts before P6 approval.

## Plan files

- docs/PRODUCT_SPEC.md — vision, scope, tiers, decisions.
- docs/MARKET_RESEARCH.md — competitor evidence and positioning.
- docs/OPTIONS_BANK.md — unique capabilities, systems, tier ownership.
- docs/ARCHITECTURE_DATA_FLOW.md — data lifecycle and system boundaries.
- docs/COMPATIBILITY.md — runtime baselines and WPML/Polylang strategy.
- docs/REACT_UX.md — React admin navigation and design bar.
- docs/AI_BACKLOG.md — agent tasks and provider handoff.
- AGENTS.md — canonical project instructions.
- CLAUDE.md, GEMINI.md, .cursor/rules/project.mdc, .github/copilot-instructions.md — provider-specific entry points.

## Non-negotiable scope

1. Prevention insights, not return-request processing.
2. Free: structured reason collection and basic reports.
3. Pro proposal: product/variation trends, clustering, alerts, corrective-action tracking.
4. React-based, polished, accessible wp-admin interface.
5. HPOS-safe WooCommerce integration.
6. WPML and Polylang behavior specified and tested; neither is a hard dependency.
7. No CLI or implementation tooling before the readiness gate.
8. AI agents read this README and AGENTS.md, make scoped changes, and update the dashboard in the same update.

## Research snapshot

Reviewed official sources on 2026-11-06. WooCommerce server recommendations for 10.8+ list WordPress 6.9+, PHP 8.3+, MySQL 8.0+ or MariaDB 10.6+, HTTPS, and 256 MB WP memory. WordPress independently recommends PHP 8.3+, MySQL 8.0+ or MariaDB 10.11+, and HTTPS. These are candidate platform baselines, not final plugin header values. Details and links are in docs/COMPATIBILITY.md.

Competitor review found reason collection, RMA workflows, and analytics already in-market. Do not claim analytics alone is unique. Details and source links are in docs/MARKET_RESEARCH.md.

## Change log

- 2026-11-06: Added planning baseline, competitor research, deduplicated options bank, data-flow proposal, React UX direction, compatibility matrix, and AI-agent entry points. No implementation or CLI tooling added.
