---
id: 49
title: 'stage-07: Cancellation reason required by task is not configured'
status: closed
priority: low
created: 2026-05-20T14:46:16.231701777+02:00
updated: 2026-05-22T05:47:55.113379031+02:00
started: 2026-05-22T05:45:16.531409133+02:00
completed: 2026-05-22T05:47:55.113378029+02:00
tags:
    - qa
    - stage-07
    - customer-portal
    - cancellation
claimed_by: mold-glade
claimed_at: 2026-05-22T05:47:55.113378941+02:00
class: standard
---

Stage 07 Task 04 test data says select cancellation reason "Just need a temporary break". The live cancellation reason dropdown only contains: Too expensive, Not using it enough, Found a better alternative, Missing features I need, Technical issues, Other. Expected: either the QA fixture/settings include the documented reason or the task data matches the configured cancellation reasons.

[[2026-05-22]] Fri 05:47
Plan: compare Stage 07 Task 04 required reason against current cancellation settings and customer modal. If missing, seed/repair settings with temporary_pause. If present, no code change; close as resolved by existing normalization/settings fix.

[[2026-05-22]] Fri 05:47
Verified: arraysubs_settings.cancellation.reasons and arraysubs_get_cancellation_reasons() include temporary_pause / Just need a temporary break. Alumnium cust1 #643 cancel modal dropdown shows: Too expensive, Not using it enough, Found a better alternative, Missing features I need, Technical issues, Just need a temporary break, Shipping or delivery problems, Other. No code change needed.
