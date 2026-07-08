---
id: 173
title: 'stage-21: Stripe successful payments log duplicate webhook event DB errors'
status: closed
priority: high
created: 2026-07-08T00:32:02.15803478+02:00
updated: 2026-07-08T08:01:59.475591984+02:00
started: 2026-07-08T07:27:47.187289688+02:00
completed: 2026-07-08T08:01:59.475591223+02:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
    - stripe
    - webhooks
class: standard
---

QA progress task: #192, Stage 21, task 05.
QA plan path: qa/stages/21-flexible-renewal-sync/05-stripe-full-and-prorate-checkout.md

Affected subscription/order IDs: Run A order #8737, subscription #8751, PaymentIntent pi_3TqhNkJG5OzSNVs208VQk5R0, charge ch_3TqhNkJG5OzSNVs2068OLiAJ; Run B order #8762, subscription #8776, PaymentIntent pi_3TqhU1JG5OzSNVs20Ufkcxne, charge ch_3TqhU1JG5OzSNVs20f0Pm4MX.
Affected WordPress user/customer IDs: Run A customer ID 334, login sync.stripefull, email sync-stripe-full-20260708-0412@example.test, role customer. Run B customer ID 335, login sync.stripeprorate, email sync-stripe-prorate-20260708-0428@example.test, role customer.

Exact test URL/admin route: customer checkout https://mirror-help.arrayhash.com/checkout/?add-to-cart=8648 in agent-browser sessions qa-customer-stripe-full and qa-customer-stripe-prorate. WP/Stripe proof gathered via WP-CLI with --allow-root and Stripe test API.

Reproduction steps:
1. Configure FRS Monthly 30 (#8648) flexible sync Segment 1 boundaries 10/20, place a Stripe card checkout using 4242 test card.
2. Configure Segment 2 boundaries 5/20, place a Stripe card checkout using 4242 test card.
3. Check wp-content/debug.log after each successful Stripe payment/webhook processing.

Expected result: Stripe webhooks/events are idempotent and do not write database duplicate-key errors to debug.log. Stage regression expects debug.log unchanged.

Actual result: debug.log grew from 1696 to 1698 lines. Two WordPress database errors were written:
- 2026-07-07 22:22:28 UTC duplicate entry arraysubs_stripe-evt_3TqhNkJG5OzSNVs20eyXcR3X for charge.succeeded during Run A.
- 2026-07-07 22:28:56 UTC duplicate entry arraysubs_stripe-evt_3TqhU1JG5OzSNVs20uGQUw7z for charge.succeeded during Run B.
Both came from ArraySubsPro AutomaticPayments StripeDelegate->rememberEvent, through different webhook routes: Run A via ArraySubsPro WebhookController/REST route, Run B via Woo Stripe official wc-api handler.

Concrete proof:
- tail -n 12 ../debug.log shows the two duplicate-key INSERT errors on wp_arraysubs_webhook_events.gateway_event.
- Run A order #8737 completed, PaymentIntent amount 3000 usd succeeded.
- Run B order #8762 completed, PaymentIntent amount 2226 usd succeeded.

Known scope notes/counterexamples: Functional payment/subscription behavior passed for both Stripe checkouts: orders completed, subscriptions active, saved Stripe customer/payment method IDs present, PaymentIntent amounts matched order totals. The issue is specifically duplicate webhook event logging/noisy database errors during successful Stripe payment processing.

[[2026-07-08]] Wed 00:48
Additional occurrence during progress task #193 / Stage 21 task 06 Run A: Stripe next-cycle order #8791, subscription #8805, PaymentIntent pi_3TqhcDJG5OzSNVs20HZ1t83R, charge ch_3TqhcDJG5OzSNVs20EMR76qH. debug.log grew to 1699 lines with duplicate entry arraysubs_stripe-evt_3TqhcDJG5OzSNVs20F3OD9Zj for charge.succeeded at 2026-07-07 22:37:23 UTC via ArraySubsPro WebhookController/StripeDelegate->rememberEvent. Functional checkout still succeeded: subscription #8805 active, next payment 2026-08-31 18:00:00 UTC, gateway customer cus_UqOPM0b5g9LW7Q, payment method pm_1Tqhc3JG5OzSNVs23Pmrt4WA.

[[2026-07-08]] Wed 00:59
Additional occurrence during progress task #194 / Stage 21 task 07: Stripe off-session renewal order #8842 for subscription #8751, PaymentIntent pi_3Tqhx9JG5OzSNVs20n8KiXub, charge ch_3Tqhx9JG5OzSNVs20bBn84YT. debug.log grew from 1699 to 1702 during the run. Product runtime duplicate webhook DB errors: duplicate entry arraysubs_stripe-evt_3Tqhx9JG5OzSNVs20KbGi2Ti for payment_intent.succeeded at 2026-07-07 22:59:02 UTC, and duplicate entry arraysubs_stripe-evt_3Tqhx9JG5OzSNVs20KUuf4Bi for charge.succeeded at 2026-07-07 22:59:03 UTC, both via ArraySubsPro WebhookController/StripeDelegate->rememberEvent. A third new debug.log line was caused by my WP-CLI inspection command reading Woo internal _transaction_id meta and is not counted as product runtime evidence.

[[2026-07-08]] Wed 01:52
Stage 21 task #197 additional occurrence: Silver variation Stripe checkout order #8978, subscription #8992, PaymentIntent pi_3TqillJG5OzSNVs20qMPf88L, latest charge ch_3TqillJG5OzSNVs20D4h4DUX. Stripe API amount=2226 usd, status=succeeded. debug.log added line 1719: duplicate wp_arraysubs_webhook_events.gateway_event entry for arraysubs_stripe event evt_3TqillJG5OzSNVs20j7XRJmU charge.succeeded during webhook processing. Plan path qa/stages/21-flexible-renewal-sync/10-variation-checkout.md.

[[2026-07-08]] Fix applied: rememberEvent() used plain $wpdb->insert which raced against the unique key gateway_event (gateway_slug,event_id) when Stripe delivered the same event to both the ArraySubsPro REST webhook route and the official Woo Stripe wc-api route — both passed isDuplicateEvent() before either row landed, second INSERT logged a duplicate-key DB error. Changed to INSERT IGNORE (prepared) in arraysubspro/src/Features/AutomaticPayments/Gateways/Stripe/StripeDelegate.php::rememberEvent and arraysubspro/src/Features/AutomaticPayments/Abstracts/AbstractArraySubsGateway.php::rememberEvent. Verified with a live duplicate-insert simulation via WP-CLI: first insert affected=1, second insert affected=0, wpdb last_error empty both times. Full Stripe checkout re-verification pending with issue #175 retest.

[[2026-07-08]] Live verification complete: Stripe off-session renewal for subscription #8751 (order #9068, PaymentIntent pi_3Tqo85JG5OzSNVs20QpApygX) processed both charge.succeeded (evt_3Tqo85JG5OzSNVs200ujWBf5) and payment_intent.succeeded (evt_3Tqo85JG5OzSNVs20232jjyX) webhooks at 2026-07-08 05:34 UTC through the dual routes with zero new debug.log lines (stayed 1719). Both events recorded exactly once in wp_arraysubs_webhook_events.
