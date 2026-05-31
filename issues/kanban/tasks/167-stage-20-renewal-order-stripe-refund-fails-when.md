---
id: 167
title: 'stage-20: Renewal order Stripe refund fails when transaction ID is PaymentIntent'
status: closed
priority: medium
created: 2026-05-23T22:13:22.112136436+02:00
updated: 2026-05-25T00:28:38.954316478+02:00
started: 2026-05-25T00:07:56.88353597+02:00
completed: 2026-05-25T00:28:33.35161985+02:00
tags:
    - qa
    - stage-20
    - refunds
    - stripe
    - smoke
class: standard
---

Stage 20 Task 06 step F. Settings verified: refund_behavior=immediate, auto_gateway=true, allow_prorated=true, min=0. Smoke subscription #2591, renewal order #2598, Stripe payment method/intent present (payment=stripe, txn/pi=pi_3TaLe2JG5OzSNVs20ZV1BvdE, source=pm_1TaLbtJG5OzSNVs2rfb533o9). Attempting a full WooCommerce gateway refund with wc_create_refund([order_id=>2598, amount=>10.00, reason=>'Smoke test refund', refund_payment=>true]) returns: 'An error occurred while attempting to create the refund using the payment gateway API.' Order remains completed, refunded total 0, subscription remains active. Expected full gateway refund to succeed, Stripe to show refund, and subscription to auto-cancel per smoke checklist F3-F4.

[[2026-05-24]] Sun 08:14
User retest on 2026-05-24 confirms normal browser Stripe refund works: order #2633 refunded fully via refund #2662, parent status refunded, payment=stripe, txn=ch_3TaVCqJG5OzSNVs21g3QRTuX. Therefore this is not a global Stripe refund failure. Narrowed remaining observation: smoke renewal order #2598 failed via wc_create_refund; #2598 transaction_id and _stripe_intent_id are pi_3TaLe2JG5OzSNVs20ZV1BvdE, with no charge id stored in txn/_stripe_charge_id. Suspect renewal/off-session orders storing PaymentIntent ID instead of Charge ID breaks WooCommerce Stripe gateway refund path. Keep open only for renewal-order refund path; normal initial-order refund passes.

[[2026-05-24]] Sun 08:17
Required QA issue fields added. QA progress task/stage: #178, Stage 20 Task 06 Final Regression Smoke Pass, checklist step F Refund. QA plan markdown: qa/stages/20-edge-and-regression/06-final-regression-smoke.md. Affected failing subscription: #2591, product Smoke Plan Plus #2571, customer/user ID 308, login smoke.tester, email smoke@example.com, role customer, payment_method stripe. Affected failing order: renewal order #2598, status completed, total 10.00, refunded 0, payment stripe, transaction_id pi_3TaLe2JG5OzSNVs20ZV1BvdE, _stripe_intent_id pi_3TaLe2JG5OzSNVs20ZV1BvdE, _stripe_charge_id empty, _stripe_source_id pm_1TaLbtJG5OzSNVs2rfb533o9, subscription_id #2591, no refunds. Exact operation: wp eval wc_create_refund([order_id=>2598, amount=>10.00, reason=>'Smoke test refund', refund_payment=>true, restock_items=>false]). Actual: WP_Error message 'An error occurred while attempting to create the refund using the payment gateway API.' Expected: gateway refund succeeds, order records refund, Stripe shows refund, subscription auto-cancels per Stage 20.06 F. Working counterexample from user retest: subscription #2651/customer #1/admin, initial/browser Stripe order #2633 refunded successfully as refund #2662. #2633 status refunded, total 29.99, refunded 29.99, payment stripe, transaction_id ch_3TaVCqJG5OzSNVs21g3QRTuX, _stripe_intent_id pi_3TaVCqJG5OzSNVs21O3e6tqT, _stripe_source_id pm_1TaVCbJG5OzSNVs2Leac01kk. Scope note: normal initial-order Stripe refund works; remaining issue is renewal/off-session order #2598 where transaction ID stores PaymentIntent (pi_...) instead of charge (ch_...).

[[2026-05-25]] Mon 00:19
Patched Stripe renewal refund normalization to persist the resolved charge ID plus Woo Stripe captured-charge metadata; PHP syntax check passed. Retrying full refund against renewal order #2598 next.

[[2026-05-25]] Mon 00:28
Fixed and verified. Code changes: ArraySubsPro Stripe renewal payments now persist the resolved Charge ID as the Woo transaction ID and set Woo Stripe _stripe_charge_captured=yes; refund prep normalizes legacy PaymentIntent-only renewal orders before Woo Stripe processes refunds. Core order/subscription linking now uses WC_Order::get_meta() so HPOS renewal order meta resolves subscription #2591. Verification: wc_create_refund on renewal order #2598 succeeded as refund #3133; order status is refunded; Stripe charge ch_3TaLe2JG5OzSNVs20PFZn0IB reports refunded=true and amount_refunded=1000; arraysubs_get_subscription_ids_for_order(#2598) returns [2591]; subscription #2591 is arraysubs-cancelled with cancellation_reason=Full refund processed. Browser proof: Alumnium admin checks and Playwright screenshots qa/artifacts/issue-167/order-2598-refunded.png and qa/artifacts/issue-167/subscription-2591-cancelled-after-refund.png.
