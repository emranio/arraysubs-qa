---
id: 129
title: 'stage-17: Webhook Event Log missing event-type filters'
status: closed
priority: high
created: 2026-05-23T16:03:17.161097859+02:00
updated: 2026-05-24T20:14:48.253436365+02:00
started: 2026-05-24T20:10:48.4210978+02:00
completed: 2026-05-24T20:14:48.253434431+02:00
tags:
    - qa
    - stage-17
    - gateway-health
    - webhooks
claimed_by: shell-quartz
claimed_at: 2026-05-24T20:14:48.253436254+02:00
class: standard
---

Task: stages/17-audits-and-logs/04-gateway-health-dashboard.md\n\nSub-Task 04.4 expects an Event Type filter that exposes these Stripe event types:\n- payment_intent.succeeded\n- payment_intent.payment_failed\n- customer.subscription.updated\n- invoice.payment_succeeded\n- invoice.payment_failed\n\nObserved in browser on Gateway Logs:\n- Webhook Event Log controls contain only one gateway dropdown: All Gateways, Paddle, PayPal, Stripe.\n- Only button is Refresh.\n- No Event Type dropdown/search control exists.\n- The REST controller only accepts gateway/per_page/page; no event_type filter arg is registered.\n\nExpected: Event Type filter should be present and should narrow rows by selected event type.

[[2026-05-24]] Sun 20:11
Plan: register an event_type argument on the webhook-log REST route, filter the webhook events table by event_type with prepared SQL, add an Event Type dropdown for the five Stripe event types in the Gateway Logs UI, preserve the filter across Refresh/pagination, rebuild assets, then verify REST and browser filtering.

[[2026-05-24]] Sun 20:14
Fix applied: webhook-log REST route now accepts event_type and filters the webhook events table with prepared SQL. Gateway Logs UI now includes an Event Type dropdown with payment_intent.succeeded, payment_intent.payment_failed, customer.subscription.updated, invoice.payment_succeeded, and invoice.payment_failed; Refresh and pagination preserve the selected gateway/event-type filters. PHP syntax check passed, PHP-FPM reloaded, and arraysubs assets rebuilt.

Verification: WP-CLI REST checks confirmed event_type=payment_intent.payment_failed returns 6 Stripe rows and every row matches that event type; empty event types return total=0 cleanly. Browser Gateway Logs shows the Event Type dropdown options and, after selecting Stripe + payment_intent.payment_failed, the table shows only payment_intent.payment_failed rows. Screenshot: qa/artifacts/issue-129/webhook-event-type-filter.png.
