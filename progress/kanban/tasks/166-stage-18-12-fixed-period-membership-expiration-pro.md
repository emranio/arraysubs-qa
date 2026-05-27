---
id: 166
title: 'stage-18: 12 Fixed-Period Membership Expiration (Pro)'
status: closed
priority: high
created: 2026-05-19T22:56:23.382424362+02:00
updated: 2026-05-24T22:24:38.224571135+02:00
started: 2026-05-23T08:06:53.478477449+02:00
completed: 2026-05-23T18:46:20.119664847+02:00
tags:
    - qa
    - stage-18
class: standard
---

Source: stages/18-renewal-followup/12-fixed-end-date-expiration-pro.md

[[2026-05-23]] Sat 18:37
Starting fixed-period expiration QA. Missing documented member-fixed fixture, so created fixture: user #48 member-fixed@example.com, product #1683 Fixed-Period Plan (fixed end enabled, absolute, renewal=expire), order #1685, subscription #1687 Active, end 2026-06-06 17:59:59 UTC, next 2026-05-30 16:36:56 UTC. Seeded future actions for removal check: expire #1397, expiring #1398, invoice #1399, process #1400, reminder #1401.

[[2026-05-23]] Sat 18:46
QA complete. Baseline PASS via browser: #1687 Active, Fixed-Period Plan, member-fixed@example.com, recurring 4.99/week, next 2026-05-30 16:36:56 UTC, fixed end 2026-06-06 17:59:59 UTC, product fixed-end enabled and renewal=expire/no auto-downgrade. Pending actions verified: expire #1397 and expiring-soon #1398 queued. Time-travel set _end_date to 2026-05-23 15:38:40 UTC; status stayed Active in DB. Ran expire action #1397. Result PASS: subscription became Expired, next payment blank, pending renewal order empty, no _cancellation_reason, Subscription Expired email note #1691, status audit #1690, Scheduled-Job success note #1692; browser verified admin Activity Audits and Scheduled-Job Logs. Customer portal PASS: member-fixed row shows Fixed-Period Plan / Expired / next dash / 4.99 Every week / View. Failures logged: #151 _end_date overwritten to job execution time, #152 renewal reminder action remained pending until cleanup, #153 no protected-content rule for access restriction check. Mail body/inbox proof still blocked by #137. Cleanup: canceled leftover reminder action #1401 after logging #152.

[[2026-05-24]] Sun 22:16
Issue #151 fixed and reverified. Fixed-period expiration for #1687 now preserves configured/time-traveled _end_date=2026-05-23 15:38:40 UTC while expiring the subscription. Screenshot: qa/artifacts/issue-151/subscription-1687-fixed-end-date-preserved.png.

[[2026-05-24]] Sun 22:20
Issue #152 fixed and reverified. Expiring #1687 now removes pending arraysubs actions even when args include extra reminder days: seeded 5 pending actions including [1687,3], after expiration pending count=0. Screenshot: qa/artifacts/issue-152/no-pending-actions-for-expired-1687.png.

[[2026-05-24]] Sun 22:24
Issue #153 fixed and reverified. Added protected page/rule for Fixed-Period Plan #1683 at /fixed-period-content/. Expired member-fixed user #48 sees restriction message and cannot see marker content. Screenshot: qa/artifacts/issue-153/fixed-period-content-expired-member-restricted.png.
