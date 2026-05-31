---
id: 79
title: 'stage-12: Store credit not shown/applied in cart totals'
status: closed
priority: high
created: 2026-05-23T09:42:49.459031549+02:00
updated: 2026-05-24T10:10:57.332275711+02:00
started: 2026-05-24T10:02:48.481485379+02:00
completed: 2026-05-24T10:10:57.332274729+02:00
tags:
    - qa
    - stage-12
    - store-credit
claimed_by: shell-quartz
claimed_at: 2026-05-24T10:10:57.332275601+02:00
class: standard
---

Stage 12 Task 05 Subtask 5.1. cust3 balance was $205, Allow at Checkout=true, min_order_amount=5. Standard Weekly cart showed $19.99 Renewals: $19.99 Every week, no Store Credit/Credit Applied line, estimated total remained $19.99. Expected store credit application indication and $0.00 total before checkout.

[[2026-05-24]] Sun 10:10
Fix verified: added checkout cart fee preview for store credit. Browser verified with isolated QA customer qa-credit-cart (user #309, 5 credit, no subscriptions): Standard Weekly cart shows product 9.99, Subtotal 9.99, Store Credit Applied -9.99, Estimated total /bin/bash.00, and cart finished loading. Checkout capture code also added to deduct the copied fee once when order is processed.
