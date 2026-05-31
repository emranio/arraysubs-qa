---
id: 57
title: 'stage-07: Expired subscription exposes Reactivate action'
status: closed
priority: high
created: 2026-05-20T15:05:23.995097899+02:00
updated: 2026-05-22T03:34:40.627413246+02:00
started: 2026-05-22T03:31:27.319662575+02:00
completed: 2026-05-22T03:34:40.627412244+02:00
tags:
    - qa
    - stage-07
    - customer-portal
    - actions
claimed_by: mold-glade
claimed_at: 2026-05-22T03:34:40.627413146+02:00
class: standard
---

Task 11 matrix says Expired subscriptions should have no Subscription Actions section and no action buttons. Actual: #673 (Expired) renders Subscription Actions with Reactivate Subscription.

[[2026-05-22]] Fri 03:32
Plan: verify Stage 07 Task 11 and current #673; remove customer-portal reactivation eligibility for terminal statuses by default so Cancelled/Expired render no Subscription Actions and REST helper agrees; verify #673 expired and #668 cancelled in browser; confirm active #633 still unaffected.

[[2026-05-22]] Fri 03:34
Fix: terminal customer reactivation is now opt-in via arraysubs_customer_reactivatable_statuses filter, with no default statuses. This keeps Cancelled/Expired portal pages read-only per Stage 07 matrix. Verification: WP-CLI canReactivate=no for #673 expired and #668 cancelled. Browser QA: #673 has no Subscription Actions section and no Reactivate Subscription; #668 cancelled also has no Subscription Actions / Reactivate. Active #633 still shows Subscription Actions with Change Plan and Cancel Subscription.
