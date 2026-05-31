---
id: 42
title: 'stage-03: 05 Simple Lifetime Deal Subscription'
status: closed
priority: high
created: 2026-05-19T22:56:02.50325455+02:00
updated: 2026-05-20T08:35:13.88088188+02:00
started: 2026-05-20T01:09:24.163305774+02:00
completed: 2026-05-20T08:35:13.880880838+02:00
tags:
    - qa
    - stage-03
claimed_by: mold-glade
claimed_at: 2026-05-20T08:35:13.88088178+02:00
class: standard
---

Source: stages/03-products/05-simple-lifetime-deal.md

[[2026-05-20]] Wed 08:35
Executed with Alumnium/WP-CLI on 2026-05-20. Created Lifetime Deal #227. Product page showed lifetime price, but non-zero length leaked into product/cart/checkout and checkout did not show no-recurring wording; filed issue #17. Restored length to 0 for downstream.
