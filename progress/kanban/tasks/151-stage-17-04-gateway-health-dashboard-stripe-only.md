---
id: 151
title: 'stage-17: 04 Gateway Health Dashboard (Stripe only)'
status: closed
priority: medium
created: 2026-05-19T22:56:20.947077499+02:00
updated: 2026-05-25T11:51:05.259502336+02:00
started: 2026-05-23T08:06:53.463651427+02:00
completed: 2026-05-23T16:04:23.247101314+02:00
tags:
    - qa
    - stage-17
class: standard
---

Source: stages/17-audits-and-logs/04-gateway-health-dashboard.md

[[2026-05-23]] Sat 16:04
Completed Gateway Health Dashboard QA (Stripe only). Browser verified cards: Paddle Disabled / 0 / Never, PayPal Disabled / 0 / Never, Stripe Test / Connected (Test Mode) / Subscriptions 2 / Last Webhook May 10, 2026 7:12 AM. DB cross-check of stripe active/on-hold/trial/pending subscriptions also returned 2. Stripe card expanded: official webhook URL https://mirror-help.arrayhash.com/?wc-api=wc_stripe, official webhook Configured, secondary webhook URL shown, WooCommerce Settings link opened WooCommerce Stripe settings. Webhook Event Log columns present; gateway filter to Stripe works; one existing row visible: arraysubs_stripe evt_1TVLuVEWlCDED5GU5kRGf52Q setup_intent.succeeded May 10, 2026 7:12 AM; footer 1 total events. Real Stripe API trigger created PaymentIntent pi_3TaG61JG5OzSNVs20Uxp8vbB and event evt_3TaG61JG5OzSNVs20u3yPi7M, but Gateway Logs did not update. Duplicate resend could not be meaningfully verified because the new event was not recorded. Logged issues #128, #129, #130, #131.

[[2026-05-24]] Sun 20:10
Issue #128 fixed and closed. Stripe expanded gateway card now renders capability pills from the existing REST boolean capability map. Browser screenshot captured after rebuild.

[[2026-05-24]] Sun 20:14
Issue #129 fixed and closed. Gateway Logs now supports Event Type filtering in REST and UI for the five stage-17 Stripe events. Browser screenshot captured after rebuild.

[[2026-05-24]] Sun 20:35
Issue #131 fixed and closed. Subscriptions now has a Gateway filter with Stripe/PayPal/Paddle, REST supports gateway filtering by _payment_gateway, and status tab counts update under the gateway filter. Verified Stripe: All 13, Active 5, On Hold 1, Pending 0, Trial 0; active/on-hold/pending/trial REST count 6 matches Gateway Health Stripe count basis. Screenshot: qa/artifacts/issue-131/subscriptions-gateway-filter.png.

[[2026-05-25]] Mon 11:51
Issue #130 fixed and retested. Gateway Logs now records successful Stripe webhook activity: real test PaymentIntent pi_3Tav1JJG5OzSNVs20quhZbpk produced logged charge.succeeded event evt_3Tav1JJG5OzSNVs20gyGJMXQ, and a signed official payment_intent.succeeded webhook produced top-row event evt_q130_1779702292. Stripe Last Webhook updated to May 25, 2026 3:44 PM site time; duplicate resend preserved one row. Screenshot: ../artifacts/issue-130/gateway-logs-payment-intent-succeeded.png.
