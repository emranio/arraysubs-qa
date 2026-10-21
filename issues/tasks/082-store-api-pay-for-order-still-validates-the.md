---
id: 82
title: Store API pay-for-order still validates the session cart against One Subscription Per Customer restrictions
status: closed
priority: low
created: 2026-10-21T01:34:12.234942963+02:00
updated: 2026-10-21T10:47:53.385447527+02:00
started: 2026-10-21T07:18:54.179228959+02:00
completed: 2026-10-21T10:47:53.385446786+02:00
tags:
    - checkout
    - store-api
    - pre-existing
class: standard
---

Active QA task ID and scheduled day: N/A (found 2026-10-20 by the #71-#78 fix verification; fix log qa/issues-65-69-fix-log.md).
Affected IDs: N/A (code reading; HEAD has the same call). Affected users: N/A.
Route: POST /wc/store/v1/checkout/{order_id}; arraysubs/src/Features/SubscriptionCheckout/Services/Traits/CartValidationTrait.php ~:254-275 runs on woocommerce_checkout_validate_order_before_payment with the shopper's current cart instead of the order being paid.
Expected: paying an existing order checks that order, not whatever is in the cart.
Actual: an unrelated cart can block (or pass) the pay-for-order request.
Proof: migration verifier LOW in scratchpad fix7x2/result-migration.json.

[[2026-10-21]] Wed 10:47
Closed 2026-10-21 (UTC) after fix + verification. Changes are in the working tree and NOT committed (user request). Full record: qa/issues-65-69-fix-log.md (timeline from 05:18 on 2026-10-21).
Fix (core SubscriptionCheckout CartValidationTrait/CheckoutMigrationTrait): paying an existing order (Store API /checkout/{id}, batch route, any route spelling, and the classic order-pay form) checks One Subscription Per Customer against that order, never the shopper's cart: renewal, plan-switch and regular orders are never refused; an unpaid subscription order is refused only when it would give the customer more than one live subscription (box/bundle counted once). A block checkout's unplaced checkout-draft holding a subscription cannot be paid through the Pay route ('This order has not been placed yet. Return to checkout to place it.'), so One Trial per Customer cannot be skipped. A guest's identity covers the order's billing email before the pay request changed it and the email of the order's own subscriptions, so changing the email (in one or two requests) does not get a second live subscription. Verified by fixer/verifier probes through the real Store API and classic routes and a final re-run of the two-step guest case (refused) and the own-order control (allowed).
