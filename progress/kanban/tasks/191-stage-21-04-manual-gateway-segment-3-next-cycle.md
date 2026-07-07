---
id: 191
title: 'stage-21: 04 Manual Gateway — Segment 3 Next-Cycle Checkout'
status: open
priority: high
created: 2026-07-08T02:50:18.587487+06:00
updated: 2026-07-08T02:51:11.843129+06:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
estimate: 30m
depends_on:
    - 188
class: standard
---

Source: stages/21-flexible-renewal-sync/04-manual-next-cycle-checkout.md

Complete Direct-bank-transfer checkout with purchase day in segment 3. Required conditions: full $30 today, 'First billing cycle' note (payment covers cycle starting B1), next payment = B2 = boundary+1 cycle, NO invoice/renewal jobs at B1, mode=next_cycle, customer gets bonus access days until B1.
