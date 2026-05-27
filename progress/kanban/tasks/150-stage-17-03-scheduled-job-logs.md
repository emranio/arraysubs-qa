---
id: 150
title: 'stage-17: 03 Scheduled-Job Logs'
status: closed
priority: medium
created: 2026-05-19T22:56:20.782047169+02:00
updated: 2026-05-24T20:04:55.356576129+02:00
started: 2026-05-23T08:06:53.462870865+02:00
completed: 2026-05-23T15:47:45.311035541+02:00
tags:
    - qa
    - stage-17
class: standard
---

Source: stages/17-audits-and-logs/03-scheduled-job-logs.md

[[2026-05-23]] Sat 15:41
Verified Scheduled-Job Logs UI row for forced renewal failure: row appears as Success for Process Renewal #959 even though subscription went On Hold and retry was scheduled. Issue #126 logged. Now cleaning subscription test state.

[[2026-05-23]] Sat 15:47
Completed Scheduled-Job Logs QA. Verified columns, human-readable labels, subscription #959 link, date filtering, refresh persistence, generate-upcoming-renewals success row, pending renewal order #1382, Activity Audit rows for #1382/#959, pagination next/previous, and cleanup. Logged issues #126 (failed process-renewal logged as Success) and #127 (orphan zero-value order on failed renewal). Cleanup: #959 restored active, product #197, next payment 2026-06-22 00:42:12; QA orders #1382/#1389 cancelled; retry action #1244 cancelled; normal future actions #1245/#1246 scheduled.

[[2026-05-24]] Sun 19:58
Issue #126 fixed and closed. Process Renewal jobs that complete at Action Scheduler level but record a renewal business failure now appear as Failed in Scheduled-Job Logs with the subscription failure reason. Verified with action #1928 / note #2987 for #959; fixture restored.

[[2026-05-24]] Sun 20:04
Issue #127 fixed and closed. Renewal order creation now validates product before creating Woo order and cleans partial orders on exceptions. Verified missing-product renewal for #959 created no order above pre-test max #2951; fixture restored.
