---
id: 72
title: 'stage-05: 12 Stripe Webhook Verification & Payment-Method Lifecycle'
status: closed
priority: high
created: 2026-05-19T22:56:08.161223354+02:00
updated: 2026-05-20T13:12:16.118068953+02:00
started: 2026-05-20T10:28:06.011523736+02:00
completed: 2026-05-20T13:12:16.118067991+02:00
tags:
    - qa
    - stage-05
claimed_by: mold-glade
claimed_at: 2026-05-20T13:12:16.118068843+02:00
class: standard
---

Source: stages/05-checkout/12-paypal-and-paddle-flows.md

[[2026-05-20]] Wed 13:12
QA attempted/blocked. PayPal/Paddle skipped per instruction. Browser admin opened ArraySubs Audits/Gateway Logs at #/settings/gateways. Stripe card shows TEST, Connected (Test Mode), Subscriptions 2, Last Webhook Never. Webhook log shows one arraysubs_stripe event evt_1TVLuVEWlCDED5GU5kRGf52Q setup_intent.succeeded processed May 10 2026 7:12 AM, but Last Webhook still Never. WP-CLI: wp_arraysubs_webhook_events table exists with only columns id, gateway_slug, event_id, event_type, processed_at and one historical setup_intent.succeeded row. arraysubs_stripe_extras option missing, so secondary ArraySubs webhook secret not saved; Woo Stripe settings have test keys and test_webhook_secret, official Woo webhook URL is ?wc-api=wc_stripe. Full lifecycle subtasks (payment_intent.succeeded, payment_failed renewal, expiring card email, token rotation, detach card) blocked because Stage 05 Task 11 Stripe card checkout could not submit secure Payment Element; filed qa/issues #32 and #33.
