---
id: 194
title: 'stage-21: 07 Renewal Execution & Date Advancement (Manual + Stripe)'
status: open
priority: critical
created: 2026-07-08T02:50:39.645082+06:00
updated: 2026-07-08T02:50:39.645082+06:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
estimate: 60m
depends_on:
    - 189
    - 190
    - 191
    - 192
class: standard
---

Source: stages/21-flexible-renewal-sync/07-renewal-execution-and-advancement.md

Time-travel the subscriptions from tasks 02-05 and run their renewals: invoice order for the FULL recurring amount (never the prorated value), renewal-invoice email + pay link for manual, off-session PaymentIntent auto-charge for Stripe, next payment re-anchors to the boundary (first synced renewal re-anchors from _renewal_sync_first_full_renewal_date by design), segment-3 skipped boundary produces no invoice/email/charge, and a second advancement stays boundary-to-boundary.
