---
id: 91
title: 'stage-07: 11 Action Availability by Subscription Status'
status: closed
priority: high
created: 2026-05-19T22:56:11.010625383+02:00
updated: 2026-05-22T05:34:32.346813124+02:00
started: 2026-05-20T13:41:52.912790204+02:00
completed: 2026-05-20T15:05:33.553375168+02:00
tags:
    - qa
    - stage-07
claimed_by: mold-glade
claimed_at: 2026-05-22T05:34:32.346812813+02:00
class: standard
---

Source: stages/07-customer-portal/11-action-availability-by-status.md

[[2026-05-20]] Wed 15:05
QA notes: Active #643 shows Cancel, Change Plan, Skip, Pause, Update Shipping Address; no Resume/Undo. Trial #663 shows Cancel, Skip, Pause; no Change/Resume/Undo, but no shipping fixture/control (issue #58). On-Hold #653 shows Resume Now but also incorrectly shows Cancel Subscription (issue #56); no shipping fixture/control (issue #58). Pending #658 shows only Cancel Subscription and hides Change/Skip/Pause/Resume/Shipping/Undo. Cancelled #668 hides auto-renew but shows Reactivate under existing issue #45. Expired #673 incorrectly shows Subscription Actions > Reactivate Subscription (issue #57). Pending-cancel render verified on #683: banner + Active/Pending Cancellation + Cancels On + Undo Scheduled Cancellation; Cancel hidden; Skip/Pause visible; shipping cutoff message visible. Plan-switch setting cross-check passed: disabling plan_switching.allow_customer_switch hides Change Plan on #643, then setting restored.

[[2026-05-22]] Fri 03:31
Issue #56 fixed. On-Hold #653 rechecked in browser: no Subscription Actions section, no Cancel Subscription, Resume Now remains visible. Active #633 control still shows expected active actions. No PayPal/Paddle coverage.

[[2026-05-22]] Fri 03:34
Issue #57 fixed. Browser QA: expired #673 no Subscription Actions / Reactivate; cancelled #668 also no Subscription Actions / Reactivate; active #633 still shows active actions.

[[2026-05-22]] Fri 05:34
Issue #58 fixed: #663 Trial and #653 On-Hold now have shipping eligibility fixture meta. Browser verified Update Shipping Address visible for both; actions matrix can be verified for Trial/On-Hold shipping column.
