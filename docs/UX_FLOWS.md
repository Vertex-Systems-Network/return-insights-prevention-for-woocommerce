# UX Flows and Acceptance Criteria

**Status:** P4 planning artifact complete; implementation and usability validation have not started.

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
- Counts and rates show source and coverage context.
- If no data exists, the screen explains how to begin and offers no fabricated chart.
- Setup progress can be revisited and does not block access to available reports.

## Flow 2: Inspect a repeated pattern

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

## Flow 3: Create and measure a corrective action (Pro)

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

## Flow 4: Resolve a capture or data-quality issue

1. Merchant opens Capture sources or an event warning.
2. The screen identifies the affected source, last successful observation, and actionable mapping/error detail.
3. Merchant follows a supported retry or correction instruction.
4. The source status and affected coverage update after a successful refresh.

**Acceptance criteria**

- Permission errors do not expose event/customer data.
- Source unavailable and stale-data states explain which reports are affected.
- Retry does not create duplicate events.
- Error copy avoids raw stack traces and gives a stable reference for support.

## Flow 5: Edit a reason label

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
- No CLI, runtime code, or implementation tooling is introduced before P6 approval.

## Review dependencies

Before P6, reconcile endpoint schemas and permissions with `docs/REACT_UX.md`, event definitions in `docs/DATA_MODEL.md`, metric boundaries in `docs/ARCHITECTURE_DATA_FLOW.md`, tiers in `docs/OPTIONS_BANK.md`, and runtime targets in `docs/COMPATIBILITY.md`.