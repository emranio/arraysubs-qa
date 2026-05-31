---
id: 156
title: 'stage-18: 02 Successful Manual Renewal'
status: closed
priority: high
created: 2026-05-19T22:56:21.82133779+02:00
updated: 2026-05-23T16:31:24.889986568+02:00
started: 2026-05-23T08:06:53.467244743+02:00
completed: 2026-05-23T16:31:24.889985576+02:00
tags:
    - qa
    - stage-18
claimed_by: mold-glade
claimed_at: 2026-05-23T16:31:24.889986458+02:00
class: standard
---

Source: stages/18-renewal-followup/02-successful-manual-renewal.md

[[2026-05-23]] Sat 16:31
QA pass with documented deviations. Used alternate active BACS Standard Weekly subscription #357 (customer-block@example.test) because documented member1 fixture is missing/misconfigured (#135). Original next payment 2026-05-27 09:39:25 UTC, completed_payments 1. Time-traveled _next_payment_date to 2026-05-23 13:21:55 UTC and ran Action Scheduler #1254 arraysubs_generate_upcoming_renewals. Browser verified renewal order #1412 pending, total 19.99, subscription stayed Active, invoice email audit row [ArraySubs] Renewal Invoice present. Used Login as Customer and pay URL; customer-block paid #1412 via Direct bank transfer, order received page showed order #1412 total 19.99 BACS. Marked BACS order completed via WP-CLI to simulate bank transfer received. Final verification: order #1412 completed, subscription #357 Active, pending renewal empty, completed_payments 2, next payment 2026-05-30 13:21:55 UTC, browser timeline shows Renewal Payment Successful and [ArraySubs] Renewal Payment Successful email audit. Scheduled-Job Logs show May 23 2026 8:23 PM Generate Upcoming Renewals Success. Mail body/inbox verification remains blocked by #137.
