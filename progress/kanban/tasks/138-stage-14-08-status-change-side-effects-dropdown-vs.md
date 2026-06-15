---
id: 138
title: 'stage-14: 08 Status Change Side Effects (Dropdown vs. Cancel Button)'
status: closed
priority: medium
created: 2026-05-19T22:56:18.403052336+02:00
updated: 2026-05-24T16:31:25.313101214+02:00
started: 2026-05-23T08:06:53.450921117+02:00
completed: 2026-05-23T12:07:47.726200802+02:00
tags:
    - qa
    - stage-14
class: standard
---

Source: stages/14-admin-subscriptions/08-status-change-side-effects.md

[[2026-05-23]] Sat 12:07
QA 2026-05-23: Sub-X #1135 dropdown path: Active -> On Hold -> Cancelled worked in UI. Notes/email records appeared. Cancellation card visible; no reason shown; manual private audit note added. Failures logged: #102 missing _on_hold_date; #101 future renewal reminder action remained pending. Sub-Y #508 Cancel Subscription button path: immediate cancellation worked; cancellation card and notes visible; analytics page shows #508 in reason bucket 'Too expensive QA test' and #1135 as 'not_provided'. Failures logged: #104 reason/details not structured; #103 cancelled detail still says Vacation Mode subscription is active; #101 also applies to #508 future renewal reminder action.

[[2026-05-24]] Sun 15:54
Fix verification 2026-05-24 for issue #101: scheduler cleanup now removes renewal reminder actions with args [subscription_id, days_before] during immediate cancellation and pending-cancel unscheduling. Browser proof: temp #2899 cancelled via admin REST and showed Cancelled; pending reminders for #2899 were empty. Pending-cancel proof: temp #2904 removed reminder [2904,3] while preserving scheduled cancel action. Temp subs deleted. Stale QA reminders cleaned; known affected #508/#683/#1135/#1704/#1719/#1733/#1758/#2651/#2819 now have no pending renewal reminders. Screenshot: qa/artifacts/issue-101-temp-2899-cancelled.png. Checks: php -l ActionScheduler.php and RenewalScheduler.php passed.

[[2026-05-24]] Sun 16:06
Issue #102 fixed: On Hold via admin status dropdown now records _on_hold_date through core status-transition lifecycle hook. Browser verified with agent-browser on temp subscription #2909, DB meta confirmed, agent-browser screenshot saved at qa/artifacts/issue-102-temp-on-hold.png. Temp fixture removed.

[[2026-05-24]] Sun 16:31
Issue #104 fixed: Cancel Subscription modal now captures cancellation reason and additional details separately. Verified reason bucket Too expensive + details QA test via agent-browser and DB meta on temp #2939. Screenshots qa/artifacts/issue-104-modal-fields.png and qa/artifacts/issue-104-cancel-details.png.
