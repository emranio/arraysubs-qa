---
id: 101
title: 'stage-14: Cancelled subscription keeps future renewal reminder action'
status: closed
priority: high
created: 2026-05-23T12:02:49.523677397+02:00
updated: 2026-05-24T15:54:17.958255991+02:00
started: 2026-05-24T15:42:57.619657951+02:00
completed: 2026-05-24T15:54:17.958254879+02:00
tags:
    - qa
    - stage-14
    - admin-subscriptions
    - lifecycle
    - scheduler
claimed_by: shell-quartz
claimed_at: 2026-05-24T15:54:17.958255891+02:00
class: standard
---

Stage 14 task 08 Sub-X #1135: after On Hold -> Cancelled via admin status dropdown, detail page showed Cancelled and cancellation details, but pending future scheduled action remained: action_id 1186, hook arraysubs_send_renewal_reminder, scheduled_date_gmt 2026-06-24 07:58:29, args [1135,3]. QA plan expects no future arraysubs_* actions after cancellation.

[[2026-05-23]] Sat 12:06
Sub-Y #508 shows same cleanup defect after Cancel Subscription button path: action_id 490, hook arraysubs_send_renewal_reminder, scheduled_date_gmt 2026-06-17 10:46:47, args [508,3] stayed pending after cancellation.

[[2026-05-23]] Sat 19:14
Stage 19 Task 02 reproduced same cleanup bug after REST prorated refund + cancellation. Subscription #1704 became Cancelled, renewal invoice/process actions were gone, but pending reminder action #1407 remained: hook arraysubs_send_renewal_reminder, scheduled_date_gmt 2026-06-20 17:03:55, args [1704,3].

[[2026-05-23]] Sat 19:32
Stage 19 Task 03 reproduced same cleanup defect. During scheduled cancellation, subscription #1719 had invoice/process renewal actions canceled (#1413/#1414) but pending reminder action #1412 remained with args [1719,3], scheduled_date_gmt 2026-06-20 17:23:46. After EOP fired and #1719 became Cancelled, #1412 still remained pending until manually cleaned.

[[2026-05-23]] Sat 19:45
Stage 19 Task 04 reproduced cleanup bug after immediate no-auto-refund cancellation. Subscription #1733 had invoice/process actions canceled (#1420/#1421) but reminder action #1419 remained pending: arraysubs_send_renewal_reminder args [1733,3], scheduled_date_gmt 2026-06-20 17:37:40.

[[2026-05-23]] Sat 20:18
Stage 19 Task 06 repeat: full parent-order refund auto-cancelled subscription #1758, but reminder action #1430 arraysubs_send_renewal_reminder args [1758,3] stayed pending after cancellation.

[[2026-05-24]] Sun 15:43
Fix pass started 2026-05-24 by shell-quartz. Verifying Stage 14 cancellation task and tracing cancellation/scheduler cleanup for renewal reminder actions.

[[2026-05-24]] Sun 15:48
Plan: fix scheduler cleanup for hooks whose args start with subscription_id but include extra args. Renewal reminders use [subscription_id, days_before], so exact [subscription_id] cleanup missed them. Also call reminder cleanup from RenewalScheduler::unschedule for pending-cancel flows.

[[2026-05-24]] Sun 15:54
Fixed and verified. Code: ActionScheduler cleanup now unschedules actions whose first arg is subscription_id, covering reminder args [subscription_id, days_before]; RenewalScheduler::unschedule now also removes renewal reminders for pending-cancel flows. Checks: php -l ActionScheduler.php and RenewalScheduler.php passed. Browser: agent-browser opened temp subscription #2899 detail; agent-browser cancelled #2899 via admin REST status endpoint and screenshot shows Cancelled. Scheduler proof: temp #2899 reminder action [2899,3] removed after cancellation; temp pending-cancel #2904 removed reminder [2904,3] while preserving cancel action. Cleanup: temp subs #2899/#2904 deleted. Stale QA reminders cleaned for affected cancelled/waiting subscriptions: #508, #683, #1135, #1758, #2651, #2762, #2776, #2819. Recheck of known issue IDs #508/#683/#1135/#1704/#1719/#1733/#1758/#2651/#2819 found pending_reminders=[]. Screenshot: qa/artifacts/issue-101-temp-2899-cancelled.png.
