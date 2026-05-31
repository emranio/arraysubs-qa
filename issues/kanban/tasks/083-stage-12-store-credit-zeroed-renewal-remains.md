---
id: 83
title: 'stage-12: Store-credit-zeroed renewal remains pending'
status: closed
priority: high
created: 2026-05-23T10:06:00.649784514+02:00
updated: 2026-05-24T10:45:12.970036006+02:00
started: 2026-05-24T10:38:35.326507945+02:00
completed: 2026-05-24T10:45:12.970034693+02:00
tags:
    - qa
    - stage-12
    - store-credit
    - renewal
claimed_by: shell-quartz
claimed_at: 2026-05-24T10:45:12.970035896+02:00
class: standard
---

Original task: stages/12-store-credit/06-auto-apply-on-renewal.md\n\nPlan step: Auto-Apply to Renewals should fully cover each Standard Weekly renewal and leave renewal order Processing/Completed, subscription Active, next payment date advanced, and credit history row recorded.\n\nObserved during cycle 1 for cust3 / subscription #1135:\n- Pre-balance: 100.00.\n- Renewal hook generated order #1152.\n- Credit applied: _arraysubs_credit_applied=19.99, Store Credit Applied fee -19.99.\n- Order total: 0.00.\n- Credit balance became 80.01 and credit log #1155 source=renewal_applied was created.\n- Browser admin order view still shows status Pending payment.\n- Order note says Renewal order created. Awaiting manual payment.\n- Subscription #1135 keeps _pending_renewal_order_id=1152 and next payment date remains in the past; renewal cycle cannot continue normally.\n\nExpected: zero-total credit-covered renewal should be marked paid/processing/completed, pending renewal cleared, and subscription next payment date advanced.\n\nEvidence: Alumnium admin order #1152 extracted: Pending payment, /bin/bash.00, Standard Weekly, Store Credit Applied, #1135 arraysubs-active, notes include Store credit applied: 9.99 and Awaiting manual payment.\n\nSuspect code path: store-credit hook applies credit after renewal invoice creation, but recurring payment processor/manual gateway path still leaves zero-total renewal in manual_required/pending instead of completing zero-total order.\n\nDebug log: no fresh PHP errors in checked tail; only old 2026-05-22 Action Scheduler WP-CLI fatal.

[[2026-05-24]] Sun 10:41
Plan: add a zero-total renewal branch in core PaymentProcessor before manual fallback/gateway processing. When Store Credit reduces the renewal order total to 0, call payment_complete() with an internal transaction id and return paid so OrderIntegration processes renewal payment, clears _pending_renewal_order_id, advances next payment, and keeps subscription active. Then verify with a real browser-created subscription and WP-CLI-triggered renewal cycle.

[[2026-05-24]] Sun 10:45
Fixed/verified. Core PaymentProcessor now marks zero-total renewal orders paid before manual/gateway fallback. Renewal proof using subscription #2727/customer #310: set balance 100 and next payment past; ran generate/process renewal hooks. New renewal order #2745 status processing, total 0.00, Store Credit Applied -19.99, _arraysubs_credit_applied=19.99, customer balance 80.01, _pending_renewal_order_id cleared, next payment advanced to 2026-05-31 08:37:15 UTC, subscription arraysubs-active. Admin browser order view showed Processing, /bin/bash.00, Store Credit Applied, notes 'Store credit applied: 9.99' and 'Renewal order total is zero. Marking paid without gateway charge.' Subscription detail showed ACTIVE, next payment 31 May 2026, order #2745 processing /bin/bash.00 Renewal.
