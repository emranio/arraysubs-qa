---
id: 56
title: 'stage-04: 03 One Subscription Per Product Rule'
status: closed
priority: high
created: 2026-05-19T22:56:06.226394238+02:00
updated: 2026-05-20T10:53:17.701459384+02:00
started: 2026-05-20T10:28:05.903051066+02:00
completed: 2026-05-20T10:53:17.701458452+02:00
tags:
    - qa
    - stage-04
claimed_by: mold-glade
claimed_at: 2026-05-20T10:53:17.701459284+02:00
class: standard
---

Source: stages/04-cart-rules/03-one-per-product-rule.md

[[2026-05-20]] Wed 10:53
QA done. One subscription per product enabled via WP-CLI. Direct add-to-cart quantity=2 produced empty cart with no visible validation on cart page. Cart-page quantity increase displayed validation, but text differed ('Reduce the quantity to 1 before continuing') and quantity stayed at 2 with total 9.98. Logged issue #24. Distinct product control partially passed: Standard Weekly + Trial Weekly increased header cart count from 1 to 2 before browser window closed. Setting restored disabled.
