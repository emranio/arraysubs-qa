---
id: 55
title: 'stage-04: 02 Multiple Subscriptions In Cart'
status: closed
priority: high
created: 2026-05-19T22:56:06.119085984+02:00
updated: 2026-05-20T10:48:54.363865446+02:00
started: 2026-05-20T10:28:05.896115981+02:00
completed: 2026-05-20T10:48:54.363864374+02:00
tags:
    - qa
    - stage-04
claimed_by: mold-glade
claimed_at: 2026-05-20T10:48:54.363865325+02:00
class: standard
---

Source: stages/04-cart-rules/02-multiple-subscriptions-in-cart.md

[[2026-05-20]] Wed 10:48
QA done. Setting disabled and persisted via WP-CLI. Browser verified Standard Weekly -> Basic Plan and reverse order both block the second product and keep only the first product. Exact expected error fails because UI appends extra sentence; logged issue #23. Re-enable control passed: Standard Weekly + Basic Plan both accepted, total 9.98. Setting restored enabled.
