---
id: 155
title: 'stage-18: 01 Time-Travel Method'
status: closed
priority: high
created: 2026-05-19T22:56:21.723138939+02:00
updated: 2026-05-24T20:47:29.755673727+02:00
started: 2026-05-23T08:06:53.466523082+02:00
completed: 2026-05-23T16:19:31.004986121+02:00
tags:
    - qa
    - stage-18
class: standard
---

Source: stages/18-renewal-followup/01-time-travel-method.md

[[2026-05-23]] Sat 16:19
Completed Task 18.01 with chosen time-travel approach C (WP-CLI timestamp edit + Action Scheduler CLI runner + browser verification). Documented procedure: save original timestamp; update _next_payment_date or relevant date meta to desired UTC past timestamp; delete stale _pending_renewal_order_id if preparing a clean run; run the relevant Action Scheduler action with WP-CLI; verify in browser subscription detail, Scheduled-Job Logs, order state, notes/email; restore original timestamp and clean local state. Test subscription: alternate #357 (customer-block@example.test) because documented Stage 18 users are missing; issue #135 logged. Original next payment 2026-05-27 09:39:25 UTC; time-travel value 2026-05-23 13:15:18 UTC. Ran action #1250 arraysubs_generate_upcoming_renewals via CLI; action completed, Scheduled-Job Logs top row showed Generate Upcoming Renewals Success at May 23 2026 8:15 PM. Renewal order #1401 created pending, 9.99, linked to #357; browser showed order #1401 and Renewal Invoice Created timeline entries. Cleanup: cancelled order #1401, restored #357 active with next date 2026-05-27 09:39:25 and empty pending meta. Cancelling order #1401 temporarily moved #357 on-hold; restored active and logged issue #136. Queue healthy after cleanup: pending generate upcoming renewals #1254, check overdue #1251, trial conversions #1038.

[[2026-05-24]] Sun 13:33
Follow-up to issue #135: documented Stage 18 fixtures are now seeded/reset and Task 18.01 no longer needs alternate #357 for future runs. Canonical fixtures: member1 #1668 BACS Standard Weekly; member-stripe #1436 Stripe 4242; member-decline #1467 Stripe 0341; member-trial #1558 Trial Weekly; member-trial-down #1575 Trial Weekly w/ Downgrade; member-stepped #1587 Stepped Weekly; member-limited #1673 Limited 3-Cycle Weekly; member-fixed #1687 Fixed-Period Plan. All have future next_payment_date 2026-05-31 11:30:34 UTC and pending renewal scheduler actions; trials have trial conversion actions.

[[2026-05-24]] Sun 20:42
Issue #136 fixed and closed. Cancelling a pending renewal invoice now clears stale _pending_renewal_order_id without moving the subscription on-hold. Verified subscription #357 with renewal order #3015: status remained Active, pending meta cleared, next payment restored. Screenshot: qa/artifacts/issue-136/subscription-357-active-after-cancelled-renewal.png.

[[2026-05-24]] Sun 20:47
Issue #137 fixed and closed. Stage 18 now has an admin-only email content log via active Dev Assist: Tools > QA Mail Log. Smoke email captured recipient, subject, headers, and body; screenshot: qa/artifacts/issue-137/qa-mail-log-body.png.
