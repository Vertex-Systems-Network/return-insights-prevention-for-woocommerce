# Execution Protocol: Evidence-Driven Completion

**Purpose:** Make repository work produce reviewable changes and verified outcomes on every turn. This file governs execution mechanics; product scope and approved architecture remain in `README.md`, `AGENTS.md`, and the relevant specifications.

## One source of truth

- `docs/IMPLEMENTATION_BACKLOG.md` owns I-ID scope and implementation acceptance.
- `docs/I10_I11_VERIFICATION.md` owns I-10/I-11 runtime and release evidence, including explicit blockers.
- `README.md` mirrors the current summary and links to those detailed records; it must not contradict them.
- A status is **Complete** only when its stated acceptance evidence is linked or recorded. Code present, source inspection, lint, and ZIP generation do not prove WordPress runtime behavior.
- Use statuses: **Complete**, **In progress**, **Blocked**, **Not started**. Use a percentage only when it is calculated from named, equally weighted checklist items; otherwise show the count (for example, 7/10 checks) and do not invent a percentage.

## Required execution loop

For every work session, including a user message containing only “continue”, “…”, or equivalent:

1. Read this file, `README.md`, `AGENTS.md`, the assigned backlog, and the latest verification record. Check the current GitHub main branch and recent CI before choosing work.
2. Select the highest-priority **unblocked** acceptance item. State the exact deliverable, files, test, and pass condition in a concise progress update, then execute it in this same session.
3. Prefer finishing one independently verifiable item over discussing the whole roadmap. Do not reply with a plan/status alone when repository work can proceed.
4. Run the narrowest permitted verification that proves the change. Record exact command or environment, result, commit/artifact URL and SHA where available. Never infer runtime success from PHP lint or packaging.
5. Update the detailed evidence record, backlog status, README dashboard and change log in the same work session. Re-read changed files and check for contradictory claims before reporting.
6. Report the result, evidence, remaining blocker, and the next concrete action. Do not ask the user to repeat authorization already given.

## Blocker handling

- A blocker is a specific failed/denied prerequisite, not an open-ended reason to stop. Record what was attempted, the observed result, date, affected acceptance item, and whether the action changed site data.
- Do not retry a browser/security-policy-denied action through another browser, API, CLI, or alternate path. Do not claim that a package was installed when it was only selected/uploaded.
- After a blocked check, immediately complete independent work: source audit, static checks, CI artifact validation, test-case preparation, documentation reconciliation, or another unblocked checklist item. Mark only the dependent item Blocked and continue.
- Ask the user only when the next concrete action requires new access, an approval not already granted, or a decision that cannot be inferred. State the exact action and why it is blocked. Do not repeatedly ask for credentials or re-upload when existing access already covers the task.
- If no useful work remains without user input, provide one precise unblock request and stop; do not send repeated “continue?” prompts.

## I-11 acceptance ledger rules

- Keep every check atomic: one scenario, one expected result, one actual result, one evidence reference, one status.
- Separate environments by exact site label. Evidence from one site must never be attributed to another.
- Separate implementation, static verification, CI/package verification, limited runtime smoke checks, and release acceptance.
- A failed or unavailable check remains open; unrelated passing checks do not raise its status.
- I-11 can close only when every release gate in `docs/I10_I11_VERIFICATION.md` has a passing evidence record or an explicitly approved, documented exclusion. A production-marked test environment cannot be used for unapproved mutation tests.
- I-12 submission work starts only after I-11 closes and the separate Free/Paid package and account prerequisites are evidenced.

## Prompt contract for AI contributors

Every provider task must include:
- project/repository and authoritative branch;
- exact backlog ID(s), scope and files;
- acceptance checklist and evidence expected;
- tests allowed and environment constraints;
- instruction to execute now, update evidence/docs in the same change, and report only verified facts;
- blocker rule above and exact next action.

Do not rely on an agent to discover hidden instructions from a provider-specific file. Provider entry points must link here and to `AGENTS.md`. Never spend a turn only repeating the plan if an unblocked acceptance item can be completed.
