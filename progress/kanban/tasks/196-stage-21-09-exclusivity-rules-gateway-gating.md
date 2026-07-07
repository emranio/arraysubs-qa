---
id: 196
title: 'stage-21: 09 Exclusivity Rules & Gateway Gating'
status: open
priority: high
created: 2026-07-08T02:50:39.703283+06:00
updated: 2026-07-08T02:50:39.703283+06:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
estimate: 45m
depends_on:
    - 188
class: standard
---

Source: stages/21-flexible-renewal-sync/09-exclusivity-and-gateway-gating.md

Feature must be inert with Different Renewal Price / Trial / Lifetime (section hidden in admin AND anniversary behavior at checkout). Unsupported gateways (Paddle/PayPal) are hidden from checkout for flex carts and rejected on forced submit; bank transfer + Stripe remain. Cross-checks: per-product segments override the global sync setting's mode; non-flex products keep global behavior; mixed cart with a non-subscription product totals correctly.
