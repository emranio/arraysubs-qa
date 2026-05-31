---
id: 76
title: 'stage-12: Credit adjustment REST allows over-deduct'
status: closed
priority: high
created: 2026-05-23T09:27:04.321048262+02:00
updated: 2026-05-24T09:52:28.726359389+02:00
started: 2026-05-24T09:48:34.403322268+02:00
completed: 2026-05-24T09:52:28.726358378+02:00
tags:
    - qa
    - stage-12
    - store-credit
claimed_by: shell-quartz
claimed_at: 2026-05-24T09:52:28.726359289+02:00
class: standard
---

Stage 12 Task 02 Subtask 2.7. Frontend over-deduct guard could not be reached because Manage Credits customer loading is blocked by #74. Backend REST probe showed POST /arraysubs/v1/credits/adjust with customer_id=7 amount=-9999 when balance was 40 returned success, created log #1105, and set balance to 0. Expected Cannot deduct more than available balance and no new history row. Probe row was deleted and cust3 balance restored to 40.

[[2026-05-24]] Sun 09:52
Fix verified: REST credit adjustment now rejects over-deduct before CreditManager clamps the deduction. WP-CLI REST probe as admin returned 400 Cannot deduct more than available balance for customer_id=7 amount=-9999; balance stayed 25 and credit log count stayed 17. Browser #/store-credit/7 over-deduct attempt showed Cannot deduct more than available balance and no QA probe row was created.
