---
id: 190
title: 'stage-21: 03 Manual Gateway — Segment 2 Prorate Checkout'
status: open
priority: high
created: 2026-07-08T02:50:18.560759+06:00
updated: 2026-07-08T02:51:11.823036+06:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
estimate: 30m
depends_on:
    - 188
class: standard
---

Source: stages/21-flexible-renewal-sync/03-manual-prorate-segment-checkout.md

Complete Direct-bank-transfer checkout with purchase day in segment 2. Required conditions: Today's charge = round(price*(L-D)/L, 2) to the cent (L = actual month length, D = day of month, site tz), order/line totals match, subscription Recurring Amount stays full $30, mode=prorate, next payment = boundary. Includes quantity x3 scaling spot-check.
