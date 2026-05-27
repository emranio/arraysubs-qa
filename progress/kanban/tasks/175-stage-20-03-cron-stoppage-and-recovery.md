---
id: 175
title: 'stage-20: 03 Cron Stoppage and Recovery'
status: closed
priority: high
created: 2026-05-19T22:56:24.598563937+02:00
updated: 2026-05-24T23:45:43.790840737+02:00
started: 2026-05-23T08:06:53.490644454+02:00
completed: 2026-05-23T21:13:05.928094198+02:00
tags:
    - qa
    - stage-20
class: standard
---

Source: stages/20-edge-and-regression/03-cron-stoppage-and-recovery.md

[[2026-05-23]] Sat 21:13
Result: QA complete with defect #164. Baseline: DISABLE_WP_CRON already true; OS cron pinger existed in /etc/cron.d/mirror-help-arrayhash-wordpress. Temporarily commented pinger and reloaded cron; restored it before closing. Created fixtures after pinger off: Sub-A #1791 Active Basic Monthly next=2026-05-23 18:39:17 UTC, overdue invoice/process actions #1479/#1480; Sub-B #1796 Trial Weekly trial_end/next=2026-05-23 18:59:19 UTC, overdue batch trial conversion #1481. Expected stuck state failed: loading #1791 admin detail triggered Action Scheduler async runner while OS cron was disabled. #1479/#1480 completed, renewal invoice/order #1799 pending was created; #1481 completed and #1796 converted Trial -> Active. Logged #164. Recovery outcomes verified: Sub-A has exactly one pending renewal order #1799; notes show Renewal Invoice Created; running Check Overdue Renewals left #1791 Active. Sub-B #1796 browser verified Active, next=24 June 2026 1:09 AM UTC+6, note 'Free trial ended. Subscription converted to paid.' _trial_length=0. No new arraysubs failed actions beyond pre-existing #1039/#163. Email body proof blocked by #137.

[[2026-05-24]] Sun 23:45
Follow-up issue #164 fixed cron-stoppage semantics. Core now disables Action Scheduler's async web/admin runner when DISABLE_WP_CRON=true. Verification: with OS cron temporarily commented/reloaded, due probe #2035 remained pending after loading ArraySubs admin; probe was deleted, cron file restored, and cron reloaded. WP-Cron/server cron remains the explicit catch-up mechanism.
