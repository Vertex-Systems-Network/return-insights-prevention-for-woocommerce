# AI Backlog and Agent Handoff Plan

## How an AI tool discovers the project

Provider entry points point to one canonical rules file:
- ChatGPT/Codex-compatible agents: README.md and AGENTS.md.
- Cursor: .cursor/rules/project.mdc.
- Claude Code: CLAUDE.md.
- Gemini CLI: GEMINI.md.
- GitHub Copilot: .github/copilot-instructions.md.

This improves discovery but cannot guarantee every version auto-loads the file. Include “Read README.md and AGENTS.md, then the relevant plan” in each task. Keep canonical rules in AGENTS.md; provider files remain short pointers to prevent drift.

## Backlog

| ID | Work package | Status | Acceptance evidence |
|---|---|---|---|
| AI-01 | Maintain README roadmap after changes | Active rule | Same update changes status, progress, dates, change log. |
| AI-02 | Audit competitor APIs, live UI, and plan limits | In progress | Zorem/Recoup options reviewed from official sources; remaining API/changelog/full plan audit with evidence. |
| AI-03 | Finalize capture/event definitions | Approved at P6 | Event kinds, manual per-item capture, refund-only evidence, and reason seed catalog recorded in `PRODUCT_SPEC.md` and `P6_READINESS_GATE.md`. |
| AI-04 | Deduplicate feature/system options | Approved at P6 | Canonical IDs map competitor options without duplicating RMA workflows in `OPTIONS_BANK.md`. |
| AI-05 | Specify schema, rate math, privacy, performance | Approved design; implementation evidence pending | Logical model, metric rules, retention defaults, idempotency, HPOS boundary, privacy, and bounded aggregation documented; benchmarks and runtime verification remain. |
| AI-06 | Finalize React flows and REST contract | Approved design; implementation validation pending | UX journeys and permission/payload contract documented; field-level schemas and accessibility validation are I-06 implementation evidence. |
| AI-07 | Set runtime compatibility baseline | Planning baseline complete; implementation/release matrix pending | PHP/WP/WC floors and optional WPML/Polylang behavior documented; rolling two-major targets dated and official evidence linked in `docs/COMPATIBILITY.md`. Actual runtime verification remains a release task. |
| AI-08 | Threat model capture and AI clustering | Approved V1 privacy/security design; runtime verification pending | Threat cases, REST controls, privacy lifecycle, and verification scenarios in `docs/P6_TECHNICAL_CONTRACTS.md`; external AI excluded from V1. |
| AI-09 | Cross-model contradiction review | Not started | ChatGPT, Cursor, Claude, Gemini reviews reconciled into decisions. |
| AI-10 | Break approved spec into implementation work | Approved at P6; execute in sequence | I-00–I-12 dependency order in `docs/IMPLEMENTATION_BACKLOG.md`; I-00/I-01 design complete. Keep CLI/build tooling out until plugin final. |
| AI-11 | Prepare WordPress.org Free and Woo Marketplace Paid submissions | Plan complete; release blocked | Separate packages/listings, accounts, test evidence, and review gates tracked in docs/DISTRIBUTION.md. |

## Collaboration protocol

1. Every tool reads README, AGENTS, and task-specific plan first.
2. Scope work to one backlog ID and exact files.
3. Research output records source URL, review date, supported claim, uncertainty.
4. Models can propose alternatives but cannot silently change approved decisions.
5. P6 approval is recorded; implementation agents work only on an authorized I-ID and user scope.
6. A second model checks contradictions and duplicate features, not unrelated prose.
7. Final review checks plan scope, privacy/security, compatibility, and progress evidence.
8. Never provide credentials, production customer data, or private store records as test context.

## Reusable task prompt

Read README.md, AGENTS.md, and the relevant plan. Work only on the assigned backlog ID. Verify changing technical claims against official sources and include links/date. P6 is approved. Work only on the assigned I-ID; do not add CLI/build tooling to this repo until the plugin is final. Keep the options bank deduplicated, update README progress/change log in this same update, and report evidence, unresolved decisions, and actual completion.
