---
id: 56
title: 'stage-07: On-Hold subscription exposes Cancel Subscription action'
status: closed
priority: high
created: 2026-05-20T15:05:23.858070413+02:00
updated: 2026-05-22T03:31:18.87131306+02:00
started: 2026-05-22T03:27:13.29311307+02:00
completed: 2026-05-22T03:31:18.871312178+02:00
tags:
    - qa
    - stage-07
    - customer-portal
    - actions
claimed_by: mold-glade
claimed_at: 2026-05-22T03:31:18.87131295+02:00
class: standard
---

Task 11 matrix says On-Hold subscriptions must not show Cancel Subscription. Actual: #653 (On Hold) renders Subscription Actions with Cancel Subscription plus Resume Now. Expected no Cancel/Change/Skip/Pause and only Resume for paused state.

[[2026-05-22]] Fri 03:28
Plan: verify Stage 07 Task 11 matrix and current #653 UI; fix core cancellation eligibility so customer cancellation allows Active/Trial/Pending but not On-Hold; keep REST and template using the same helper; verify #653 no longer renders Cancel Subscription while Resume Now remains visible; quick-control Active #633 still has customer actions.

[[2026-05-22]] Fri 03:31
Fix: core cancellation helper now excludes arraysubs-on-hold from customer-cancellable statuses, matching Stage 07 Task 11 action matrix. Pro auto-renew customer action/hint gating now excludes arraysubs-on-hold too, so paused/on-hold pages do not keep an otherwise empty Subscription Actions section. Verification: WP-CLI #653 canCancel=no while #633 active, #618 active, #384 trial canCancel=yes. Browser QA: #653 shows no Subscription Actions section, no Cancel Subscription, and still shows Manage Your Subscription > Resume Now. Active control #633 still shows Change Plan, Cancel Subscription, Skip Next Renewal, Pause Subscription, and Auto-Renew On.
