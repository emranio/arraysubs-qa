---
id: 57
title: 'stage-04: 04 Different Billing Cycles Rule'
status: closed
priority: high
created: 2026-05-19T22:56:06.373543811+02:00
updated: 2026-05-20T11:00:39.978919924+02:00
started: 2026-05-20T10:28:05.909543474+02:00
completed: 2026-05-20T11:00:39.978918902+02:00
tags:
    - qa
    - stage-04
claimed_by: mold-glade
claimed_at: 2026-05-20T11:00:39.978919844+02:00
class: standard
---

Source: stages/04-cart-rules/04-different-billing-cycles.md

[[2026-05-20]] Wed 11:00
QA done. Allow multiple subscriptions enabled; Allow different billing cycles disabled via WP-CLI. Browser verified Standard Weekly -> Basic Monthly and reverse order both block the second product and preserve original cart item. Exact expected error fails because UI appends extra guidance; logged issue #25. Re-enable control passed: Standard Weekly + Basic Monthly both accepted, total 9.98. Setting restored enabled.
