---
id: 33
title: 'stage-05: Stripe gateway health inconsistent with webhook config'
status: closed
priority: high
created: 2026-05-20T13:12:05.283590889+02:00
updated: 2026-05-22T02:14:49.988337934+02:00
started: 2026-05-22T02:06:51.772217799+02:00
completed: 2026-05-22T02:14:49.988337163+02:00
tags:
    - qa
    - stage-05
    - stripe
    - webhooks
    - gateway-health
claimed_by: mold-glade
claimed_at: 2026-05-22T02:14:49.988337854+02:00
class: standard
---

Stage 05 Task 12 Stripe-only verification found gateway health/config inconsistencies. Dashboard shows Stripe Connected (Test Mode), Subscriptions 2, but Last Webhook = Never while the Webhook Event Log lists arraysubs_stripe event evt_1TVLuVEWlCDED5GU5kRGf52Q setup_intent.succeeded processed May 10, 2026 7:12 AM. The expected task URL is /wp-json/arraysubs/v1/stripe/webhook; code/dashboard secondary endpoint is /wp-json/arraysubs/v1/webhooks/arraysubs_stripe (site renders index.php?rest_route=...), and Woo Stripe test webhook config points to https://mirror-help.arrayhash.com/?wc-api=wc_stripe. arraysubs_stripe_extras option is missing, so the ArraySubs secondary webhook secret is not saved. Full event lifecycle could not be verified because Task 11 Stripe card checkout is blocked (#32).

[[2026-05-22]] Fri 02:09
Plan: fix Stripe health by treating official Stripe and ArraySubs secondary webhook rows as one Stripe health source; detect Woo Stripe test webhook secret when test mode is on; expose official/secondary webhook config in dashboard UI; verify REST + browser dashboard after patch.

[[2026-05-22]] Fri 02:14
Fixed: REST now treats Stripe and arraysubs_stripe webhook rows as one visible Stripe health source, uses Woo Stripe test_webhook_secret in test mode, and exposes official/secondary webhook config. Built admin assets. Verified via REST and Alumnium: Stripe card Last Webhook = May 10, 2026 7:12 AM; official webhook configured; secondary shown not configured; Stripe log filter still shows arraysubs_stripe setup_intent.succeeded row.
