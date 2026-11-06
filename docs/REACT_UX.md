
# React Admin UX — Direction

**Status:** Design direction, not coded UI.

## Design intent

A professionally designed WooCommerce tool, not a stack of generic tables and charts. Clear hierarchy, restrained color, purposeful spacing, accessible type, and useful empty states. Reuse WordPress patterns where they improve familiarity.

## Proposed navigation

1. Overview — strongest reason patterns, evidence, recent changes.
2. Insights — reason/product/variation analysis with date/category/source filters.
3. Corrective actions — hypothesis, owner/status, dates, measurement window (Pro).
4. Reason catalog — canonical reasons and translated labels.
5. Capture sources — adapters, status, last import, mapping problems.
6. Settings — metric definitions, privacy, retention, alerts, license.

## Screen requirements

- Show total events separately from return rate; display denominator beside every rate.
- Drill down reason → product → variation while preserving filter context.
- Make low samples and missing data visible.
- Link corrective action to the issue; show pre/post windows and sample sizes.
- Keep reason identity stable while editing localized labels.
- Explain source provenance, sync errors, and duplicate prevention.
- Show capability states for Free/Pro; license expiry must not hide merchant data.

## Quality bar

Keyboard-operable screens, accessible labels/focus, color never the only signal, chart data table/summary, responsive admin layout, RTL support, and loading/empty/denied/error/integration-unavailable states. Avoid clutter and decorative motion.

## Design artifacts before implementation

Screen flows for first-run, reason capture, overview, drill-down, alert, action tracking, and no-data. Component inventory, token sheet, React-to-REST contract, capability matrix, and a clothing-store walkthrough where a variation has repeated size issues. The interface should recommend reviewing sizing detail without claiming causation.

## Build constraint

React is the chosen admin direction. Do not add source, compiled assets, package manifests, or build commands before P6 approval. Decide build pipeline at that gate; releases should ship built assets so merchants do not need CLI tooling.
