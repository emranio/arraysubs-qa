---
id: 152
title: stage-18 expired subscription keeps renewal reminder action
status: closed
priority: high
created: 2026-05-23T18:41:57.849174954+02:00
updated: 2026-05-24T22:20:23.132476282+02:00
started: 2026-05-24T22:16:57.841360548+02:00
completed: 2026-05-24T22:20:23.13247533+02:00
tags:
    - qa
    - stage-18
    - fixed-period
    - scheduler
    - email
claimed_by: shell-quartz
claimed_at: 2026-05-24T22:20:23.132476181+02:00
class: standard
---

Stage 18 Task 12. Fixture subscription #1687 had future actions before expiration: expire #1397, expiring-soon #1398 [1687], renewal invoice #1399 [1687], process renewal #1400 [1687], renewal reminder #1401 [1687,3]. After running expiration #1397, actions #1398/#1399/#1400 were canceled, but #1401 remains pending (arraysubs_send_renewal_reminder, args [1687,3], scheduled 2026-05-27 16:36:58 UTC). Expected: no pending arraysubs hooks should reference an expired subscription. Likely cause: unschedule-all uses args [subscription_id] but renewal reminder actions are scheduled as [subscription_id, days_before].

[[2026-05-24]] Sun 22:18
Plan: verify the current centralized ActionScheduler::unscheduleAllForSubscriptionArg() path removes actions whose first arg is the subscription ID, including renewal reminder args [subscription_id, days_before]. Seed #1687 with pending expire, expiring-soon, invoice, process-renewal, and renewal-reminder actions, run arraysubs_expire_subscription, then confirm no pending action args contain 1687. If any remain, patch the unscheduler; if none remain, close as fixed by current scheduler changes.

[[2026-05-24]] Sun 22:20
Fixed/verified live. Current scheduler code uses ActionScheduler::unscheduleAllForSubscriptionArg(), which inspects pending actions by first argument and removes hooks with extra args such as arraysubs_send_renewal_reminder [subscription_id, days_before]. Verification on #1687: seeded pending actions #2015 expire [1687], #2016 expiring soon [1687], #2017 invoice [1687], #2018 process renewal [1687], and #2019 renewal reminder [1687,3]. Ran arraysubs_expire_subscription. Result: #1687 expired with _end_date preserved and pending actions containing 1687 went from 5 to 0. Browser Scheduled Actions pending search for 1687 shows 'No items found'. Screenshot: qa/artifacts/issue-152/no-pending-actions-for-expired-1687.png.
