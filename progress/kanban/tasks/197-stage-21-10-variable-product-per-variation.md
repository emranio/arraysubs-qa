---
id: 197
title: 'stage-21: 10 Variable Product — Per-Variation Flexible Sync Checkout'
status: open
priority: high
created: 2026-07-08T02:50:39.732477+06:00
updated: 2026-07-08T02:50:39.732477+06:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
estimate: 35m
depends_on:
    - 188
class: standard
---

Source: stages/21-flexible-renewal-sync/10-variation-checkout.md

Per-variation independence through complete checkouts: flex-enabled Silver variation prorates and syncs to the boundary (manual + one Stripe spot-check) while sibling Gold without flex bills full $50 on its anniversary with no _renewal_sync_* meta. Variation-level trial hides/disables only that variation's flex config.
