---
id: 43
title: 'stage-03: 06 Simple Fixed-Length Subscription (6 Cycles)'
status: closed
priority: high
created: 2026-05-19T22:56:02.710126553+02:00
updated: 2026-05-22T04:47:43.243827362+02:00
started: 2026-05-20T01:09:24.164079176+02:00
completed: 2026-05-20T08:35:13.881876207+02:00
tags:
    - qa
    - stage-03
claimed_by: mold-glade
claimed_at: 2026-05-22T04:47:43.243827102+02:00
class: standard
---

Source: stages/03-products/06-simple-fixed-length.md

[[2026-05-20]] Wed 08:35
Executed with Alumnium/WP-CLI on 2026-05-20. Created Fixed-Length Weekly (6 cycles) #229. Admin/meta persisted length 6. Product page/cart showed 6 billing cycles and 4.99 Every week. Checkout lacked next charge date; filed issue #21.

[[2026-05-22]] Fri 04:47
Issue #21 recheck: fresh Alumnium checkout for Fixed-Length Weekly #229 shows Duration 6 billing cycles, Today charge USD 24.99, and Next charge 29 May, 2026 (UTC+6).
