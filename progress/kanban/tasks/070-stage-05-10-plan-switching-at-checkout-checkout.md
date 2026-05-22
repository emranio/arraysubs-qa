---
id: 70
title: 'stage-05: 10 Plan Switching at Checkout (Checkout Migration)'
status: closed
priority: high
created: 2026-05-19T22:56:07.896646389+02:00
updated: 2026-05-22T04:57:38.399216676+02:00
started: 2026-05-20T10:28:05.945551618+02:00
completed: 2026-05-20T12:54:45.413786351+02:00
tags:
    - qa
    - stage-05
claimed_by: mold-glade
claimed_at: 2026-05-22T04:57:38.399216306+02:00
class: standard
---

Source: stages/05-checkout/10-plan-switching-at-checkout.md

[[2026-05-20]] Wed 12:54
QA complete. Setup Pro Monthly #495 (9.99/month), linked Basic Monthly #197 upgrade target and Pro Monthly downgrade source. Setup customer-switch@example.test with original active Basic subscription #508 and parent order #505. Enabled one_per_customer + auto_migrate during test. Browser cart/checkout: Pro Monthly added as migration, due today 0.00, recurring 9.99 every month, Direct bank transfer. Checkout copy: 'Replaces the customer\'s current Upgrade plan from Basic Monthly' (wording mismatch filed qa/issues #31). Placed migration order #511 at 0.00. After payment complete: order meta _arraysubs_order_type=plan_switch_checkout, _arraysubs_switch_processed=yes, existing subscription #508 updated in place to product #495; no duplicate customer-switch live subscription. Admin detail: product Pro Monthly, recurring 9.99, billing schedule Every 1 month(s), order history #511 and #505, next payment 20 June 2026 4:46 PM (UTC+6), notes show upgraded from Basic Monthly to Pro Monthly. Woo order note contains exact 'Existing subscription updated from checkout migration.' Settings one_per_customer and auto_migrate restored false after task.

[[2026-05-22]] Fri 04:57
Issue #31 fixed: checkout migration copy now says Replaces your current Basic Monthly subscription; completed BACS migration order #1013 and admin subscription #508 notes panel shows Existing subscription updated from checkout migration.
