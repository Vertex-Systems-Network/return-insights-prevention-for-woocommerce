
# AI Backlog and Agent Handoff Plan

## How an AI tool discovers the project

Provider entry points point to one canonical rules file:
- ChatGPT/Codex-compatible agents: README.md and AGENTS.md.
- Cursor: .cursor/rules/project.mdc.
- Claude Code: CLAUDE.md.
- Gemini CLI: GEMINI.md.
- GitHub Copilot: .github/copilot-instructions.md.

This improves discovery but cannot guarantee every version of every provider auto-loads the file. Include “Read README.md and AGENTS.md, then the relevant plan” in each task. Keep canonical rules in AGENTS.md; provider files are short pointers to prevent drift.

## Backlog

| ID | Work package | Status | Acceptance evidence |
|---|---|---|---|
| AI-01 | Maintain README roadmap after changes | Active rule | Same update changes status, progress, dates, change log. |
| AI-02 | Audit competitor screens/docs/plan limits | Planned | Zorem and Recoup settings, docs, changelogs, APIs compared with sources. |
| AI-03 | Finalize event/capture model | Open | D1/D2 decisions recorded in product spec. |
| AI-04 | Deduplicate feature/system options | Draft ready | One canonical ID/owner per distinct outcome. |
| AI-05 | Finalize schema, rate math, privacy | Planned | Event schema, denominator, retention, HPOS reviewed. |
| AI-06 | Finalize React flows and REST contract | In progress | Screens/states/accessibility/capabilities approved. |
| AI-07 | Verify PHP/WP/WC/WPML/Polylang support | Draft ready | Exact supported/tested matrix with official links. |
| AI-08 | Threat model capture and AI clustering | Planned | Abuse controls, opt-in, data sent, explainability approved. |
| AI-09 | Cross-model contradiction review | Not started | ChatGPT, Cursor, Claude, Gemini reviews reconciled into decisions. |
| AI-10 | Break approved spec into implementation work | Blocked by P6 | Small dependency-ordered tasks; no premature CLI. |

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
