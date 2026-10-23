# Subscription Plans QA execution schedule

Scheduled day: 2026-10-24. Time zone: Asia/Dhaka. This is today's execution order, not a recurring automation or revival of a previous lifecycle schedule.

Plan: `qa/subscription-plans-2026-10-24/plan.md`.
Progress board: `qa/subscription-plans-2026-10-24/progress/kanban/`.
Site: `http://localhost:10013`.

| Order | Progress task | Execution block | Dependency / completion gate |
| --- | --- | --- | --- |
| 1 | #1 | Navigation, listing, status/type/search/sort/pagination | Feature assets built and local admin reachable. Record baseline product population. |
| 2 | #2 | Create a simple plan from Add new plan | #1 establishes the correct page/route. Keep the fixture for editing/actions. |
| 3 | #3 | Edit simple billing and optional configurations | #2 creates a dedicated fixture. Verify persisted values by reopening. |
| 4 | #4 | Variable parent and per-variation billing | Create or identify a safe variable fixture; record all child IDs. |
| 5 | #5 | Box and Pro bundle configuration editing | Premium/module availability recorded; use dedicated child products and compound fixtures. |
| 6 | #6 | Row actions and bulk behavior | Reuse only this cycle's fixtures for destructive actions. |
| 7 | #7 | Failure/access controls and core-only | Capture original plugin/module state first; restore it before the final regression. |
| 8 | #8 | Desktop/mobile/accessibility, final regression and cleanup | Repeat any repaired cases, preserve evidence, remove only cycle fixtures, confirm restored state. |

Execute all blocks on 2026-10-24 in this order unless an independent fixture allows safe parallel browser work in a separate session. A discovered failure pauses only its dependent cases: record the issue and evidence, investigate/fix it within scope, and rerun the affected browser flow. Keep unresolved cases visible rather than marking them passed because the day or current turn is ending.

For each block, move its progress task to in-progress, append the real result/evidence/fixture IDs, then move it to done after verification. New defects go into the preserved `qa/issues/` board with the plan/task/day fields. Existing unrelated issue #27 (completion-card descriptions) and #26 (shared mobile-header overflow) remain preserved and are not scheduled for repair by this feature cycle.

Supplemental independent block **#9**, scheduled for the same day: registered REST dispatch/data checks using dedicated fixtures in parallel with the browser blocks. Covers invalid updates, stale versions, anonymous/customer/editor permissions, optional billing metadata clearing, lifetime normalization, scheduled-sale/unrelated data preservation, and process-local core-only operation. This supplements browser task #7 without replacing it. Result/evidence: `evidence/rest-data-report.md`.
