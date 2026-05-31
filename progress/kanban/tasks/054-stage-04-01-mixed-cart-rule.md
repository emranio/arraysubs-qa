---
id: 54
title: 'stage-04: 01 Mixed Cart Rule'
status: closed
priority: high
created: 2026-05-19T22:56:06.007761763+02:00
updated: 2026-05-20T10:37:30.394514888+02:00
started: 2026-05-20T10:28:05.862424655+02:00
completed: 2026-05-20T10:37:30.394513836+02:00
tags:
    - qa
    - stage-04
claimed_by: mold-glade
claimed_at: 2026-05-20T10:37:30.394514788+02:00
class: standard
---

Source: stages/04-cart-rules/01-mixed-cart-rule.md

[[2026-05-20]] Wed 10:37
QA done. Allow mixed cart setting toggled via WP-CLI and restored enabled. Browser verified subscription-first and regular-first paths block the second item and preserve original cart item, but both paths fail exact expected error text. Logged issue #22. Re-enable path passed: Standard Weekly + Plain Mug accepted together, cart total 9.99.
