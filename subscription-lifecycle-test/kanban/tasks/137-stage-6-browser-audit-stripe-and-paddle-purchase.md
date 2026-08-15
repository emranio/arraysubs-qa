---
id: 137
title: 'stage-6: Browser audit Stripe and Paddle purchase records'
status: done
priority: critical
created: 2026-08-15T09:35:53.820705447+02:00
updated: 2026-08-15T10:01:47.756867101+02:00
started: 2026-08-15T10:01:47.756866129+02:00
completed: 2026-08-15T10:01:47.756866129+02:00
tags:
    - browser
    - lifecycle
    - orders
    - subscriptions
class: standard
---

Independently verify completed checkout fixtures in WooCommerce admin, ArraySubs subscription detail, customer portal, Gateway Health, Scheduled Actions, and Mailpit. Confirm exact line-item and subscription cardinality for Stripe and Paddle subscription-only, regular-only, mixed, SCA, and cancel scenarios. Do not modify source/settings or clean fixtures.

[[2026-08-15]] Sat 09:53
Interim audit (initial six fixtures): WP-CLI and browser agree. Paddle regular #27275 and Stripe regular #27315 are paid Processing orders with Standard Tee only and zero subscriptions. Paddle #27307/#27309 and Stripe #27280/#27296 are paid subscription-only purchases with exactly one Active subscription. Paddle #27339/#27341 and Stripe #27346/#27362 are paid mixed purchases with both line items and exactly one Active subscription. All four subscriptions show matching recurring totals/dates, connected gateway/card bindings, one invoice/process pair (plus Stripe reminder), correct customer portal and Related Subscriptions row. Mailpit has order/customer/admin subscription messages with rendered placeholders. Gateway Health/webhook rows present and no 4xx/5xx requests. Reproducible storefront page error on every customer portal/order load: AbortError: Transition was skipped; functional rendering is correct; investigating scope before filing. Evidence: qa/artifacts/payment-migration-regression-20260815/audit/. Adding SCA and cancellation fixtures next.

[[2026-08-15]] Sat 10:01
Final audit verdict:
- PASS: Paddle regular order #27275 and Stripe regular order #27315 are paid Processing orders with Standard Tee only, correct totals/gateways, and exactly zero subscriptions in WP-CLI, admin, customer orders, and empty subscription portals.
- PASS: Paddle subscription #27307/#27309 and Stripe subscription #27280/#27296 have one line item, exactly one Active subscription, correct amount/schedule/dates, completed payment count 1, intact gateway/card bindings, correct initial-order history, and one invoice/process action pair; Stripe also has one reminder.
- PASS: Paddle mixed #27339/#27341 and Stripe mixed #27346/#27362 contain subscription plus Standard Tee with totals 26.00 and 34.99 respectively, but exactly one subscription each. Customer order pages show both products and exactly one Related Subscriptions row.
- PASS with separate email finding: Stripe SCA #27387/#27403 completed from requires_action to charge.succeeded to payment_intent.succeeded (webhook rows #1206/#1208/#1210), is paid/Active with Visa ending 3184, exact pending actions #23942-23944, correct admin/customer/order views, and rendered Mailpit messages. The initial-checkout email is worded as renewal verification; root QA is filing that separately.
- PASS for canceled-payment invariants, FAIL for presentation: Paddle cancel #27381/#27383 remains Pending/unpaid with no completed transaction, no customer/payment-method/Paddle-subscription/card binding, no scheduler actions, no payment/subscription mail, and only transaction.created/updated rows #1204/#1205. Formal issue qa/issues #2 records the misleading Connected and Paddle (Paddle) authorization UI plus bacs-order/Paddle-subscription divergence.
- Gateway Health shows connected test-mode Paddle and Stripe with expected subscription counts and webhook history. Mailpit order/subscription/admin messages have expected items/totals/dates and no unresolved placeholders.
- Clean direct customer states for users #459-#466 produced zero JS page errors and zero 4xx/5xx requests. The earlier AbortError: Transition was skipped reproduced only while temporary Login as Customer switch-back cookies were present, including on the storefront home page, and disappeared when those helper cookies alone were removed; it is a QA impersonation artifact.
- Evidence is under qa/artifacts/payment-migration-regression-20260815/audit/. No source/settings were changed and purchase/subscription fixtures were not deleted. Loading user #462 portal caused WooCommerce itself to remove the retained cart item with its cannot-be-purchased notice; this was reported to root and was not manually re-added.

Audit complete; task closed with issue #2 open for engineering.
