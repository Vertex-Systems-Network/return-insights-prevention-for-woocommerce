# Implementation Backlog — Draft, Blocked by P6

**Status:** Ordered work plan only. No implementation or tooling has started.  
**Start condition:** Explicit product-owner approval recorded in `P6_READINESS_GATE.md`.  
**Repository constraint:** Do not add CLI/build tooling to this repository until the plugin is final. Before React implementation, approve a build and artifact workflow that obeys this constraint.

Each item must become a scoped task with named files, acceptance evidence, dependencies, and README progress update. No task below is authorization to begin.

## Sequence

| ID | Work package | Main scope | Depends on | Exit evidence |
|---|---|---|---|---|
| I-00 | Lock detailed technical decisions | Final event taxonomy, rate cohort/maturity rules, thresholds, retention/export fields, package model, REST schemas/permissions | P6 approval | Decision records agree across product, data, UX, privacy, compatibility specs |
| I-01 | Security and privacy design | Threat model, capabilities/roles, REST permission matrix, nonce/auth assumptions, validation, minimization, export/erase, retention/uninstall | I-00 | Reviewed threat/privacy checklist and data lifecycle |
| I-02 | Plugin foundation | Naming/slug/constants, activation/deactivation behavior, migrations, dependency checks, translations, admin menu, supported public APIs | I-00, I-01 | Activation/deactivation/migration behavior documented and manually verified |
| I-03 | Reason catalog and manual capture | Stable reason keys, translated labels, per-item merchant capture, validation/audit/provenance | I-02 | Manual event saved and edited with capability checks; canonical ID stable across locales |
| I-04 | Native refund observation | Supported WooCommerce refund hooks/objects, idempotent ingestion, refund-scope attribution, HPOS + legacy behavior | I-02, I-03 | Refund evidence stored once with source/line mapping and no physical-return inference |
| I-05 | Basic reporting (Free) | Counts, date filters, coverage, source breakdown, empty/low-data states | I-03, I-04, I-06 | UI numbers reconcile to event definitions; no unsupported rate shown |
| I-06 | REST contract and React admin | Permission-protected endpoints, pagination/errors, Overview/Insights/catalog/source screens, accessibility and RTL | I-00, I-01, approved build/artifact process | Screen/API acceptance criteria met, equivalent chart data available |
| I-07 | Pro product/variation insights | Aggregation, eligible denominators, comparable cohorts, sample-aware pattern output | I-04, I-05 | Counts/rates show definitions and coverage; weak/incomplete cohorts suppress rates |
| I-08 | Pro clustering and alerts | Local explainable grouping; thresholds, deduplication, dismissal and audit | I-07 | Membership is reviewable/reversible; alert evidence and suppression reasons visible |
| I-09 | Corrective-action tracking | Hypothesis, owner, statuses, dates, measurement windows, before/after view | I-07, I-08 | Status history and comparable pre/post evidence; no causality claims |
| I-10 | Optional multilingual/integration compatibility | WPML/Polylang integration and any approved RMA source adapters | I-03 to I-06, demand/API review | Tested optional integrations; core works when absent/disabled |
| I-11 | Release hardening | Current compatibility matrix, security/accessibility/performance/privacy checks, migration/rollback, docs, package boundaries | I-02 to I-10 | Evidence recorded for claimed versions and release criteria |
| I-12 | Submission preparation | WordPress.org Free and WooCommerce.com Paid artifacts/listings, QIT and reviewer responses | I-11 | Separate packages validated and submission checklists complete |

## Execution rules for AI coding tools

1. Read `README.md`, `AGENTS.md`, this backlog, and the assigned package specs before changing files.
2. Work on one I-ID at a time unless the owner explicitly combines tightly coupled work.
3. Verify current WordPress/WooCommerce APIs against primary documentation; never invent hooks or internal APIs.
4. Use test fixtures, not real merchant/customer data. Do not disclose secrets or credentials.
5. Do not add CLI/build tooling to the repo before the plugin is final. If a task appears to require it, stop that task and propose a compliant artifact workflow for owner review.
6. Do not send customer/order data to external AI. External AI remains out of V1 unless a separately approved privacy/security design changes scope.
7. Do not claim a release compatibility or performance result without recorded evidence.
8. Update README roadmap/change log with every meaningful repository change.
9. Report exact files changed, acceptance evidence, open risks, and work not performed.
