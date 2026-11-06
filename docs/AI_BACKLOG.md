
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
| AI-02 | Audit competitor APIs, live UI, and plan limits | In progress | Zorem/Recoup options reviewed from official sources; remaining API/changelog/full plan audit recorded with evidence. |
| AI-03 | Finalize capture/event definitions | Draft ready; approval pending | Refund evidence vs confirmed return, manual per-item capture, event scope documented. |
| AI-04 | Deduplicate feature/system options | Plan complete; approval pending | Canonical IDs map competitor options without duplicating RMA workflows. |
| AI-05 | Finalize schema, rate math, privacy | In progress | Event schema, source confidence, denominator, retention, HPOS reviewed. |
| AI-06 | Finalize React flows and REST contract | In progress | Screens/states/accessibility/capabilities approved. |
| AI-07 | Set runtime compatibility baseline | Baseline selected; Marketplace release range alignment pending | V1 minimums documented; two-major support range rechecked at submission. |
| AI-08 | Threat model capture and AI clustering | Planned | Abuse controls, opt-in, data sent, explainability approved. |
| AI-09 | Cross-model contradiction review | Not started | ChatGPT, Cursor, Claude, Gemini reviews reconciled into decisions. |
| AI-10 | Break approved spec into implementation work | Blocked by P6 | Small dependency-ordered tasks; no premature CLI. |
| AI-11 | Prepare WordPress.org Free and Woo Marketplace Paid submissions | Plan complete; release blocked | Separate packages/listings, accounts, test evidence, and review gates tracked in docs/DISTRIBUTION.md. |

## Collaboration protocol

1. Every tool reads README, AGENTS, and task-specific plan first.
2. Scope work to one backlog ID and exact files.
3. Research output records source URL, review date, supported claim, uncertainty.
4. Models can propose alternatives but cannot silently change approved decisions.
5. No implementation agent acts until P6 approval.
6. A second model checks contradictions and duplicate features, not unrelated prose.
7. Final review checks scope, privacy/security, compatibility, and progress evidence.
8. Never provide credentials, production customer data, or private store records as test context.

## Reusable task prompt

Read README.md, AGENTS.md, and the relevant plan. Work only on the assigned backlog ID. Verify changing technical claims against official sources and include links/date. Do not add runtime code or CLI tooling before P6 approval. Keep the options bank deduplicated, update README progress/change log in this same update, and report evidence, unresolved decisions, and actual completion.
