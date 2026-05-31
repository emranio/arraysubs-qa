---
id: 119
title: 'stage-12: 02 Manual Credit Adjustments'
status: closed
priority: medium
created: 2026-05-19T22:56:15.602647502+02:00
updated: 2026-05-24T09:52:14.337319564+02:00
started: 2026-05-23T08:06:53.429839696+02:00
completed: 2026-05-23T09:27:42.697280013+02:00
tags:
    - qa
    - stage-12
class: standard
---

Source: stages/12-store-credit/02-manual-adjustments.md

[[2026-05-23]] Sat 09:27
QA done with failures/workarounds. 2.1 single-character search no dropdown PASS; full cust3@test.local dropdown FAIL logged #74. REST route evidence: pretty REST redirects to trailing slash and fails browser UI; #/store-credit/7 cannot load customer. 2.2/2.3/2.6/2.8 admin UI blocked by #74, so add/deduct were seeded via StoreCredit service to preserve Stage 12 data: #1103 admin credit +50 note 'Goodwill — Stage 12 baseline', #1104 admin debit -10 note 'QA test deduction'. 2.4 customer portal PASS: cust3 Store Credit shows 0 after add, then 0 after deduct, with both history rows. 2.5 email arrival blocked by existing env issue #40 (no accessible mailbox/log); email registration enabled. 2.7 REST over-deduct FAIL logged #76: backend allowed -9999 and zeroed balance; probe row deleted and balance restored. Global Credit History regression FAIL logged #75: browser shows rest_no_route and Total 0 despite rows. Final cust3 balance: 0. Debug log no fresh related errors; only old 2026-05-22 Action Scheduler WP-CLI fatal lines.

[[2026-05-24]] Sun 09:39
Issue #74 fix verified: Manage Credits search now returns cust3@test.local dropdown result, and selecting it loads #/store-credit/7 with balance, Member Details, adjustment form, and Credit History table.

[[2026-05-24]] Sun 09:52
Issue #76 fix verified: over-deduct now fails through REST and browser UI. customer_id=7 amount=-9999 returns 400 with Cannot deduct more than available balance; cust3 balance remains 25 and no probe history row was created.
