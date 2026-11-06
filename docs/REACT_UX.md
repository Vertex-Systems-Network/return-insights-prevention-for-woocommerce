# React Admin UX — Specification

**Status:** P4 specification approved; I-05 Free reports, I-06 admin surfaces, and I-07 Pro patterns screen are implemented. Visual QA, usability, accessibility, and runtime verification remain for I-11.

## Product UX principle

Lead merchants from a repeatable signal to a practical review and a measurable follow-up. The product does not process RMAs, authorize returns, issue refunds, or claim that a pattern proves why a customer acted. I-07 adds an entitlement-gated patterns area with maturity/cohort selectors, current/comparison rates, eligible sold and confirmed-return quantities, percentage-point changes, and explicit unavailable/coverage states.

Use the WordPress admin visual language and WooCommerce extension conventions. Build a distinct, considered information hierarchy rather than a generic grid of KPI cards. Respect the selected wp-admin color scheme, AA contrast, consistent terminology, and WordPress accessibility standards. Avoid promotional UI and decorative motion.

## Information architecture

| Area | Purpose | Tier |
|---|---|---|
| Overview | A concise evidence-led summary, highest-priority patterns, coverage, and open actions | Free + Pro |
| Insights | Filter and explore reason, product, and variation patterns | Basic Free; product/variation trend depth Pro |
| Corrective actions | Record a hypothesis, owner, status, review window, and before/after evidence | Pro |
| Reason catalog | Manage canonical reason keys and merchant-facing labels | Free + Pro |
| Capture sources | Understand refund observation and manual insight entry health and provenance | Free + Pro |
| Settings | Configure capture, metrics, privacy/retention, notifications, language, and license | Free + Pro |

Navigation must preserve filter context when moving Overview → Insights → product → variation. Pro-only controls remain discoverable with an accurate tier explanation; expired licenses never hide or delete merchant data.

## Screen specifications

### Overview

Prioritize one useful pattern and its evidence, then coverage and action status. A pattern summary contains reason, affected product/variation when supported, count, date window, source, sample size, comparison baseline, and a link to inspect details. Do not imply a trend from a single event or weak sample. Explain what action is available without asserting causality.

Separate event counts, confirmed-return rates, and refund ratios. Every rate shows numerator, denominator, date window, and source coverage. If the denominator is unavailable, show “not enough data” rather than estimating.

### Insights and drill-down

Filters: date range, reason, product/category, variation, and source. Use server-side pagination and stable sorting for event lists. Show provenance and mapping state on each event. The drill path is reason → product → variation; do not assign an order-level refund reason to every line item.

Clothing example: repeated “size mismatch” observations on one variation can suggest reviewing that variation’s sizing guidance, fit notes, and size chart. Phrase this as a review suggestion, not proof that the chart caused returns.

### Corrective actions (Pro)

Create an action from an insight or manually. Record issue link, proposed change/hypothesis, owner, status, created date, planned review date, comparison windows, and notes. Statuses: proposed, in progress, monitoring, completed, paused. Keep an auditable history of status changes. Post-action comparison must show pre/post windows and sample sizes and use “associated change” language, not causal claims.

### Reason catalog

Show canonical reason identity separately from localized display labels. Support active/inactive state, ordering, and duplicate/near-duplicate guidance. Do not merge historical records silently when labels change. A translated label does not create a new reason key.

### Capture sources and settings

Explain native refund observation versus manual per-item insight entry. Show source state, last successful observation, last error, mapping warnings, and duplicate/idempotency status in merchant language. Offer retry or corrective steps only when supported.

First-run setup guides reason review, capture-source explanation, metric definitions, and data coverage. Never start a historical backfill automatically. Any later backfill must disclose scope and offer progress, pause/cancel, and resumability.

## States and accessibility

Every screen defines loading, first-use empty, filtered-empty, low-sample, incomplete-coverage, unknown mapping, stale data, permission denied, source unavailable, recoverable error, and upgrade/license states where relevant. Error states explain the impact and the next useful step.

Keyboard operation, visible focus, programmatic labels, semantic headings, announced async status, and accessible validation are required. Color is never the only state signal. Charts require an equivalent data table or textual summary. Support RTL layout and translated labels without changing canonical reason keys. Respect reduced-motion preferences; motion is not needed for core comprehension.

## Component and implementation direction

Use WordPress-provided React through the `wp.element` global, components through `wp.components`, and `wp.apiFetch` for REST. Enqueue registered handles as dependencies and load component styles correctly. To preserve the no-CLI constraint, use browser-ready JavaScript without JSX, transpilation, npm packages, or a build step in this repository. Avoid unsupported internal WooCommerce APIs; verify RTL behavior.

All data access goes through namespaced REST endpoints. The server enforces authentication, capability checks, validation, and response shaping; hiding a UI control is not authorization. Paginated collections use stable ordering and explicit totals/cursors. Mutations return validation errors in a consistent field-aware format. All REST timestamps use an explicit timezone convention and all metric responses include their definition/version and coverage metadata.

### Proposed endpoint families (contract outline only)

| Endpoint family | Read/write intent | Required response context |
|---|---|---|
| `/insights/v1/summary` | Read overview for filters | I-05 implements date-windowed event/quantity counts, reason/source groups, mapping coverage, and top item-scoped product/category groups. No return rates. |
| `/insights/v1/patterns` | Read/filter patterns and drill-downs | Sample size, source, confidence/quality flags |
| `/insights/v1/events` | Read paginated captured events | Provenance, mapping state, stable pagination |
| `/insights/v1/reasons` | Read; manage reason catalog | Canonical key, localized labels, active state |
| `/insights/v1/sources` | Read source coverage | I-06 currently provides event counts and latest dates by source; health/error monitoring remains future work. |
| `/insights/v1/actions` | Pro read/create/update actions | Status history, windows, linked pattern |
| `/insights/v1/settings` | Read/update permitted settings | Schema/version, validation, capability gates |

The implemented namespace is `/wp-json/wpessential/return-insights/v1`. I-06 provides `/reasons`, `/reasons/{id}`, `/orders/{id}`, `/events`, `/events/{id}`, and `/sources`; I-05 adds `/summary`. Method schemas and behavior are documented in `docs/P6_TECHNICAL_CONTRACTS.md`. Future `/patterns`, `/actions`, and `/settings` routes remain planned, not implemented.

## I-06 implementation boundary

The current UI provides date-filtered evidence totals, reason/event-kind/source tables, mapped/unmapped reason coverage, and top item-scoped product/category count tables. It does not calculate return rates, sales-denominator metrics, trends, product variation comparisons, or corrective actions. Product/category tables exclude refund-scope records because line-item attribution is not known; category membership can overlap. I-07/I-09 own deeper Pro insights and action tracking. Event lists use server pagination (20 by default, API max 100) and can filter by order ID. Refund observations remain read-only and distinct from confirmed physical returns. The UI uses WordPress-bundled `wp.element`, `wp.components`, and `wp.apiFetch`, without JSX/build tooling.

## Tier boundary

Free includes reason capture, reason catalog, capture status, and basic reason-level reports with transparent counts/coverage. Pro adds product/variation trends, clustering, alerts, and corrective-action tracking. Avoid presenting clustering as an external AI dependency; external AI remains off by default and requires a separately reviewed privacy/consent design.

## Design artifacts and build constraint

The companion `docs/UX_FLOWS.md` defines journeys and acceptance criteria. Before implementation, validate these approved flows against the metric/data model and current platform components. UI source may be added after P6 approval. Do not add CLI/build tooling or package manifests to this repository until the plugin is final. Use WordPress-bundled packages; no compilation step is planned.

## Official design references

- WordPress components: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-components/
- WordPress API Fetch: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-api-fetch/
- WordPress accessibility: https://developer.wordpress.org/block-editor/how-to-guides/accessibility/
- WooCommerce extension accessibility: https://developer.woocommerce.com/docs/extensions/best-practices-extensions/accessibility
- WooCommerce extension best practices: https://developer.woocommerce.com/docs/extensions/ux-guidelines-extensions/best-practices
- WooCommerce colors and contrast: https://developer.woocommerce.com/docs/extensions/ux-guidelines-extensions/colors

Reviewed 2026-11-06. WordPress-provided React globals and the no-build constraint were approved at P6.
