---
id: 123
title: 'stage-12: 06 Auto-Apply on Renewal'
status: closed
priority: medium
created: 2026-05-19T22:56:16.158788259+02:00
updated: 2026-05-24T10:47:24.493821709+02:00
started: 2026-05-23T08:06:53.433552135+02:00
completed: 2026-05-23T10:18:09.387707123+02:00
tags:
    - qa
    - stage-12
class: standard
---

Source: stages/12-store-credit/06-auto-apply-on-renewal.md

[[2026-05-23]] Sat 10:03
Cycle 06 QA resumed. Balance reset to 100 via backend because Manage Credits search blocked by issue #74. Starting renewal cycle probe.

[[2026-05-23]] Sat 10:17
Stage 12 Task 06 complete. Reset cust3 balance 185.01 -> 100.00 by backend due Manage Credits search issue #74. Active Standard Weekly subscription #1135 verified. Cycle 1 via hooks: order #1152 got Store Credit Applied -19.99, balance 80.01, but order stayed Pending payment and blocked next cycle; logged issue #83. Continued cycles 2-5 using direct RenewalProcessor invoice + manual processing workaround after confirming same pending bug: orders #1158/#1164/#1170/#1176 all credit-covered to 0.00, manually advanced to processing, balances 60.02 / 40.03 / 20.04 / 0.05. Cycle 6 negative failed: order #1182 auto-applied leftover 0.05 despite minimum 5.00, balance became 0, order pending 19.94; logged issue #84. Browser verified admin subscription #1135 active with related renewal orders and customer portal Store Credit balance/history. Credit Used email verification blocked by issue #40.

[[2026-05-24]] Sun 10:45
Issue #83 fixed/verified: zero-total store-credit renewal now completes instead of remaining pending. Subscription #2727 renewal order #2745 processed at /bin/bash.00 with Store Credit Applied -9.99, customer balance 80.01, pending renewal cleared, next payment advanced to 2026-05-31 08:37:15 UTC, subscription active.

[[2026-05-24]] Sun 10:47
Issue #84 fixed/verified: renewal auto-apply skips when available credit is below min_order_amount. Subscription #2727 cycle with 0.05 credit produced order #2751 pending at 9.99, no Store Credit Applied fee, balance stayed 0.05.
