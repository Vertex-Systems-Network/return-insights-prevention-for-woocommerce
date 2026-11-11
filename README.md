# WPEssential Return Insights & Prevention for WooCommerce

Early implementation repository. P6 is approved. Source implementation for I-02 through I-10 is recorded; I-11 runtime/release verification remains open and I-12 submission preparation is gated by I-11.

## Product direction

Help WooCommerce merchants understand why items are returned, identify repeat product/variation issues, and track whether a corrective change helps. This is reason intelligence, not RMA/refund/exchange/carrier processing.

**Proposed tiers:** Free reason capture and basic reports. Pro proposed at $79/year for product/variation trends, reason clustering, alerts, and corrective-action tracking. Pricing and boundaries remain unapproved.

**Positioning reality:** Existing products already collect reasons and some advertise product/reason analytics. Proposed distinction is a focused prevention loop—evidence → correction → comparable measurement—not analytics alone.

## Roadmap

Reviewed 2026-11-11. Dates are planning estimates in UTC; a bar is shown only when its stated gate is actually complete. Planning documents, source implementation, runtime verification, and marketplace readiness are separate gates.

| Phase | Outcome | Status | Progress / evidence | Start | Target end |
|---|---|---|---|---|---|
| P0 Repository foundation | Product brief, scope guardrails, AI entry points | Complete | ██████████ 100% — repository rules and provider entry points present | 2026-11-06 | 2026-11-06 |
| P1 Market/settings research | Competitor workflows, options, platform evidence | In progress | Not scored — competitor evidence reconciliation remains in AI-02 | 2026-11-06 | Re-estimate after evidence review |
| P2 Product scope and options bank | Capture model and deduplicated capability/system inventory | Approved at P6 | ██████████ 100% — D1–D12 approval recorded | 2026-11-06 | 2026-11-06 |
| P3 Data-flow and engineering blueprint | Data model, attribution, metrics, privacy, performance | Blueprint recorded; implementation validation open | Not scored — I-11 evidence is separate | 2026-11-06 | Re-estimate at release review |
| P4 React admin UX specification | Screen map, interaction and accessibility | Blueprint recorded; rendered accessibility review open | Not scored — I-11 evidence is separate | 2026-11-06 | Re-estimate at release review |
| P5 Compatibility/release baseline | PHP, WordPress, WooCommerce, HPOS/legacy requirements | Baseline recorded; supported-version matrix open | Not scored — versions must be verified at release | 2026-11-06 | Re-estimate at release review |
| P6 Pre-development readiness gate | Resolve decisions and approve implementation scope | Complete; owner approval recorded | ██████████ 100% — approval and D1–D12 recorded | 2026-11-06 | 2026-11-06 |
| P7 Distribution and submission plan | WordPress.org Free + WooCommerce.com Paid tracks | Plan recorded; submission blocked by release gates | Not scored — I-11 and vendor/package prerequisites remain | 2026-11-06 | Re-estimate after I-11 |

Planning artifacts cover P0–P7, but that does not mean every research, runtime, or submission acceptance item is complete. I-02 through I-10 have source implementation recorded; I-11 release verification remains open and I-12 submission preparation is gated by I-11. No aggregate implementation percentage is reported because the package dashboard mixes source work and unverified runtime acceptance.

## Implementation status

P6 approval is recorded. Source implementation is present for I-02 through I-10; this describes implementation scope only, not release acceptance. I-11 runtime/security/accessibility/compatibility evidence remains open and I-12 submission preparation is gated by I-11. PHP classes use Composer PSR-4 mapping `WPEssential\ReturnInsights\` to `includes/`. The custom fallback loader has been removed; the generated Composer `vendor/autoload.php` is required at runtime. Free reports provide date-filtered event/reason/quantity/source counts and item-scoped product/category totals. Pro patterns compare mature purchase cohorts, use exact item-level confirmed-return attribution, and suppress rates for incomplete mapping, fewer than 20 sold units, missing denominators, and returns above sold quantity. WPML/WCML/Polylang-specific integrations are outside V1 support and release gates; standard WordPress gettext localization remains. Existing adapter source is not removed by this planning change. WordPress/WooCommerce privacy export/erasure hooks are now implemented; runtime behavior is still unverified. Runtime, visual, accessibility, compatibility, and commercial entitlement verification remain open. React uses WordPress bundled packages without JSX/build tooling. A private test ZIP builder is now authorized; it runs Composer only in isolated staging and does not add a frontend build pipeline.

## Composer autoload task status

| Task | Status | Progress | Evidence / remaining work |
|---|---|---:|---|
| Keep plugin classes under `includes/` | Complete | 100% | Eleven PSR-4 class files use case-matched names in `includes/`. |
| Map `WPEssential\ReturnInsights\` to `includes/` | Complete | 100% | `composer.json` declares the namespace prefix and directory. |
| Load `vendor/autoload.php` directly | Code updated | 100% | Plugin bootstrap and uninstall handler directly require Composer's generated loader; no project SPL loader remains. |
| Generate Composer vendor files and verify runtime | CI packaging pass; runtime pending | Not scored | GitHub Actions generates PSR-4 files in isolated packaging staging; activation/uninstall must be evidenced on a supported WordPress + WooCommerce runtime. |

## Distribution plan

- **Free edition:** submit a complete, production-ready package to the WordPress.org Plugin Directory.
- **Paid Pro edition:** submit separately to the WooCommerce.com Marketplace.
- For a freemium product, Woo currently requires separate Free and Paid submissions; they may be combined on one product page after approval.
- Woo Marketplace submission also requires an approved vendor account, product/business details, test instructions, compatibility with the two latest major WordPress and WooCommerce releases, HPOS, accessibility, and required Quality Insights Toolkit checks.
- Re-evaluate version targets, licensing, listing name, pricing, vendor terms, and packages at release. The current compatibility baseline is a planning target, not proof of future marketplace approval.

Details and official source links: docs/DISTRIBUTION.md. No submission has been made.

## Runtime work-package dashboard

This dashboard separates source implementation from I-11 evidence. "Implemented" never means that runtime/release acceptance passed.

| Package | Source implementation | Runtime/release evidence | Start | Target end |
|---|---|---|---|---|
| I-02 Foundation | Present | Activation, upgrade, dependency and uninstall matrix remains in I-11 | 2026-11-06 | Open |
| I-03 Catalog/manual capture API | Present | Limited staging lookup and reversible capture/edit/soft-delete recorded; full permission/DB/audit matrix open | 2026-11-06 | Open |
| I-04 Refund observation | Present | Refund lifecycle, HPOS/legacy writes and failure injection open | 2026-11-06 | Open |
| I-05 Free reports | Present | Runtime data accuracy, query failures, visual and accessibility checks open | 2026-11-06 | Open |
| I-06 REST + React admin | Present | Limited admin screens/keyboard smoke recorded; full role/accessibility/RTL review open | 2026-11-06 | Open |
| I-07 Pro product/variation patterns | Present; entitlement provider absent and fail-closed | Cohort math, data boundaries and entitled-provider behavior open | 2026-11-06 | Open |
| I-08 Pro clustering and alerts | Present | Persistence, weekly refresh, volume, visual/accessibility checks open | 2026-11-06 | Open |
| I-09 Corrective-action tracking | Present | Transaction/concurrency/failure-injection and Pro entitlement checks open | 2026-11-06 | Open |
| I-10 Third-party integration scope | No third-party adapters approved for V1 | WPML/WCML/Polylang excluded from V1 support claims and runtime gates | 2026-11-06 | 2026-11-11 |
| I-11 Runtime, security, accessibility and release verification | Source hardening plus limited staging smoke checks | Open; Ahead Earth package/UI smoke and Pro-section keyboard navigation passed partially. SA-03 transactional erasure source fix passed CI; runtime/failure injection and SA-02 pagination/error-path checks remain open (new Woo query DB errors now fail closed; runtime injection still required) alongside concurrency, behavioral tests, compatibility and full accessibility review | 2026-11-06 | Re-estimate after gate review |
| I-12 WordPress.org Free + WooCommerce.com Paid submissions | Checklist/package-model preparation | In progress (prep only) — no final Free + Pro pair, vendor application, or submission | 2026-11-10 | Re-estimate after I-11 |

Dates after actual work started are estimates and are not delivery promises. The repository currently has no dedicated automated behavioral test suite; CI lint and package validation do not establish behavior. See `docs/AI_BACKLOG.md` AI-13 and I-11's test strategy before release. All contributors follow `docs/EXECUTION_PROTOCOL.md`.

## Current gate

P6 passed on 2026-11-06. D1–D12 are approved and recorded in docs/P6_READINESS_GATE.md. Source implementation for I-02 through I-10 is recorded; I-11 release verification remains open and I-12 is gated. I-08 and I-09 are complete at implementation scope only. I-08 includes deterministic clusters, reversible assignments, weekly Pro evaluation, a full candidate scan capped at 2,000 rows with fail-closed overflow, ≥80% mapping threshold, per-candidate sample/rate gates, stable deduplication, bounded 2,000-alert storage and 50-entry action history, persisted scan status, and React cluster/alert review UI. The Alerts screen surfaces coverage blocks and withheld-candidate reasons. WPML/WCML/Polylang-specific compatibility and runtime checks are not V1 release gates; standard WordPress gettext localization remains. Runtime, visual, accessibility, compatibility, privacy exporter runtime behavior (SA-02 source paging added; runtime/error-path checks open), eraser rollback/failure-injection runtime behavior (SA-03 source fix passed CI, runtime pending), action-workflow, entitlement-provider, concurrency, cron lifecycle, and behavioral-test verification remain tracked in I-11. On 2026-11-09, per-item manual insight writes were serialized with MySQL advisory locks; duplicate retries are checked before quantity validation and rechecked under the lock. GitHub Actions run 42637830397 passed PHP 8.3 lint and package validation; this is CI/package evidence only. On 2026-11-11, the exact package from CI run `42936569058` was installed and updated on the authorized Ahead Earth site; selected Free screens and the consistent Pro feature gate rendered. I-11 remains open for the remaining behavioral, security, compatibility, accessibility and release checks. Evidence is recorded in `docs/I10_I11_VERIFICATION.md`. A 2026-11-10 privacy-export source audit fixed a database-failure path that previously marked an incomplete export successful; WordPress now receives `WP_Error`. CI run `42703752886` passed lint/package checks. The WordPress privacy-export runtime/failure-injection check remains open. A route-by-route source audit found explicit capability callbacks on every registered REST route; read/manage permissions and fail-closed Pro gates match the approved contract. Dynamic authorization tests remain open. A corrective-action transaction audit also fixed stale concurrent status reads and unchecked target-delete, history-trim, and COMMIT failures; CI run `42705241357` passed. Live concurrency and database failure-injection tests remain open. Evidence capture now locks/rechecks action state to prevent concurrent duplicate baselines and stale target/window snapshots; CI run `42705553668` passed, with live concurrency tests still pending. On `wpessential.org/el-test`, earlier package/install and limited smoke checks are recorded below; separately, the current authorized Ahead Earth site completed package upload/update and limited screen smoke checks. The `_locale=user` query-validation defect was fixed and the corrected artifact was installed. Free overview/history/catalog/source screens, expected Pro upgrade gate, and read-only lookup of one order's line item were smoke-checked. After the test site was authorized for a narrow manual-capture smoke check, one clearly temporary insight for order #49/item #1 was created, edited, and soft-deleted with an audit note; History returned to empty. No WooCommerce order or refund was changed. WooCommerce reports HPOS active and lists the plugin's five tables. Its environment type reports `production` despite the `/el-test/` path; this limits further testing to controlled reversible plugin-only checks until broader release testing is defined. Broader runtime, security, compatibility, and accessibility verification remains in I-11. Composer manifest maps PSR-4 only; the private test-package workflow generates Composer files in a temporary staging directory. No third-party dependency, generated vendor files, or frontend build tooling is committed.



## Audit and AI handoff controls

The 2026-11-10 plan/source audit found and corrected conflicting provider instructions, unsupported 100% planning bars, an implementation count that mixed I-10 work with I-11 release acceptance, and five static release findings (SA-01–SA-05) now tracked in the verification ledger. The repository has no dedicated automated behavioral test suite; this is now an explicit AI-13/I-11 planning item. GitHub `main` is the source of truth. While the user's current instruction applies, do not recreate a local checkout, download artifacts locally, or deliver files/ZIPs in chat. See `AGENTS.md`, `docs/AI_BACKLOG.md`, and `docs/EXECUTION_PROTOCOL.md` for the model handoff and execution rules.

## Plan files

- docs/PRODUCT_SPEC.md — vision, scope, capture model, tiers, decisions.
- docs/MARKET_RESEARCH.md — competitor evidence, settings, and positioning.
- docs/OPTIONS_BANK.md — unique capabilities, systems, competitor-option disposition.
- docs/ARCHITECTURE_DATA_FLOW.md — data lifecycle and boundaries.
- docs/DATA_MODEL.md — logical schema, idempotency, migrations, privacy, query plan.
- docs/COMPATIBILITY.md — platform baselines and V1 support boundaries.
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
6. Standard WordPress gettext localization is in scope. WPML, WooCommerce Multilingual/WCML, and Polylang-specific support and testing are out of V1 scope.
7. P6 is passed; implementation follows the approved backlog. The only current packaging exception is the private test ZIP workflow (`tools/build-artifact.py` and its GitHub Actions workflow); no general CLI, WP-CLI, or frontend build pipeline.
8. AI agents read this README and AGENTS.md, make scoped changes, and update the dashboard in the same update.
9. PHP classes follow Composer PSR-4: `WPEssential\ReturnInsights\` maps to `/includes/` in `composer.json`; plugin bootstrap and uninstall directly require generated `vendor/autoload.php`. The test packager runs Composer only in temporary staging; do not commit generated vendor files or add third-party dependencies.

## Research snapshot

Dated platform review (2026-11-06): WordPress 7.1.4 and WooCommerce 11.2.2 are the latest releases listed in the official release archives. Planned support matrix targets WordPress 7.1/7.0 series and WooCommerce 11.2/11.1 series, with PHP 8.3+, MySQL 8.0+ or MariaDB 10.11+, HTTPS. Woo Marketplace requires the two latest major WP and WC release series; refresh these moving targets before implementation and again at submission. WPML, WooCommerce Multilingual/WCML, and Polylang-specific integrations are not V1 support targets or release gates. Standard WordPress gettext localization remains in scope. See docs/COMPATIBILITY.md for the current compatibility policy.

Recommended MVP capture model: observe native WooCommerce refund records and allow per-item admin insight entry for non-refund returns. A refund is not proof that an item physically returned, and an order-level reason must not be guessed onto every line item.

P3 architecture specifies indexed custom event storage, source-level idempotency, bounded background aggregation, separate return/refund metrics, and privacy export/erasure. See docs/ARCHITECTURE_DATA_FLOW.md and docs/DATA_MODEL.md.

## Change log
- 2026-11-11: Read-only keyboard smoke on Ahead Earth: Shift+Tab moved focus to Alerts and Enter selected it; DOM reported the active section with `aria-current="page"`. No records changed. Full accessibility review remains open.
- 2026-11-11: Hardened SA-02 against WooCommerce order-query database errors that appear as empty results: the exporter now checks `$wpdb->last_error` and query count around the CRUD lookup. CI run [42945832568](https://github.com/Vertex-Systems-Network/wpessential-return-insights-prevention-woocommerce/actions/runs/42945832568) passed PHP 8.3 lint/package checks (commit `56fccef64d7e89d21a15826855a407ef0cc7a1d5`). Runtime failure injection and legacy-store checks remain open.
- 2026-11-11: Audited WooCommerce 11.2.2 order-query source for SA-02. HPOS uses `$wpdb->get_col()` behind the CRUD query wrapper; the plugin catches thrown failures, but HPOS/legacy silent-empty behavior remains unverified and is explicitly gated on runtime failure injection.
- 2026-11-11: Added bounded customer order-linked privacy export to the plugin's paged WordPress exporter; Woo order filter duplication was removed. CI run [42944762236](https://github.com/Vertex-Systems-Network/wpessential-return-insights-prevention-woocommerce/actions/runs/42944762236) passed PHP 8.3 lint/package checks (commit `aea0eb42c6f5e761a59424015aeeeda70cdfd7c4`). Runtime/error-path/overflow/HPOS/legacy checks remain open; pages over 500 linked events return an explicit export error.
- 2026-11-11: SA-03 order-erasure handling now uses WooCommerce's boolean privacy-erasure filter and transactional anonymization; CI run [42943190251](https://github.com/Vertex-Systems-Network/wpessential-return-insights-prevention-woocommerce/actions/runs/42943190251) passed PHP 8.3 lint/package validation (commit `ce955ec2927f98ca3844ce5decfcdd03ebb9bb0a`). Runtime/failure-injection checks remain open; SA-02 order-linked export is unresolved. No I-11 closure claimed.
- 2026-11-11: Updated the exact run 42936569058 package on the authorized Ahead Earth site. WordPress 7.1.4/WooCommerce 11.2.2/PHP 8.3.36/MySQL 8.0.46, HPOS, active plugin, Free capture/reason screens, and Pro upgrade wording were observed. The host reports `production`, so no order, refund, or plugin records were mutated. SA-01 source quota lock passed CI run 42936569058; its concurrency runtime check remains open. The I-11 ledger has the detailed evidence. I-12 remains preparation-only.

- 2026-11-10: Fixed the SA-04 cron lifecycle mismatch: plugin deactivation now clears the weekly alert hook. GitHub Actions run [42925662732](https://github.com/Vertex-Systems-Network/wpessential-return-insights-prevention-woocommerce/actions/runs/42925662732) passed PHP lint and package validation; schedule/deactivation/reactivation runtime checks remain pending, so I-11 remains open.

- 2026-11-10: Began I-12 preparation-only review and performed the initial Ahead Earth runtime setup. WooCommerce activation showed an unexplained 312-character-output warning; admin remained reachable. A later 2026-11-11 upload/update and limited smoke check is documented above and in `docs/I10_I11_VERIFICATION.md`. I-12 remains blocked from submission by I-11 and vendor/final-package prerequisites.

- 2026-11-11: Removed WPML, WooCommerce Multilingual/WCML, and Polylang-specific support and runtime tests from V1. Standard WordPress gettext localization remains. Existing includes/Multilingual.php adapter source was not removed; code cleanup is a separate implementation decision. Earlier dated staging notes remain historical evidence, not a support commitment.


- 2026-11-10: Deep plan/source audit repaired the AI handoff and progress model. Provider entry points now route to canonical rules; Composer PSR-4 constraints are consistent. Removed unsupported aggregate completion claims and separated source implementation from I-11 runtime/release evidence. The source review recorded five open release findings (action quota concurrency, privacy callback failure visibility, alert cron lifecycle, and missing behavioral/JS CI coverage) in `docs/I10_I11_VERIFICATION.md`; AI-13 now carries the test strategy. Current gate states are summarized here; old dated progress entries remain historical snapshots. No local checkout or artifact was recreated or downloaded.

- 2026-11-09: Hardened manual insight REST reads after source audit. Database errors from idempotency lookup and event reads now return safe server errors; quantity-limit validation fails closed on database query errors; create/update paths verify the saved row before serialization. This addresses a source-level fail-open/fatal edge case but does not replace REST abuse, race-condition, or database failure-injection testing. The source push triggers the PHP-lint/package GitHub Action; its result is not yet recorded. I-11 remains open; no checklist-based percentage is claimed.

- 2026-11-09: Prepared the user-provided TasteWP site `quirkybrake.s2-tastewp.com` for WooCommerce runtime testing. WordPress 7.1.4 was observed; official WooCommerce 11.2.2 was installed and the fresh Installed Plugins screen confirmed it active, although the activation redirect displayed a WooCommerce translation-loading notice. The current Return Insights test ZIP was selected in the upload form, but the browser blocked the final Install Now action; the plugin was not confirmed installed on this site. No I-11 progress increase; continue only after a supported upload flow is available.

- 2026-11-08: Fixed a stale-order lookup race: changing the order ID now clears prior order/item state, and capture controls remain disabled during lookup. CI run `42439188546` passed and its artifact was installed on staging. Invalid order `999999999` showed “Order not found,” cleared the prior Hat selection, and kept Save disabled; valid order `49` then reloaded its single Hat line item. History remained empty. No insight, WooCommerce order, or refund was changed. I-11 remains open; no checklist-based percentage is claimed.

- 2026-11-08: Fixed the manual insight edit request to send only REST-supported PATCH fields (`event_kind`, `quantity`, `reason_id`, `occurred_at`). CI run `42434063755` passed PHP lint and test ZIP packaging; artifact `13598489437` was installed on staging. A test insight for order #49/item #1 was created, changed from `Other` to `Changed mind`, then soft-deleted with an audit note; History returned to the empty state. No WooCommerce order/refund was modified. The UI also blocked quantity 2 for an item ordered at quantity 1 before submission; server-side boundary testing remains open. I-11 remains open; no checklist-based percentage is claimed. Remaining release gates are listed in `docs/I10_I11_VERIFICATION.md`.

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

- 2026-11-10: Fixed the privacy personal-data exporter so database query failure returns a logged `WP_Error` instead of a synthetic exported-data row with `done=true`. Official WordPress core exporter handler accepts `WP_Error`; see `docs/I10_I11_VERIFICATION.md`. The initial lint run `42703690672` caught a namespace-escape typo; the immediate follow-up correction passed PHP 8.3 lint in run `42703752886`, along with Composer PSR-4 packaging, ZIP validation and artifact upload; artifact `13712606784` (GitHub archive digest `sha256:48cbef0c3726d26e02385bcf38b2b8bcb005e061b7be4be32c7ba90dfdd9865f`, expires 2026-11-23). Runtime export and DB failure-injection remain unverified.

- 2026-11-10: Audited all registered REST route permission callbacks against `docs/P6_TECHNICAL_CONTRACTS.md`. Each route has an explicit callback; read routes, manager mutations, order lookup, and Pro entitlement gates match the approved matrix. Recorded the route groups and exact runtime follow-up cases in `docs/I10_I11_VERIFICATION.md`. This is static evidence only; no role changes or unauthorized requests were run.

- 2026-11-10: Hardened corrective-action transactions. Status updates now lock and reread the action row before writing status history; target replacement, history retention, and transaction commit failures fail closed. GitHub Actions run `42705241357` passed PHP 8.3 lint, Composer PSR-4 packaging, ZIP validation and artifact upload (artifact `13712508716`, digest `sha256:05ab51cd6ed54829503f83b0636e22d2217e7d3d996372bcc14fba25f9140d6d`, expires 2026-11-23). Runtime concurrency and DB failure-injection remain unverified.

- 2026-11-10: Serialized corrective-action evidence phase checks under an action-row lock. Capture re-reads action configuration and existing snapshots before insert, rejecting stale settings or duplicate baseline requests; CI run `42705553668` passed PHP 8.3 lint, Composer packaging, ZIP build and artifact upload (artifact `13712978338`, digest `sha256:996132f3f9cb98e57d1132a5f4577adeccf70fc12a30f309739c564a1fc3f2ea`, expires 2026-11-23). Live concurrency verification remains open. A detail-query audit also fixed a fail-open read: targets, history and evidence lookups now each check database errors immediately. CI run `42707097395` passed; failure-injection verification remains pending.

- 2026-11-10: Fixed `ActionService::hydrate_many()` to check targets, history and evidence query errors individually before the next query can reset `wpdb` error state. CI run `42707097395` passed PHP 8.3 lint, Composer packaging, ZIP validation and artifact upload (artifact `13713609300`, expires 2026-11-23). Database failure-injection remains open.


### Ahead Earth follow-up UI verification (2026-11-11)

On the authorized Ahead Earth site, the installed Return Insights page loaded at the WooCommerce submenu route. Read-only checks confirmed the empty Overview and History states, the reason catalog, and consistent Pro gates across Trends, Alerts, and Corrective Actions. No plugin-specific console errors appeared. No store or plugin records were changed. This is limited UI smoke evidence; I-11 remains open for behavioral, security, privacy, compatibility, accessibility, and release verification.


### WooCommerce privacy contract review (2026-11-11)

Source review confirmed WooCommerce's order-export filter must return an array, while WordPress's registered privacy exporter can return `WP_Error`. WooCommerce's order-erasure extension point is a void action and does not surface plugin failures to its eraser response. SA-02/SA-03 remain open; the ledger records the required bounded, WooCommerce CRUD-based privacy callback redesign. No source code or runtime behavior was changed by this review.
