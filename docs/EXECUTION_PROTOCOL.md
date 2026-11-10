# Execution Protocol: Evidence-Driven Completion

**Purpose:** Make repository work produce reviewable changes and verified outcomes on every turn. This file governs execution mechanics; product scope and approved architecture remain in `README.md`, `AGENTS.md`, and the relevant specifications.

## One source of truth

- GitHub `main` is authoritative; inspect the current tree and latest commit before choosing work.
- `docs/IMPLEMENTATION_BACKLOG.md` owns I-ID scope and implementation acceptance.
- `docs/I10_I11_VERIFICATION.md` owns I-10/I-11 runtime and release evidence, including explicit blockers.
- `README.md` is a summary mirror. It must not claim stronger completion than either detailed ledger.
- Provider entry points only route models to these rules. If a provider file conflicts with `AGENTS.md`, follow `AGENTS.md` and repair the pointer.
- A status is **Complete** only when its stated acceptance evidence is linked or recorded. Code present, source inspection, lint, and ZIP generation do not prove WordPress runtime behavior.
- Use statuses: **Complete**, **In progress**, **Blocked**, **Not started**. Use a percentage only when it is calculated from named, equally weighted checklist items with completed/total counts; otherwise show a count or qualitative status.
- Keep source implementation, static inspection, automated CI, manual site smoke checks, and release acceptance distinct.

## Required execution loop

For every work session, including a user message containing only “continue”, “…”, or equivalent:

1. Read this file, `README.md`, `AGENTS.md`, the assigned backlog, and latest verification record. Check current GitHub main and recent CI before choosing work.
2. If the user named an I-ID, work within it. Otherwise select the highest-priority unblocked acceptance item; do not spend the turn only describing a plan.
3. Identify the concrete deliverable, exact repository paths, acceptance checks, allowed verification, and environment restrictions.
4. Make the smallest coherent change on GitHub `main` (unless the user requests a branch/PR). Preserve existing history and avoid local project/artifact copies while the no-local-file instruction applies.
5. Run the narrowest permitted verification that proves the change. Record exact command/environment, result, commit URL/SHA, and artifact reference only when relevant. Never infer runtime success from PHP lint or packaging.
6. Update the detailed backlog/evidence, README dashboard, and change log in the same work session as every meaningful repo change.
7. Re-read changed files and compare the provider pointers, README, backlog, evidence ledger, and approved decisions for contradictions.
8. Report completed work, evidence class, unresolved blocker, and the next concrete action. Do not ask the user to repeat authorization already given.

## Blocker handling

- A blocker is a specific failed/denied prerequisite. Record what was attempted, the observed result, date, affected acceptance item, and whether the action changed site data.
- Do not retry a browser/security-policy-denied action through another browser, API, CLI, or alternate path. Do not claim a package was installed when only selected/uploaded.
- After a blocked check, immediately complete independent work: source audit, plan reconciliation, static checks that do not create local files, test-case preparation, or another unblocked checklist item. Mark only the dependent item Blocked and continue.
- Ask only when the next concrete action needs new access, a genuinely missing approval, or a decision that cannot be inferred. State the precise blocked action and why. Do not repeatedly ask for credentials or re-upload.
- If no useful work remains without user input, provide one precise unblock request and stop.

## I-11 acceptance ledger rules

- Keep every check atomic: one scenario, one expected result, one actual result, one evidence reference, one status.
- Separate environments by exact site label. Evidence from one site never proves another site.
- A failed/unavailable check remains open; unrelated passing checks do not raise its status.
- The repository currently has no dedicated automated test suite. Do not treat successful lint/package CI as behavioral coverage. Before release, define and execute a suitable automated and manual test matrix for REST permission boundaries, schema migration/upgrade, CRUD/idempotency, refunds, privacy failures, cohort math, alert/action persistence and concurrency, and multilingual adapters. Respect the no-CLI/tooling constraints and document the allowed approach before adding test infrastructure.
- Do not use production-marked environments for mutation tests. Any staging exception must be narrowly authorized and reversible, must avoid changing WooCommerce orders/refunds, and must be recorded.
- I-11 closes only when every required release gate in `docs/I10_I11_VERIFICATION.md` has a passing evidence record or an explicitly approved, documented exclusion.
- I-12 submission work starts only after I-11 closes and separate Free/Paid package and account prerequisites are evidenced.

## Prompt contract for AI contributors

Every task prompt must contain repository/branch, assigned I-ID (or instruction to select the highest-priority unblocked item), exact scope/files, acceptance checks, allowed tests/environment, prohibited actions, required doc updates, evidence classes, and report format. Tell the model to execute in the same session and not return status only when work is possible. Do not rely on hidden provider auto-discovery; provider files must link to `AGENTS.md`, this protocol, and `docs/AI_BACKLOG.md`.
