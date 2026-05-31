---
id: 16
title: 'stage-03: Stepped renewal checkout summary missing next charge date'
status: closed
priority: medium
created: 2026-05-20T01:32:17.381626436+02:00
updated: 2026-05-22T04:45:36.916272064+02:00
started: 2026-05-22T04:30:34.608098583+02:00
completed: 2026-05-22T04:45:36.916271052+02:00
tags:
    - qa
    - stage-03
    - checkout
    - stepped-pricing
claimed_by: mold-glade
claimed_at: 2026-05-22T04:45:36.916271964+02:00
class: standard
---

Observed with Alumnium on 2026-05-20 for Stepped Weekly #209. Product/admin tier fields persisted; product page and cart showed both initial 9.99 and later 9.99 after 3 cycles/renewals. Checkout summary showed 'After 3 renewals: 9.99 Every week' and today's charge 9.99, but no next charge date. Expected Stage 03 task 04.5: next charge date approximately one week away.

[[2026-05-22]] Fri 04:33
Plan: reproduce Stepped Weekly block cart and checkout. Extend Cart Block proceed fallback from trial-only carts to subscription carts. Verify Stepped Weekly can reach checkout and order summary shows renewal tier, today charge, and next charge date. If checkout still lacks next charge, patch block checkout metadata/rendering.

[[2026-05-22]] Fri 04:45
Verification: Fresh Alumnium session added Stepped Weekly #209, opened checkout page #8, and order summary showed renewal tier (USD 19.99 weekly for first 3 payments then USD 29.99 weekly), Today charge USD 19.99, and Next charge 29 May, 2026 (UTC+6). No code change needed for this issue; current checkout item metadata satisfies Stage 03 Task 04.5.
