---
id: 32
title: 'stage-05: Stripe card QA blocked by inline Payment Element'
status: closed
priority: high
created: 2026-05-20T13:08:19.332991948+02:00
updated: 2026-07-08T02:18:27.311808+06:00
started: 2026-05-22T02:04:39.259818145+02:00
completed: 2026-07-08T02:18:27.320251+06:00
tags:
    - qa
    - stage-05
    - stripe
    - checkout
    - automatic-payments
    - blocker
class: standard
---

Stage 05 Task 11 expects an ArraySubs Stripe hosted Checkout Session where 4242/SCA cards can be entered after redirect. Actual checkout shows Woo Stripe inline 'Payment methods Test Mode' with a secure Payment Element iframe and no hosted Stripe redirect. agent-browser can see the iframe but cannot type into it; direct WebDriver inspection found Stripe frames but no reachable input fields after selecting Stripe. Clicking Place Order produces 'Your payment information is incomplete.' Paid, trial SetupIntent, SCA, and cancel-session paths could not be completed in browser QA. Stripe config itself is enabled in Woo Stripe test mode with test keys and test webhook secret; PayPal/Paddle were skipped per instruction.

[[2026-05-22]] Fri 02:06
Verified Stage 05 Task 11, issue body, and Stripe code. Current implementation intentionally does not register an ArraySubs Stripe WC checkout gateway: AutomaticPayments\Services\Hooks registers PayPal/Paddle only, while StripeDelegate is a non-checkout delegate for renewals/webhooks and first-purchase UI is delegated to the official WooCommerce Stripe Gateway. DatabaseMigration also migrates old arraysubs_stripe payment meta to stripe and deletes woocommerce_arraysubs_stripe_settings. Woo Stripe settings show test mode + Payment Element/optimized checkout enabled, matching the inline iframe seen in QA. Plan options: (1) update Stage 05/12 QA/manual to test the official Woo Stripe inline/optimized checkout flow, or (2) reintroduce a new ArraySubs-hosted Stripe Checkout Session gateway and webhook/order-completion path. Option 2 is a substantial feature change touching gateway registration, process_payment, Checkout Session creation, webhooks, order/subscription context capture, setup intents, SCA/cancel flows, and migration semantics. Not safe to patch as a one-issue bug fix without product decision. Marking blocked.
