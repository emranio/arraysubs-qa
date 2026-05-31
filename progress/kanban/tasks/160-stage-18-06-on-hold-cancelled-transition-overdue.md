---
id: 160
title: 'stage-18: 06 On-Hold → Cancelled Transition (overdue_payment)'
status: closed
priority: high
created: 2026-05-19T22:56:22.330615614+02:00
updated: 2026-05-24T21:38:19.033530042+02:00
started: 2026-05-23T08:06:53.473260223+02:00
completed: 2026-05-23T17:17:33.859522399+02:00
tags:
    - qa
    - stage-18
class: standard
---

Source: stages/18-renewal-followup/06-on-hold-to-cancelled-transition.md

[[2026-05-23]] Sat 17:17
QA complete with defects. Used #1467 member-decline / Standard Weekly / decline PM display 0341. Baseline from #159 cleanup: Active, next 2026-05-26 15:06:39 UTC, completed_payments 3. Time-traveled _next_payment_date to 2026-05-12 15:12:42 UTC (11 days overdue), ran generate #1299 -> renewal order #1520 pending, ran process #1311 -> Stripe card_declined, order #1520 failed, subscription became On Hold immediately with _on_hold_date empty (known #139/#142). Set _on_hold_date manually to 2026-05-15 15:13:37 UTC (8 days old), ran overdue checker #1302. Cancellation passed partially: #1467 became Cancelled, next payment cleared, _end_date/_cancelled_date set 2026-05-23 15:13:57, _cancelled_by=system, _cancellation_reason=overdue_payment. Browser detail verified Cancelled, Overdue payment, System, Subscription Cancelled email audit entries. Activity Audits verified System row: Status changed from On Hold to Cancelled. Scheduled-Job Logs showed Check Overdue Renewals / Process Renewal / Generate Upcoming Renewals rows. No pending Action Scheduler rows reference #1467. Failure: _pending_renewal_order_id still 1520 and order #1520 remained failed instead of cancelled; logged #144. Mail body/inbox verification remains blocked by #137. Final state intentionally left Cancelled per task.

[[2026-05-24]] Sun 21:38
Issue #144 fixed and verified. RenewalProcessor now cancels failed unpaid renewal orders during overdue cancellation and clears _pending_renewal_order_id. Live proof: #1467 cancelled for overdue_payment, order #3029 cancelled, pending meta empty, no pending AS actions for 1467. Screenshot: qa/artifacts/issue-144/subscription-1467-overdue-cancelled-order-3029-cancelled.png.
