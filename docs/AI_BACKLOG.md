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
| AI-06 | Finalize React flows and REST contract | Implemented; runtime/accessibility verification pending | I-06 provides field schemas, unknown-field checks, permission gates, paginated events, and React workflows; runtime/accessibility validation remains I-11. |
| AI-07 | Set runtime compatibility baseline | Planning baseline complete; implementation/release matrix pending | PHP/WP/WC floors and optional WPML/Polylang behavior documented; rolling two-major targets dated and official evidence linked in `docs/COMPATIBILITY.md`. Actual runtime verification remains a release task. |
| AI-08 | Threat model capture and AI clustering | Approved V1 privacy/security design; runtime verification pending | Threat cases, REST controls, privacy lifecycle, and verification scenarios in `docs/P6_TECHNICAL_CONTRACTS.md`; external AI excluded from V1. |
| AI-09 | Cross-model contradiction review | Not started | ChatGPT, Cursor, Claude, Gemini reviews reconciled into decisions. |
| AI-10 | Break approved spec into implementation work | I-02 through I-09 implemented; I-10/I-11 pending | I-07 provides bounded mature cohort patterns behind fail-closed Pro entitlement. I-08 clustering/alerts and I-09 corrective-action tracking implementations are complete. I-10 integration verification and I-11 runtime/release verification remain. Composer manifest is limited to PSR-4 and has no external dependencies. |
| AI-11 | Prepare WordPress.org Free and Woo Marketplace Paid submissions | Plan complete; release blocked | Separate packages/listings, accounts, test evidence, and review gates tracked in docs/DISTRIBUTION.md. |
| AI-12 | Close execution-loop and evidence gaps | In progress | `docs/EXECUTION_PROTOCOL.md` defines atomic acceptance, evidence classes, no-progress-only continue handling, blocker action, status vocabulary, and I-11/I-12 closure gates; README and verification records must agree. |

## Collaboration protocol

1. Every tool reads README, AGENTS, and task-specific plan first.
2. Scope work to one backlog ID and exact files.
3. Research output records source URL, review date, supported claim, uncertainty.
4. Models can propose alternatives but cannot silently change approved decisions.
5. P6 approval is recorded; implementation agents work only on an authorized I-ID and user scope.
6. A second model checks contradictions and duplicate features, not unrelated prose.
7. Final review checks plan scope, privacy/security, compatibility, and progress evidence.
8. Never provide credentials, production customer data, or private store records as test context.
9. PHP classes follow Composer PSR-4 (`WPEssential\ReturnInsights\` → `/includes/`) through `composer.json`; plugin bootstrap and uninstall directly require generated `vendor/autoload.php`. Keep class/file casing aligned; no Composer command before final packaging.

## Reusable task prompt

> Repository: `Vertex-Systems-Network/wpessential-return-insights-prevention-woocommerce`; source of truth: GitHub `main`. Read `README.md`, `AGENTS.md`, `docs/EXECUTION_PROTOCOL.md`, and the assigned plan/evidence record before editing. Execute the highest-priority unblocked acceptance item for **[I-ID]** in this session; do not return a status-only response if useful work can proceed. Scope/files: **[exact scope]**. Acceptance checks: **[atomic checks with expected outcomes]**. Allowed verification/environment: **[commands/site; explicitly list prohibited actions]**. P6 is approved. Do not add general CLI/build tooling, dependencies, `vendor/`, or a frontend build pipeline; the approved private ZIP workflow is the only packaging exception. Keep the options bank deduplicated. Run permitted checks, record exact results and evidence links, update the detailed ledger plus README dashboard/change log in the same change, and re-read for contradictions. Distinguish static, CI/package, runtime, and release evidence; never claim untested behavior. On a denied/blocked check, record the exact denial and data impact, do not retry through alternate surfaces, then finish independent work and state the precise next action. Ask only for genuinely missing access/approval/decision. Report changed files, commit, checks/results, unresolved gate, and next action.
