---
id: 75
title: 'Paid checkout-migration (plan_switch_checkout) order never switches the subscription: arraysubs_order_paid is suppressed for it'
status: closed
priority: high
created: 2026-10-20T16:24:13.51688497+02:00
updated: 2026-10-21T02:09:42.973784358+02:00
started: 2026-10-20T19:27:41.560909409+02:00
completed: 2026-10-21T02:09:42.973782424+02:00
tags:
    - checkout
    - plan-switching
    - migration
    - needs-repro
class: standard
---

Active QA task ID and scheduled day: N/A (found 2026-10-20 by review round 4 of the #65–#70 fixes; pre-existing, out of their scope). Fix log: qa/issues-65-69-fix-log.md (R4-12).
Affected subscription IDs and order IDs: N/A (code reading; checkout migration — one subscription per customer + auto-migrate at checkout — is off on staging, so no order exists).
Affected WordPress user/customer: N/A.
Exact route: checkout with "migrate the existing subscription at checkout" on; order meta `_arraysubs_order_type = plan_switch_checkout` (CheckoutMigrationTrait.php:168). Finalizer `CheckoutMigrationTrait::maybeProcessCheckoutMigrationOrder()` is hooked only to `arraysubs_order_paid` (SubscriptionCheckout/Services/Hooks.php:93). `OrderIntegration::onOrderStatusChanged()` (arraysubs/src/Features/Subscriptions/Services/OrderIntegration.php:917-934) fires `arraysubs_order_paid` only when the order type is NOT `plan_switch` / `plan_switch_checkout` (added in b759027). The PlanSwitching finalizer `PlanSwitching\Services\Hooks::onProrationOrderPaid()` (arraysubs/src/Features/PlanSwitching/Services/Hooks.php:1297, hooked at :86-88 on `woocommerce_payment_complete`, `woocommerce_order_status_completed` and `woocommerce_order_status_processing`) handles `plan_switch` only (checks at :1309 and :1317). The only other `arraysubs_order_paid` emitter is the Paddle path (PaddlePaymentProcessingTrait.php:1938).
Reproduction (to run; not run live): enable one subscription per customer + auto-migrate at checkout + Plan Switching upgrades (settings snapshot first), as a customer with an active subscription add an allowed upgrade target, pay with Stripe test card, then check the order notes and the subscription's product.
Expected: the paid migration order switches the existing subscription (PlanManager::updateSubscriptionProduct), sets `_arraysubs_switch_processed = yes`, links the order.
Actual (by code reading): nothing calls the finalizer for Stripe, Mollie, PayPal or manual gateways: the customer pays the plan-change amount and the subscription stays on the old plan.
Proof: code references above; `grep -rn maybeProcessCheckoutMigrationOrder arraysubs/src` finds only the hook registration and the method.
Scope notes: verify live before fixing. Fix direction: give `plan_switch_checkout` its own paid trigger (e.g. call the finalizer from onOrderStatusChanged for that type) rather than re-entering the generic initial-payment fan-out. Related: #76 (the same orders can skip payment).

[[2026-10-21]] Wed 02:09
Closed 2026-10-21 (UTC) after fix + verification. Changes are in the working tree and NOT committed (user request). Full record: qa/issues-65-69-fix-log.md (section 'Issues #71–#78' and timeline from 17:26).
Fix: plan_switch_checkout orders are finalized on payment (online payment_complete, or Processing/Completed for manual methods) exactly once; their refunds never cancel the subscription; only the shopper's own account-owned subscription can be switched; PayPal/Paddle are refused for them (classic and Store API pay-for-order); a Stripe/Mollie switch payment does not replace the renewal method; later switch-screen pricing is not skewed. Live 2026-10-20 (settings window opened and restored identical): fixture customer 1520 (Basic Monthly ×2, sub 87088) switched to Pro Monthly ×2 at checkout ('Replaces your current Basic Monthly subscription', credit $59.97, $39.99 today, no Paddle/PayPal); order 87640 completed with one 'Existing subscription updated from checkout migration.' note; sub 87088 on Pro Monthly with the same next date; a full refund of 87640 left the subscription active. Product decision left open: #79 (signup fee on a checkout switch).
