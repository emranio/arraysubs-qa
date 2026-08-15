---
id: 7
title: 'stage-5: Post-fix Stripe and Paddle core-only payment regression'
status: closed
priority: critical
created: 2026-08-15T14:19:37.064338284+02:00
updated: 2026-08-15T18:11:08.569780047+02:00
started: 2026-08-15T14:19:59.700520931+02:00
completed: 2026-08-15T18:11:08.569779335+02:00
tags:
    - stage-05
    - stripe
    - paddle
    - checkout
    - renewal
    - qa
class: standard
---

QA plan:
- `qa/stages/05-checkout/11-stripe-test-card-flow.md`
- `qa/stages/05-checkout/12-paypal-and-paddle-flows.md` (the current file is the Stripe payment-method lifecycle plan; Paddle coverage is the user's explicit migration-regression extension)
- `qa/stages/05-checkout/14-mixed-cart-and-block-checkout.md`
- Post-fix coverage for QA issues #4, #5, #6, and #7.

Tasks:
- Verify the repaired cancelled renewal is unpayable in the customer browser and direct order-pay route.
- With ArraySubsPro temporarily inactive, purchase a Stripe mixed subscription + normal-item cart and a Paddle mixed subscription + normal-item cart.
- Exercise Stripe SCA/3DS, exact-PI automatic renewal completion with `return_url`, genuine decline notification idempotency, and one distinct retry attempt.
- Verify order/subscription cardinality, provider bindings, webhooks, Mailpit, scheduler state, browser errors, and payment logs.
- Reactivate ArraySubsPro, cancel remote disposable billing safely, remove only disposable fixtures, and restore every temporary setting to its exact baseline hash.

Progress — core-only Stripe initial mixed checkout:
- ArraySubs active and ArraySubsPro intentionally inactive; both core Stripe and core Paddle adapters registered and available.
- Disposable customer #471 (`core-stripe-final-20260815@example.test`) completed a real mixed checkout containing Basic Monthly #197 and Standard Tee #447.
- Stripe SCA card ending 3184 displayed and completed the real 3DS challenge. Order #27752 is paid/processing for $44.99 with both lines and one Stripe charge; exactly one active subscription #27766 was created for the subscription line.
- Subscription #27766 stores the Stripe customer/payment-method binding, Visa 3184, one completed payment, and one pending invoice/process action pair. The normal item did not create a second subscription.
- Browser order-received and customer-subscription pages are clean. Mailpit contains the expected WooCommerce admin/customer order pair and ArraySubs admin/customer subscription pair, with no renewal-verification or payment-failure mail.
- No post-fix missing-`return_url` Stripe log entry was created. Renewal, decline/idempotency, cancellation, and core-only Paddle legs remain open until the concurrent hardening patches are frozen.


Progress — completed Stripe renewal and decline regression:
- The core-only Stripe subscription completed the normal automatic-renewal customer-action flow through its ordinary signed order-pay URL. A real 3DS challenge completed on the same exact bound PaymentIntent, with one paid/captured charge and one completed renewal payment.
- Reprocessing before customer action retained the same order/intent. Successful settlement cleared action/outbox state and left no retry actions.
- A fresh generic-decline attempt produced exactly one semantic failure note and exactly three expected messages across customer, ArraySubs admin, and WooCommerce admin. Same-attempt webhook/synchronous replay added zero notes and zero emails; a distinct attempt remained independently eligible.
- Customer cancellation of the now-overdue Stripe fixture exposed HTTP 500 update_failed. QA issue #8 records the fix: a valid elapsed canonical boundary now uses the existing immediate-cancellation path under lock. The same browser request returned HTTP 200, the unpaid renewal became cancelled/unpayable, and no retry/action/scheduler state remained.

Progress — completed core-only Paddle mixed checkout:
- Product #12112 at $11/day plus normal product #447 at $15 completed a real Paddle sandbox overlay checkout for $26.
- Order #27827 became paid/processing with both lines. Exactly one local subscription #27828 was created for the recurring line; the normal item did not create another subscription.
- The provider transaction completed with one recurring $11 item and one one-time $15 item. Its provider subscription contains only the recurring item and exactly one completed transaction.
- Local/provider status, amount, product binding, next-billing timestamp, payment count, webhook events, and deterministic invoice/process scheduler pair all matched.
- Mailpit captured the expected WooCommerce order pair and ArraySubs subscription pair. The subscription emails described only the recurring product.
- Browser order-received and customer-subscription views were clean. End-of-period Paddle cancellation scheduled correctly for the future boundary before final remote cancellation/cleanup.

Progress — combined-plugin and cleanup verification:
- ArraySubsPro was reactivated. Combined checkout displayed one saved Stripe method, one Credit/Debit Card gateway, and one Paddle gateway with no duplicate provider UI.
- Runtime inspection found one core gateway/provider instance and one owner for registration, Blocks, automatic-renewal, official Stripe webhook, deferred Stripe webhook, and Paddle paths; the Pro fallback was dormant.
- Gateway Logs showed Stripe and Paddle Connected (Test Mode), both Stripe endpoints configured, current webhook events, Paddle subscription count 2, Stripe subscription count 14, and no browser errors on a fresh reload.
- Only disposable QA billing was cancelled. The local fixtures then permanently removed after exact guards were subscriptions #27766/#27828, orders #27752/#27776/#27788/#27798/#27827, and customer #471. Provider test history remains, but no disposable remote subscription remains active.
- Existing controls were preserved: Stripe subscription #4406 remains cancelled with one completed payment; Paddle subscription #7809 remains active and matched its provider state after its natural scheduled renewal created completed order #27808.
- Both plugins are active: ArraySubs 1.8.12 and ArraySubsPro 1.1.3.
- All temporary settings were restored to their exact serialized baseline hashes, including ArraySubs, Woo Stripe, Stripe extras, and Paddle settings. Product fixtures #197/#12112/#447 remain.
- No new missing-return_url error exists; exactly two historical pre-fix entries remain. No new PHP debug entry was produced by the final live browser regressions.
- Final evidence: qa/artifacts/payment-bug-fixes-20260815/final-gateway-health-after-cleanup.png plus the core-only-final Stripe/Paddle browser screenshots.
- Task complete. No lint or PHPCS was run, as required by the issue-fix workflow.
