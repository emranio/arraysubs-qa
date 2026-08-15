---
id: 2
title: 'stage-4: Mixed-cart eligibility and block parity'
status: closed
priority: critical
created: 2026-08-15T09:45:26.798907525+02:00
updated: 2026-08-15T10:01:11.257892607+02:00
tags:
    - stage-04
    - cart
    - checkout
    - qa
class: standard
---

References: ../stages/04-cart-rules/01-mixed-cart-rule.md, ../stages/04-cart-rules/07-block-checkout-parity.md

Verify subscription-only, regular-only, and mixed carts are purchasable in the live WooCommerce Blocks checkout without changing unrelated access rules. Record the narrow reversible QA window and exact restoration proof.

[[2026-08-15]] Sat 10:01
PASS. Woo Blocks carts/checkout exercised with subscription-only, normal-only, and mixed product combinations on both Stripe and Paddle. Mixed carts retained the correct initial/recurring totals and created exactly one subscription for the recurring line. The four-product member-access exclusion and renewal-sync override were narrow, browser-managed, and restored exactly to their original fingerprints.
