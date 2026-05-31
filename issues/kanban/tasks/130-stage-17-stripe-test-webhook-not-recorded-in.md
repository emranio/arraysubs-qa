---
id: 130
title: 'stage-17: Stripe test webhook not recorded in Gateway Logs'
status: closed
priority: high
created: 2026-05-23T16:03:30.331503083+02:00
updated: 2026-05-25T11:51:21.613494787+02:00
started: 2026-05-25T11:33:49.301209125+02:00
completed: 2026-05-25T11:51:15.514069997+02:00
tags:
    - qa
    - stage-17
    - gateway-health
    - stripe
    - webhooks
class: standard
---

Task: stages/17-audits-and-logs/04-gateway-health-dashboard.md\n\nSub-Task 04.5 expects a real Stripe test webhook event to appear at the top of Gateway Logs and update the Stripe Last Webhook timestamp.\n\nObserved:\n- Local Stripe CLI is missing: stripe command not found. Stripe API was used with the saved test secret key as fallback.\n- Created confirmed test PaymentIntent pi_3TaG61JG5OzSNVs20Uxp8vbB. Stripe returned event evt_3TaG61JG5OzSNVs20u3yPi7M with type payment_intent.succeeded.\n- Stripe account has enabled webhook endpoints for both official Woo Stripe URL and ArraySubs secondary URL.\n- After waiting and refreshing Gateway Logs, Webhook Event Log still shows only old event evt_1TVLuVEWlCDED5GU5kRGf52Q / setup_intent.succeeded from May 10.\n- Stripe card Last Webhook stayed May 10, 2026, 7:12 AM.\n- wp_arraysubs_webhook_events still contains only one row.\n\nExpected: New payment_intent.succeeded event should be recorded and Last Webhook should update.

[[2026-05-23]] Sat 16:45
Stage 18.03 reproduced same gateway-log gap during real Stripe automatic renewal. Subscription #1436 used Stripe PM 4242, renewal order #1440 was charged off-session successfully via PaymentIntent pi_3TaGiJJG5OzSNVs21iChByOw, order moved to processing, subscription stayed active and counter advanced. Browser Gateway Logs still showed only old setup_intent.succeeded evt_1TVLuVEWlCDED5GU5kRGf52Q from May 10, 2026; Stripe card Last Webhook stayed May 10, 2026 7:12 AM. Expected recent payment_intent.succeeded or charge.succeeded row for May 23, 2026.

[[2026-05-23]] Sat 17:04
Stage 18.04 partial webhook behavior: failed Stripe renewal #1471 did appear in Gateway Logs as payment_intent.payment_failed evt_3TaGs5JG5OzSNVs20iuBOLgi at May 23, 2026 8:49 PM, and Last Webhook updated. However recovery PaymentIntent pi_3TaH1mJG5OzSNVs21CD0D54S (order #1471 processing after successful retry) did not produce a visible payment_intent.succeeded/charge.succeeded row; visible rows remained two payment_intent.payment_failed events and the old setup_intent.succeeded. Task expected both failed and succeeded events.

[[2026-05-25]] Mon 11:36
Plan: (1) hook ArraySubsPro into Woo Stripe's deferred webhook action so deferred payment_intent.succeeded events are recorded even when Woo Stripe skips wc_stripe_webhook_received because the order is already complete; (2) normalize deferred array/object payloads and resolve the deferred order_id before dispatching the existing Stripe handler; (3) verify by simulating a deferred succeeded event against a real Stripe renewal/order and by browser-checking Gateway Logs/Last Webhook; no phpcs/lint per instructions.

[[2026-05-25]] Mon 11:44
Implemented first pass: ArraySubsPro now maps Stripe success/failure/refund event types for the secondary endpoint, listens to Woo Stripe deferred webhooks, and pre-records official payment_intent.succeeded events only when Woo Stripe will skip them (no order or already-paid order). Syntax check passed; now reloading PHP and verifying with signed official webhook + browser UI.

[[2026-05-25]] Mon 11:50
Fixed and verified. Root cause: Woo Stripe suppresses wc_stripe_webhook_received for payment_intent.succeeded when the event has no matching order or the order is already complete, and ArraySubs secondary Stripe mapping did not treat success events as loggable. Fix: StripeDelegate now logs skipped official payment_intent.succeeded events before Woo Stripe exits, handles wc_stripe_deferred_webhook for deferred success events, maps payment/charge/invoice/refund events for the secondary endpoint, and updates secondary webhook setup help text. Verification: real Stripe test PaymentIntent pi_3Tav1JJG5OzSNVs20quhZbpk succeeded and Gateway Logs recorded Stripe event evt_3Tav1JJG5OzSNVs20gyGJMXQ as charge.succeeded. Signed official payment_intent.succeeded webhook recorded evt_q130_1779702292 at the top of Gateway Logs; duplicate resend stayed at one row. Stripe Last Webhook updated to 2026-05-25 09:44:59 UTC / May 25, 2026 3:44 PM site time. Alumnium browser verified the Gateway Logs UI, and Playwright screenshot saved at qa/artifacts/issue-130/gateway-logs-payment-intent-succeeded.png. PHP syntax checks passed; phpcs/lint skipped per instruction.
