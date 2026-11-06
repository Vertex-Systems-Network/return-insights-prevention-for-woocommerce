# UX Flows and Acceptance Criteria

**Status:** P4 planning artifact complete; I-05 adds Free reports, I-06 implements overview/capture/history/catalog/source flows, and I-07 implements entitlement-gated Pro pattern review. Usability and runtime validation remain.

This file converts the React admin direction into reviewable merchant journeys. Metric and data decisions in the product and architecture specifications remain authoritative.

## Flow 1: First run and data coverage

1. Merchant opens Return Insights & Prevention.
2. Overview explains the two capture sources: observed WooCommerce refunds and manually recorded per-item return insights.
3. Merchant reviews the reason catalog and edits display labels if needed.
4. Overview states which data is available, the period covered, and what cannot yet be calculated.
5. Merchant can proceed without enabling any external AI service or historical backfill.

**Acceptance criteria**

- Refunds are never described as confirmed physical returns.
- No historical backfill starts automatically.
- I-05 reports show captured event counts and coverage with source context; no return rate is shown because eligible sales denominators are not yet implemented.
- If no data exists, the screen explains how to begin and offers no fabricated chart.
- No setup wizard blocks the available count-only screens; these are preliminary coverage information, not reports.

## Flow 2: Review a Free evidence report

1. Merchant opens Overview and sees the default last-30-days reporting period in the store timezone.
2. Merchant changes inclusive start/end dates and submits the filter.
3. The screen shows captured event count, summed recorded quantity, mapped/unmapped reason coverage, and reason/event-kind/source tables. Tables are limited to the top 20 reasons and top 10 products/categories.
4. Product and category tables show only item-attributed evidence; category membership may overlap.
5. An empty period explains how to widen the range or add an item-level record.

**Acceptance criteria**

- UI totals come from the server summary endpoint and are filtered by `occurred_at_gmt` within converted store-local date bounds.
- Refund-scope evidence remains a refund observation and is never presented as a confirmed physical return.
- The interface shows counts and recorded quantities; it does not imply a return rate, causal reason, or sales denominator.
- Mapping coverage states numerator and denominator; it is not a product performance rate.
- Empty and loading states do not fabricate values.

## Flow 3: Inspect a repeated pattern

1. Merchant opens a pattern from Overview or Insights.
2. Pattern detail shows reason, date window, observed count, data source, sample size, and available comparison.
3. Merchant filters or drills from reason to product and then variation.
4. For a repeated clothing size-mismatch pattern, the interface suggests reviewing sizing/fit details and the size chart.
5. Merchant can inspect the supporting events and their provenance.

**Acceptance criteria**

- Drill-down preserves filters and date window.
- An order-level reason is not copied onto every product line.
- “Return rate” appears only when a valid confirmed-return denominator exists; refund ratio is separately named.
- Low samples, incomplete coverage, and unknown mappings are visible.
- Recommendations use review/hypothesis wording and do not claim causation.

## Flow 4: Review mature product/variation patterns (Pro)

1. Merchant opens the Patterns section when Pro entitlement is available.
2. Merchant selects maturity and comparison windows from 30/60/90/120/180 days.
3. The view compares the current and preceding mature cohorts by product/variation and reason.
4. The merchant reviews sold and confirmed-return quantities, eligible rates, rate changes, and data-quality notices.
5. Low-volume or incomplete patterns explain why a rate is unavailable; the UI does not estimate missing values.

**Acceptance criteria**

- The endpoint requires `view_return_insights` and an entitlement provider; the Free core defaults to locked.
- Only exact item-mapped confirmed returns count; refund observations and exchanges are excluded.
- Incomplete mapping, fewer than 20 sold units, missing denominator, or returned quantity above sold quantity suppresses rates.
- Recommendations remain review prompts and do not claim causation.

## Flow 5: Create and measure a corrective action (Pro)

1. Merchant starts an action from a pattern.
2. Merchant writes a proposed change, assigns an owner, selects a status, and sets a review date/window.
3. The action remains linked to the originating pattern and records status changes.
4. At review time, the merchant compares pre/post windows with sample sizes and coverage displayed.
5. Merchant marks the action complete, continues monitoring, or pauses it.

**Acceptance criteria**

- The action records a hypothesis and dates; it does not automatically edit products.
- Comparison periods and counts are visible and comparable.
- Sparse or mismatched data produces an unavailable/limited comparison state.
- Copy describes changes as associated with the action, not proven to be caused by it.
- Action history is retained according to the approved retention policy.

## Flow 6: Resolve a capture or data-quality issue

1. Merchant opens Capture sources or an event warning.
2. The screen identifies the affected source, last successful observation, and actionable mapping/error detail.
3. Merchant follows a supported retry or correction instruction.
4. The source status and affected coverage update after a successful refresh.

**Acceptance criteria**

- Permission errors do not expose event/customer data.
- Source unavailable and stale-data states explain which reports are affected.
- Retry does not create duplicate events.
- Error copy avoids raw stack traces and gives a stable reference for support.

## Flow 6: Edit a reason label

1. Merchant edits a reason’s display label or its translated labels.
2. The canonical reason identity remains stable.
3. Existing event history and reports continue to use the same reason key.
4. Merchant sees duplicate guidance if a new reason closely matches an existing one.

**Acceptance criteria**

- Label edits never silently rewrite historical event identity.
- Localized labels do not create duplicate reason keys.
- Inactive reasons remain understandable on historical records.

## Cross-flow review checklist

- WordPress admin conventions, consistent plain-language terms, keyboard and screen-reader operation.
- Visible focus, labels, headings, status announcements, validation, and non-color state cues.
- Charts have a table or textual equivalent; responsive layout and RTL are accounted for.
- Loading, empty, denied, stale, low-sample, incomplete, and recoverable-error states are specified.
- Capability checks are enforced by the REST server, not only by hiding controls.
- External AI remains off by default and does not receive store data under these flows.
- P6 is passed; scoped runtime tasks may proceed. No CLI/build tooling is added to the repository until the plugin is final.

## Review dependencies

Before P6, reconcile endpoint schemas and permissions with `docs/REACT_UX.md`, event definitions in `docs/DATA_MODEL.md`, metric boundaries in `docs/ARCHITECTURE_DATA_FLOW.md`, tiers in `docs/OPTIONS_BANK.md`, and runtime targets in `docs/COMPATIBILITY.md`.