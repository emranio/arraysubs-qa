---
id: 195
title: 'stage-21: 08 Partition Toggles, Quantity & Day-Boundary Edges'
status: open
priority: high
created: 2026-07-08T02:50:39.674496+06:00
updated: 2026-07-08T02:50:39.674496+06:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
estimate: 40m
depends_on:
    - 188
class: standard
---

Source: stages/21-flexible-renewal-sync/08-partition-toggles-quantity-and-edges.md

Checkout-level proof of the partition rule: two-segment plans resolve disabled days to the covering active segment (no anniversary fallback), single-segment plans apply to every day, boundary day D is inclusive (b1=D -> full, b1=D-1 -> prorate), weekly products sync to the store start-of-week with round(20*(7-d)/7,2) proration, and quantity 3 orders bill 3x the unit prorated amount.
