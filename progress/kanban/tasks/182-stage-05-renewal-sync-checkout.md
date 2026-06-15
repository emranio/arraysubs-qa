---
id: 182
title: 'stage-05: Renewal Sync checkout'
status: closed
priority: high
created: 2026-06-03T12:19:03.941725419+02:00
updated: 2026-06-03T15:36:06.792216965+02:00
started: 2026-06-03T12:44:44.638335605+02:00
completed: 2026-06-03T13:29:58.947358742+02:00
tags:
    - qa
    - stage-05
    - renewal-sync
    - checkout
    - stripe
class: standard
---

Browser QA for prorated/full first charge, manual gateway, Stripe support, and unsupported gateway filtering. Plan: qa/stages/05-checkout/15-renewal-sync-checkout.md.



agent-browser Stripe block checkout pass (2026-06-03): selected Stripe Payment methods Test Mode, expanded Stripe iframe Card option, filled test card 4242, and placed order #4251 for $26.99. Created active subscription #4406 for customer #319 with payment_method=stripe, _recurring_amount=29.99, _next_payment_date=2026-06-30 18:00:00 (1 July 2026 UTC+6), _renewal_sync_enabled=yes, _renewal_sync_first_charge_mode=prorate, _renewal_sync_initial_recurring_amount=26.99. Screenshots: /tmp/arraysubs-stripe-frame-clicked.png and /tmp/arraysubs-stripe-block-result.png.
