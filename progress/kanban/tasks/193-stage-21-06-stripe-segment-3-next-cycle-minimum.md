---
id: 193
title: 'stage-21: 06 Stripe — Segment 3 Next-Cycle & Minimum-Charge Edge'
status: open
priority: high
created: 2026-07-08T02:50:39.596203+06:00
updated: 2026-07-08T02:50:39.596203+06:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
estimate: 35m
depends_on:
    - 188
    - 192
class: standard
---

Source: stages/21-flexible-renewal-sync/06-stripe-next-cycle-and-minimum-charge.md

Segment 3 on Stripe (full $30 today, next payment = boundary+1 cycle, nothing scheduled at the skipped boundary) plus the gateway minimum safeguard: prorated charge below ~$0.50 bumps to the minimum when full price >= minimum (verify PaymentIntent accepts), manual gateway shows the raw un-bumped value, and no bump when even the full price is below the minimum.
