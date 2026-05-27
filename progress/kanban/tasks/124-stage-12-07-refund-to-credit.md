---
id: 124
title: 'stage-12: 07 Refund to Credit'
status: closed
priority: medium
created: 2026-05-19T22:56:16.278751786+02:00
updated: 2026-05-24T11:12:39.648233026+02:00
started: 2026-05-23T08:06:53.434492134+02:00
completed: 2026-05-23T10:27:48.660822895+02:00
tags:
    - qa
    - stage-12
class: standard
---

Source: stages/12-store-credit/07-refund-to-credit.md

[[2026-05-23]] Sat 10:18
Starting Stage 12 Task 07. Target order #1110 (Credit Pack 0, BACS, 5, cust3). Pre-balance after Task 06 is /bin/bash.00 because issue #84 consumed remaining /bin/bash.05. Manage Credits UI remains blocked by issue #74, so balance also verified through customer portal/CLI.

[[2026-05-23]] Sat 10:27
Stage 12 Task 07 complete. Browser order #1110: Processing, 5.00, cust3, Refund UI shows Payment Gateway/manual and Store Credit methods; Store Credit preview showed Current Balance /bin/bash.00 and Balance After 5.00 after entering amount. Browser refund submit blocked by native confirm(); logged issue #85. Applied backend-equivalent refund to continue: log #1186 source=refund amount=15 balance=15 note QA refund-to-credit test, order meta _refunded_as_credit=15, WC total remains 45.00, WC gateway refunded=0, order note/history row visible. Reopened refund UI: Store Credit max refundable = 0.00. Backend AJAX over-refund 50.00 rejected with Amount exceeds maximum refundable (0.00), no balance change. Customer portal shows balance 5.00 and top Refund row. Browser global Credit History still shows 0 transactions; appended issue #75. Credit Added email verification blocked; appended issue #40. Guest order #440 refund UI shows store credit not available for guest orders.

[[2026-05-24]] Sun 11:12
Issue #85 fixed/verified. Store Credit refund UI no longer uses native dialogs and now uses custom confirmation modal/admin notice/loading state. Playwright screenshots: qa/artifacts/issue-85/03-confirm-modal.png and 05-after-refund-refresh.png. Order #2605 refunded .00 as store credit: _refunded_as_credit=1, customer #308 balance=1, credit log #2756, order note visible.
