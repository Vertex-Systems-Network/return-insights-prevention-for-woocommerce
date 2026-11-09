# WPEssential Return Insights & Prevention for WooCommerce

> Early implementation repository. P6 is complete. I-02 through I-09 implementation work is present, including Composer PSR-4 PHP autoloading and Pro corrective-action tracking. I-10 approved integration implementation is complete; runtime/release verification remains in I-11 and submission preparation in I-12.

## Product direction

Help WooCommerce merchants understand why items are returned, identify repeat product/variation issues, and track whether a corrective change helps. This is reason intelligence, not RMA/refund/exchange/carrier processing.

**Proposed tiers:** Free reason capture and basic reports. Pro proposed at $79/year for product/variation trends, reason clustering, alerts, and corrective-action tracking. Pricing and boundaries remain unapproved.

**Positioning reality:** Existing products already collect reasons and some advertise product/reason analytics. Proposed distinction is a focused prevention loop—evidence → correction → comparable measurement—not analytics alone.

## Roadmap

Planning estimates in UTC; reviewed 2026-11-06. P0–P7 track planning artifacts; runtime work packages are tracked separately below.

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

**Planning artifact completion: 100%** across P0–P7. P6 is approved and implementation is authorized within the scoped backlog. Runtime work packages: 8/11 implemented (**73%**). Dates are estimates, not delivery promises. Keep planning progress separate from implementation progress. Update this table and change log whenever the plan changes.

## Implementation status

P6 approval is recorded. I-02 foundation, I-03 catalog/manual-capture API, I-04 refund observation, I-05 Free reports, I-06 REST/React admin, I-07 Pro product/variation cohort insights, I-08 Pro clustering/alerts, and I-09 corrective-action tracking implementation are present (8/11, **73%**). PHP classes use Composer PSR-4 mapping `WPEssential\ReturnInsights\` to `includes/`. The custom fallback loader has been removed; the generated Composer `vendor/autoload.php` is required at runtime. Free reports provide date-filtered event/reason/quantity/source counts and item-scoped product/category totals. Pro patterns compare mature purchase cohorts, use exact item-level confirmed-return attribution, and suppress rates for incomplete mapping, fewer than 20 sold units, missing denominators, and returns above sold quantity. WPML/Polylang merchant-reason label adapters are implemented with guarded optional APIs; active/absent integration runtime checks remain in I-11. WordPress/WooCommerce privacy export/erasure hooks are now implemented; runtime behavior is still unverified. Runtime, visual, accessibility, compatibility, and commercial entitlement verification remain open. React uses WordPress bundled packages without JSX/build tooling. A private test ZIP builder is now authorized; it runs Composer only in isolated staging and does not add a frontend build pipeline.

## Composer autoload task status

| Task | Status | Progress | Evidence / remaining work |
|---|---|---:|---|
| Keep plugin classes under `includes/` | Complete | 100% | Eleven PSR-4 class files use case-matched names in `includes/`. |
| Map `WPEssential\ReturnInsights\` to `includes/` | Complete | 100% | `composer.json` declares the namespace prefix and directory. |
| Load `vendor/autoload.php` directly | Code updated | 100% | Plugin bootstrap and uninstall handler directly require Composer's generated loader; no project SPL loader remains. |
| Generate Composer vendor files and verify runtime | Build automated; runtime pending | 75% | GitHub Actions builds a staged test ZIP with Composer PSR-4 files; still install and verify activation/uninstall on a WordPress + WooCommerce test site. |

## Distribution plan

- **Free edition:** submit a complete, production-ready package to the WordPress.org Plugin Directory.
- **Paid Pro edition:** submit separately to the WooCommerce.com Marketplace.
- For a freemium product, Woo currently requires separate Free and Paid submissions; they may be combined on one product page after approval.
- Woo Marketplace submission also requires an approved vendor account, product/business details, test instructions, compatibility with the two latest major WordPress and WooCommerce releases, HPOS, accessibility, and required Quality Insights Toolkit checks.
- Re-evaluate version targets, licensing, listing name, pricing, vendor terms, and packages at release. The current compatibility baseline is a planning target, not proof of future marketplace approval.

Details and official source links: docs/DISTRIBUTION.md. No submission has been made.

## Runtime work-package dashboard

| Package | Status | Progress | Start | End |
|---|---|---:|---|---|
| I-02 Foundation | Complete | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| I-03 Catalog/manual capture API | Implementation complete; real-order lookup and staging capture/edit/soft-delete smoke-tested; broader permissions/DB/audit verification pending I-11 | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| I-04 Refund observation | Complete | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| I-06 REST + React admin | Implemented; I-11 verification pending | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| I-05 Free reports | Implemented; I-11 verification pending | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| I-07 Pro product/variation patterns | Implemented; entitlement provider + I-11 verification pending | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| I-08 Pro clustering and alerts | Implementation complete; I-11 runtime verification pending | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| I-09 Corrective-action tracking | Implementation complete; I-11 runtime verification pending | ██████████ 100% | 2026-11-06 | 2026-11-06 |
| I-10 WPML/Polylang and optional source checks | V1 adapter implemented; absent-plugin fallback smoke-tested; active-plugin cases remain open | ██████████ 100% | 2026-11-06 | 2026-11-07 |
| I-11 Runtime, security, accessibility and release verification | Staging admin/REST, HPOS schema presence, read-only lookup, manual capture/edit/soft-delete, invalid→valid order lookup regression, Pro gates, basic keyboard and absent-translation fallback checked; remaining release gates open | Evidence ledger; open gates | 2026-11-06 | 2026-11-24 |
| I-12 WordPress.org Free + WooCommerce.com Paid submission preparation | Pending I-11 release evidence and vendor account/package checks | ░░░░░░░░░░ 0% | 2026-11-25 | 2026-12-04 |

Dates for pending packages are planning estimates; I-11 dates depend on access to a PHP/Composer and WordPress/WooCommerce runtime. Package progress means implementation scope is present; it does not mean runtime/release acceptance is verified. See `docs/IMPLEMENTATION_BACKLOG.md` and `docs/I10_I11_VERIFICATION.md` for detailed evidence and blockers. All contributors must follow `docs/EXECUTION_PROTOCOL.md`; its checklist and blocker rules define how work proceeds and when I-11/I-12 can close.

## Current gate

P6 passed on 2026-11-06. D1–D12 are approved and recorded in docs/P6_READINESS_GATE.md. I-02 through I-09 implementation packages are present (8/11, 73%); I-08 and I-09 are complete at implementation scope. I-08 includes deterministic clusters, reversible assignments, weekly Pro evaluation, a full candidate scan capped at 2,000 rows with fail-closed overflow, ≥80% mapping threshold, per-candidate sample/rate gates, stable deduplication, bounded 2,000-alert storage and 50-entry action history, persisted scan status, and React cluster/alert review UI. The Alerts screen surfaces coverage blocks and withheld-candidate reasons. WPML/Polylang optional reason-label adapters are present, but their active/absent runtime checks remain open. Runtime, visual, accessibility, compatibility, privacy exporter/eraser, action-workflow, and entitlement-provider verification remain tracked in I-11. On 2026-11-09, per-item manual insight writes were serialized with MySQL advisory locks; duplicate retries are checked before quantity validation and rechecked under the lock. GitHub Actions run 42637830397 passed PHP 8.3 lint and generated the test ZIP (SHA-256 `0801dea18585d7ff19d8199bff45e17f8061351b5754550f8226378ce146ea0b`; artifact expires 2026-11-23). A separate TasteWP installation attempt was blocked by the browser URL policy before install completed; it is not staging-install evidence. I-11 remains open pending authorized runtime installation and remaining release checks. On `wpessential.org/el-test`, a prior package install/activation and limited smoke checks are recorded below; this is separate from TasteWP, where the latest package installation was blocked before completion. The `_locale=user` query-validation defect was fixed and the corrected artifact was installed. Free overview/history/catalog/source screens, expected Pro upgrade gate, and read-only lookup of one order's line item were smoke-checked. After the test site was authorized for a narrow manual-capture smoke check, one clearly temporary insight for order #49/item #1 was created, edited, and soft-deleted with an audit note; History returned to empty. No WooCommerce order or refund was changed. WooCommerce reports HPOS active and lists the plugin's five tables. Its environment type reports `production` despite the `/el-test/` path; this limits further testing to controlled reversible plugin-only checks until broader release testing is defined. Broader runtime, security, compatibility, and accessibility verification remains in I-11. Composer manifest maps PSR-4 only; the private test-package workflow generates Composer files in a temporary staging directory. No third-party dependency, generated vendor files, or frontend build tooling is committed.

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
- docs/I10_I11_VERIFICATION.md — integration behavior, static audit evidence, and release-gate blockers.
- docs/AI_BACKLOG.md — agent tasks and provider handoff.
- composer.json — Composer PSR-4 namespace map (`WPEssential\ReturnInsights\` → `includes/`); generated vendor files are required in plugin ZIPs.
- tools/build-artifact.py — private test ZIP packager with staged Composer autoload generation and SHA-256 output.
- .github/workflows/build-test-artifact.yml — on-demand and source-change GitHub Actions artifact build (14-day download window).
- readme.txt — plugin-directory-format test package metadata and install instructions.
- AGENTS.md — canonical project instructions.
- docs/EXECUTION_PROTOCOL.md — required work loop, acceptance evidence, blocker handling, and phase closure rules.
- CLAUDE.md, GEMINI.md, .cursor/rules/project.mdc, .github/copilot-instructions.md — provider entry points.

## Non-negotiable scope

1. Prevention insights, not return-request processing.
2. Free: structured reason collection and basic reports.
3. Pro proposal: product/variation trends, clustering, alerts, corrective-action tracking.
4. React-based, polished, accessible wp-admin interface.
5. HPOS-safe WooCommerce integration.
6. WPML and Polylang behavior specified and tested; neither is a hard dependency.
7. P6 is passed; implementation follows the approved backlog. The only current packaging exception is the private test ZIP workflow (`tools/build-artifact.py` and its GitHub Actions workflow); no general CLI, WP-CLI, or frontend build pipeline.
8. AI agents read this README and AGENTS.md, make scoped changes, and update the dashboard in the same update.
9. PHP classes follow Composer PSR-4: `WPEssential\ReturnInsights\` maps to `/includes/` in `composer.json`; plugin bootstrap and uninstall directly require generated `vendor/autoload.php`. The test packager runs Composer only in temporary staging; do not commit generated vendor files or add third-party dependencies.

## Research snapshot

Dated platform review (2026-11-06): WordPress 7.1.4 and WooCommerce 11.2.2 are the latest releases listed in the official release archives. Planned support matrix targets WordPress 7.1/7.0 series and WooCommerce 11.2/11.1 series, with PHP 8.3+, MySQL 8.0+ or MariaDB 10.11+, HTTPS. Woo Marketplace requires the two latest major WP and WC release series; refresh these moving targets before implementation and again at submission. WPML and Polylang are optional integrations. See docs/COMPATIBILITY.md for evidence and caveats.

Recommended MVP capture model: observe native WooCommerce refund records and allow per-item admin insight entry for non-refund returns. A refund is not proof that an item physically returned, and an order-level reason must not be guessed onto every line item.

P3 architecture specifies indexed custom event storage, source-level idempotency, bounded background aggregation, separate return/refund metrics, and privacy export/erasure. See docs/ARCHITECTURE_DATA_FLOW.md and docs/DATA_MODEL.md.

## Change log

- 2026-11-09: Hardened manual insight REST reads after source audit. Database errors from idempotency lookup and event reads now return safe server errors; quantity-limit validation fails closed on database query errors; create/update paths verify the saved row before serialization. This addresses a source-level fail-open/fatal edge case but does not replace REST abuse, race-condition, or database failure-injection testing. The source push triggers the PHP-lint/package GitHub Action; its result is not yet recorded. I-11 remains 70% pending runtime/release gates.

- 2026-11-09: Prepared the user-provided TasteWP site `quirkybrake.s2-tastewp.com` for WooCommerce runtime testing. WordPress 7.1.4 was observed; official WooCommerce 11.2.2 was installed and the fresh Installed Plugins screen confirmed it active, although the activation redirect displayed a WooCommerce translation-loading notice. The current Return Insights test ZIP was selected in the upload form, but the browser blocked the final Install Now action; the plugin was not confirmed installed on this site. No I-11 progress increase; continue only after a supported upload flow is available.

- 2026-11-08: Fixed a stale-order lookup race: changing the order ID now clears prior order/item state, and capture controls remain disabled during lookup. CI run `42439188546` passed and its artifact was installed on staging. Invalid order `999999999` showed “Order not found,” cleared the prior Hat selection, and kept Save disabled; valid order `49` then reloaded its single Hat line item. History remained empty. No insight, WooCommerce order, or refund was changed. I-11 advances to 70%; the broader release gates remain open.

- 2026-11-08: Fixed the manual insight edit request to send only REST-supported PATCH fields (`event_kind`, `quantity`, `reason_id`, `occurred_at`). CI run `42434063755` passed PHP lint and test ZIP packaging; artifact `13598489437` was installed on staging. A test insight for order #49/item #1 was created, changed from `Other` to `Changed mind`, then soft-deleted with an audit note; History returned to the empty state. No WooCommerce order/refund was modified. The UI also blocked quantity 2 for an item ordered at quantity 1 before submission; server-side boundary testing remains open. I-11 remains at 70%; remaining release gates are listed in `docs/I10_I11_VERIFICATION.md`.

- 2026-11-07: Hardened native refund observation to log database lookup/insert/update/delete failures without order or customer identifiers. CI run `42386157013` passed PHP lint and package validation; artifact `13580105362` includes refund and bounded privacy-erasure hardening. Runtime failure-injection and privacy callback checks remain open.
- 2026-11-07: Staging System Status showed the plugin Author as blank; added `Author: WPEssential` and the WPEssential site URI to the plugin header. Bounded user-erasure batches to 100 rows per plugin table per callback, with residual-row checks and resumable status. CI run `42385970130` passed PHP lint and packaging; artifact `13579790771` contains these updates. Staging has not been updated with the latest package; privacy erasure runtime remains unverified.
- 2026-11-07: Added a pre-autoloader PHP 8.3 guard so Composer's generated platform check cannot fatal before WordPress receives a compatibility notice. Then matched the staging fatal log to `includes/RestController.php:538`: a stray REST-route fragment after the class close caused a PHP parse error. Removed the 53-line fragment and made PHP lint mandatory before packaging. Latest lint + package run passed (`42375227945`); artifact `13575581948` is available through 2026-11-21. Runtime recovery on the staging site remains unconfirmed.

- 2026-11-07: Added a private test ZIP build path using isolated staging, Composer PSR-4 autoload generation, package allowlisting, ZIP validation, SHA-256 output, and a 14-day GitHub Actions artifact. Run #1 passed (GitHub Actions run `42373990114`); artifact `13575185569` is available through 2026-11-21. This confirms packaging only; the test-site runtime install is still pending.

- 2026-11-06: Implemented optional WPML and Polylang reason-label registration/resolution with guarded public APIs; canonical reason IDs remain stable. Added WordPress privacy export/erasure hooks for actor data and WooCommerce order-linked insight references; runtime behavior remains unverified. Completed I-11 static audit notes and recorded remaining runtime, privacy, accessibility, and release blockers in `docs/I10_I11_VERIFICATION.md`. I-10 implementation is complete for the approved V1 scope; WPML/Polylang runtime cases and the I-11 release gate remain open.
- 2026-11-06: Re-ran I-10/I-11 static checks: Composer JSON/PSR-4 mapping, no custom SPL loader, admin JavaScript syntax, whitespace, and merge-marker scans pass. No combined GitHub CI statuses were reported for the reviewed commit. I-10 implementation is 100%; I-11 remains 20% because the PHP/Composer and WordPress/WooCommerce runtime matrix is unavailable; see `docs/I10_I11_VERIFICATION.md`.
- 2026-11-07: Corrected I-10 reason-label resolution: optional WPML/Polylang translations apply to the registered source label, while explicit merchant locale overrides remain authoritative. Hook checks now correctly accept priority `0`. This correction has not been run against active WPML or Polylang installations; those runtime cases are tracked in I-11.
- 2026-11-06: Deep-audited I-02–I-09 implementation paths. Schema v3 now fails closed unless each expected table and required columns verify; feature services pause with an admin notice after a failed upgrade. Hardened malformed reason/translation data, report and history query errors, and manual event write failures. Static checks only; runtime acceptance remains in I-11.
- 2026-11-06: Moved all eleven PSR-4 classes into `includes/`, mapped Composer's namespace prefix to that directory, and removed the custom autoloader. Plugin bootstrap and uninstall directly require `vendor/autoload.php`. No third-party dependencies added. Composer/PHP are unavailable in this workspace, so vendor generation and runtime verification remain pending for final packaging. I-09 corrective-action implementation was added with schema v3, Pro REST routes, transactional action/target/history writes, bounded status/evidence histories, baseline/follow-up sequencing, and React review controls. Runtime package totals are now 8/11 (73%).
- 2026-11-06: Completed I-08 implementation: weekly scan status and suppression reasons are returned through REST and displayed in Alerts; below-80% mapping blocks evaluation and candidate-level sample/coverage gates are counted. Bounded storage is documented and I-08 is 100% at implementation scope; live runtime verification is assigned to I-11. Runtime work packages: 8/11 (73%).
- 2026-11-06: Completed product scope/options plan and proposed WooCommerce refund observation plus per-item admin capture.
- 2026-11-06: Added separate WordPress.org Free and WooCommerce.com Paid submission plan.
- 2026-11-06: Completed P3 logical data model, HPOS-safe ingestion, metric boundaries, privacy lifecycle, and performance plan. Added official design source links; no plugin implementation or CLI tooling added.
- 2026-11-06: Completed P4 React admin UX blueprint, merchant flow acceptance criteria, accessibility/state requirements, tier boundary, and REST contract outline. Overall planning readiness 86%; no plugin implementation or CLI tooling added.
- 2026-11-06: Completed P5 compatibility policy with dated WordPress 7.1/7.0 and WooCommerce 11.2/11.1 targets, PHP 8.3+, database floor, optional WPML/Polylang requirements, and release-time recheck rule. Planning artifact completion 88%; no plugin implementation or CLI tooling added.
- 2026-11-06: Prepared P6 decision register and ordered I-00–I-12 implementation backlog; aligned distribution guidance with the dated compatibility baseline.
- 2026-11-06: Added REST payload/permission contracts, privacy/security threat controls, approved no-build React approach, and Free-core/Pro-add-on packaging decision.
- 2026-11-06: Product owner delegated approval; D1–D12 approved, P6 marked complete, and planning artifact completion reached 100%. Runtime implementation is 0%; CLI/build tooling remains prohibited until the plugin is final.
- 2026-11-06: Implemented I-02 foundation files: plugin header/bootstrap, dependency checks, per-site activation guard, role capabilities, WooCommerce admin entry, non-destructive deactivation, and uninstall capability cleanup. Runtime work progress is 1/11 packages (9%); no runtime verification performed.
- 2026-11-06: Implemented I-03 reason catalog and manual-capture REST/data layer: initial dbDelta schema migration, canonical reason keys, custom reasons, protected reason/order/event routes, per-item validation, manual event idempotency, edit and audited soft-delete. Runtime work progress was 2/11 packages (18%). React capture UI, route runtime checks, and broader permission/accessibility verification remain; no CLI/build tools or tests were added.
- 2026-11-06: Implemented I-04 native refund observation with the WooCommerce post-save refund hook, CRUD object reads, schema v2 refund linkage, idempotent refund-scope event upsert, exact catalog-label mapping without retaining free text, and soft deletion on refund deletion. No physical-return inference, CLI/build tooling, or runtime tests were added.
- 2026-11-06: Implemented I-06 REST schemas and no-build React admin surfaces for overview counts, event history, manual item capture/edit/audited removal, reason catalog, and source counts/latest dates. No runtime tests, CLI, or build tooling were added.
- 2026-11-06: Implemented I-07 mature product/variation cohorts, bounded WooCommerce CRUD order reads, exact item/reason attribution, low-sample and incomplete-data rate suppression, fail-closed Pro entitlement, and the React patterns screen. Static source inspection only; runtime verification and a production entitlement provider remain.
- 2026-11-06: Opened I-08 with explicit alert/clustering acceptance criteria and a dated runtime-dashboard row.
- 2026-11-06: Added deterministic reason clusters, reversible assignments, weekly Pro alert evaluation, threshold evidence, stable dedupe keys, a full candidate scan capped at 2,000 rows with fail-closed overflow, and alert read/dismiss/restore endpoints. Added localized React cluster/alert review views and a bounded 50-entry action history. JavaScript syntax check passed; PHP/WordPress runtime verification remains. Local workspace lacks PHP, WP-CLI, MySQL, and Docker. A test ZIP was built, but browser policy blocked its install submission, so the test site was not changed.

- 2026-11-07: Read-only inspection of the authorized staging site confirmed WordPress 7.1.4 and WooCommerce 11.2.2, but WPML, Polylang, and Return Insights are not installed there. The site therefore cannot provide real I-10 runtime evidence in its current state; no plugin/site changes were made.

- 2026-11-07: I-11 source audit hardened WooCommerce order privacy export/erasure failure visibility. Database lookup/update errors are logged through WooCommerce's logger without user or order identifiers. This is source-level hardening only; PHP parsing and real privacy-flow execution remain unverified.

- 2026-11-07: Staging smoke test on WordPress 7.1.4/WooCommerce 11.2.2 exposed a strict REST query allowlist rejecting WordPress apiFetch's `_locale=user`. Added a query-only validator to ignore that transport parameter while preserving strict JSON body validation. Corrected build run `42377569578` passed lint/package checks; artifact `13575829765` was SHA-verified and updated successfully on staging. Overview, History, and Capture Sources now render expected zero-data states without the REST error. No synthetic records were created. I-11 is 45%; migration/CRUD writes, unauthorized-role checks, refund ingestion, Pro entitlement/backend flows, HPOS, privacy, accessibility, WPML/Polylang runtime, and release checks remain open. The tested Pro UI shows an upgrade gate without exposing the missing entitlement provider. A separate WordPress core `ob_end_flush()` notice persists.

- 2026-11-07: Extended staging smoke checks to the capture screen's read-only order lookup. Existing order #49 returned one line item and ordered quantity via the WooCommerce order API; no return insight was submitted. Manual event writes, permission matrix, migration assertions, refund lifecycle, HPOS/legacy, privacy, accessibility, and optional integrations remain unverified. I-11 progress adjusted to 50%.

- 2026-11-07: WooCommerce System Status confirmed PHP 8.3.36, WordPress 7.1.4, WooCommerce 11.2.2, HPOS enabled, `OrdersTableDataStore` active, and all five Return Insights tables present. A read-only capture lookup had already returned an item. The same report labels the environment type `production` despite the `/el-test/` URL, so I kept subsequent work read-only. I-11 progress is 55%; legacy mode and write/permission/privacy gates remain.

- 2026-11-07: Basic keyboard smoke check passed on the Return Insights admin: Tab reaches the WordPress skip links and first plugin tab after skip-to-main; Enter opens Overview and its data renders. This is limited keyboard evidence only; screen reader, RTL, contrast and full focus-order review remain open. I-11 progress is 60%.

- 2026-11-07: Staging System Status lists no WPML or Polylang plugin as active, while the Return Insights reason catalog loaded canonical English labels. This confirms only the optional integrations' absent-plugin fallback on this runtime; WPML String Translation and Polylang active cases remain unverified. I-11 progress is 65%.

- 2026-11-10: Added a mandatory evidence-driven execution protocol and replaced the vague reusable AI prompt with a task contract. “Continue” now means execute the highest-priority unblocked acceptance item in the same session; status-only replies are insufficient when work can proceed. Reconciled I-11 records: run `42637830397` passed PHP 8.3 lint and generated artifact `13683022555` (SHA-256 `0801dea18585d7ff19d8199bff45e17f8061351b5754550f8226378ce146ea0b`); this proves CI/package only. Earlier limited runtime checks belong to `wpessential.org/el-test`; TasteWP install was blocked before plugin installation. I-11 remains open and no percentage is treated as acceptance evidence.
