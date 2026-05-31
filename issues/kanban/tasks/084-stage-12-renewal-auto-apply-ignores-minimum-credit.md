---
id: 84
title: 'stage-12: Renewal auto-apply ignores minimum credit threshold'
status: closed
priority: high
created: 2026-05-23T10:14:23.533802118+02:00
updated: 2026-05-24T10:47:24.653943688+02:00
started: 2026-05-24T10:45:30.872589518+02:00
completed: 2026-05-24T10:47:24.653942706+02:00
tags:
    - qa
    - stage-12
    - store-credit
    - renewal
claimed_by: shell-quartz
claimed_at: 2026-05-24T10:47:24.653943588+02:00
class: standard
---

Original task: stages/12-store-credit/06-auto-apply-on-renewal.md\n\nPlan step: cycle 6 negative check with customer balance about 0.05 and Minimum Order Amount = 5.00. Auto-apply should be skipped and balance should remain 0.05.\n\nObserved for cust3 / subscription #1135 / renewal order #1182:\n- Pre-balance: 0.050000000000001.\n- Renewal invoice created for next payment date 2026-06-27 07:58:29.\n- Store credit auto-applied the remaining 0.05 despite balance being below the 5.00 minimum.\n- Order total became 19.94 with Store Credit Applied fee -0.05.\n- Customer balance became 0.\n- Browser admin order view confirmed Pending payment, 9.94, Standard Weekly, -/bin/bash.05, note Store credit applied: /bin/bash.05.\n\nExpected: no credit fee on cycle 6, renewal falls back to gateway/manual payment for full 19.99, and balance remains 0.05.\n\nSuspect code path: StoreCredit\Services\Hooks::onRenewalInvoiceCreated() calls CreditManager::applyCreditsToOrder() without checking store_credit.min_order_amount or available balance threshold.\n\nDebug log: no fresh PHP errors in checked tail; only old 2026-05-22 Action Scheduler WP-CLI fatal.

[[2026-05-24]] Sun 10:45
Plan: add the same renewal eligibility guard before CreditManager::applyCreditsToOrder(): auto-apply only when the renewal order total meets min_order_amount and available credit (subscription + customer credit) is at least that threshold. Then verify a cycle with /bin/bash.05 balance creates a full 9.99 pending/manual renewal and leaves balance unchanged.

[[2026-05-24]] Sun 10:47
Fixed/verified. StoreCredit renewal auto-apply now checks min_order_amount against both renewal order total and available credit before applying. Verification with subscription #2727/customer #310: set balance 0.05 and next payment past, ran renewal hooks. Renewal order #2751 stayed pending, total 19.99, no Store Credit Applied fee, _arraysubs_credit_applied empty, customer balance remained 0.05, pending renewal order remains #2751 as expected. Admin browser order #2751 showed Pending payment, 9.99, no Store Credit Applied fee, note 'Renewal order created. Awaiting manual payment.', related subscription #2727 active.
