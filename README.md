
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
| P2 Product scope and options bank | Deduplicated capability/system inventory | Draft ready for review | ██████████ 95% | 2026-11-06 | 2026-11-07 |
| P3 Data-flow and engineering blueprint | Storage, capture, privacy, reporting boundaries | Draft ready for review | ████████░░ 80% | 2026-11-06 | 2026-11-08 |
| P4 React admin UX specification | Screen map, interaction and accessibility | In progress | ██████░░░░ 60% | 2026-11-06 | 2026-11-09 |
| P5 Compatibility/release baseline | PHP, WP, WC, WPML, Polylang requirements | Planning complete; release tests pending | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| P6 Pre-development readiness gate | Resolve decisions and approve implementation scope | Not started | ░░░░░░░░░░ 0% | 2026-11-09 | 2026-11-10 |

**Overall planning readiness: 76%**, equally weighted across phases. Dates are estimates, not delivery promises. Keep planning progress separate from implementation progress. Update this table and the change log whenever the plan changes.

## Current gate

Before implementation, resolve Product Specification decisions, review the Options Bank, and approve Compatibility. Do not add plugin code, CLI tooling, Composer/npm manifests, or build scripts before P6 approval.

## Plan files

- docs/PRODUCT_SPEC.md — vision, scope, tiers, decisions.
- docs/MARKET_RESEARCH.md — competitor evidence, settings, and positioning.
- docs/OPTIONS_BANK.md — unique capabilities, systems, and competitor-option disposition.
- docs/ARCHITECTURE_DATA_FLOW.md — data lifecycle and boundaries.
- docs/COMPATIBILITY.md — platform baselines and WPML/Polylang.
- docs/REACT_UX.md — React admin navigation and design bar.
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
7. No CLI or implementation tooling before the readiness gate.
8. AI agents read this README and AGENTS.md, make scoped changes, and update the dashboard in the same update.

## Research snapshot

The selected V1 planning baseline is WordPress 6.9+, WooCommerce 10.8+, PHP 8.3+, and MySQL 8.0+ or MariaDB 10.6+, over HTTPS. WPML and Polylang are optional integrations with explicit test requirements. These requirements are grounded in current official recommendations; release claims still require test evidence. See docs/COMPATIBILITY.md.

Competitor review confirms mature RMA workflows, reason capture, eligibility/exclusion controls, notifications, analytics, and return-processing AI tools in-market. These operational controls are intentionally excluded; see docs/MARKET_RESEARCH.md and docs/OPTIONS_BANK.md.

## Change log

- 2026-11-06: Created the planning baseline and AI entry points; completed source-level competitor settings crosswalk, options bank, architecture proposal, React UX direction, and compatibility baseline. No plugin implementation or CLI tooling added.
