---
id: 37
title: 'stage-06: Trial status task expects wrong product and duration'
status: closed
priority: medium
created: 2026-05-20T13:58:18.901351026+02:00
updated: 2026-05-22T05:05:40.98153577+02:00
started: 2026-05-22T05:03:51.887186254+02:00
completed: 2026-05-22T05:05:40.981534547+02:00
tags:
    - qa
    - stage-06
    - trial
    - documentation
    - test-data
claimed_by: mold-glade
claimed_at: 2026-05-22T05:05:40.981535659+02:00
class: standard
---

Stage 06 Task 02 expects customer-trial@example.test subscription product 'Trial 14-Day' with next payment Stage 05 Task 03 date + 14 days. Actual Stage 05 Task 03 created subscription #384 for product 'Trial Weekly' (#202) with 7-day trial: trial_end/next_payment 2026-05-27 09:49:26 from 2026-05-20. Trial status behavior is correct, but task data does not match the canonical Stage 05 artifact.

[[2026-05-22]] Fri 05:04
Plan: update Stage 06 trial references from old Trial 14-Day / 14-day data to canonical Stage 05 Task 03 artifact Trial Weekly / 7-day trial; adjust email and order-page cross-check docs in same stage; verify current subscription #384 in browser/admin or WP-CLI shows Trial Weekly, Trial status, trial end and next payment both 2026-05-27.

[[2026-05-22]] Fri 05:05
Fix: Stage 06 docs now align with canonical Stage 05 Task 03 artifact: Trial Weekly, 7-day trial, T+7 next payment/trial end. Updated README, Task 02, and related order/email checks. Verification: agent-browser admin subscription #384 detail shows Trial status, Trial Weekly, Trial Length 7 day(s), Trial Ends and Next Payment 27 May, 2026.
