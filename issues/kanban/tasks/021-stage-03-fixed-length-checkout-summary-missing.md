---
id: 21
title: 'stage-03: Fixed-length checkout summary missing next charge date'
status: closed
priority: medium
created: 2026-05-20T08:34:15.115243952+02:00
updated: 2026-05-22T04:47:37.548438749+02:00
started: 2026-05-22T04:45:49.460791548+02:00
completed: 2026-05-22T04:47:37.548437817+02:00
tags:
    - qa
    - stage-03
    - fixed-length
    - checkout
claimed_by: mold-glade
claimed_at: 2026-05-22T04:47:37.548438659+02:00
class: standard
---

Observed with agent-browser on 2026-05-20 for Fixed-Length Weekly (6 cycles) #229. Product page and cart showed '4.99 Every week' and '6 billing cycles'. Checkout summary showed Duration: 6 billing cycles and today's charge 4.99, but next charge date was not visible. Expected next charge date approximately one week away.

[[2026-05-22]] Fri 04:46
Plan: verify Stage 03 Task 06 product #229 metadata, run fresh agent-browser add-to-cart and checkout flow, confirm order summary contains Duration 6 billing cycles, Today charge USD 24.99, and Next charge about one week out. If missing, patch checkout/cart item metadata renderer.

[[2026-05-22]] Fri 04:47
Verification: Fresh agent-browser session added Fixed-Length Weekly (6 cycles) #229, opened checkout page #8, and order summary showed Duration 6 billing cycles, Today charge USD 24.99, and Next charge 29 May, 2026 (UTC+6). Current checkout metadata satisfies Stage 03 Task 06.4.
