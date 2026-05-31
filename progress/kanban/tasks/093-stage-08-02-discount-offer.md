---
id: 93
title: 'stage-08: 02 Discount Offer'
status: closed
priority: high
created: 2026-05-19T22:56:11.366594184+02:00
updated: 2026-05-20T15:13:13.09841158+02:00
started: 2026-05-20T13:41:52.917847798+02:00
completed: 2026-05-20T15:13:13.098410578+02:00
tags:
    - qa
    - stage-08
claimed_by: mold-glade
claimed_at: 2026-05-20T15:13:13.09841149+02:00
class: standard
---

Source: stages/08-retention/02-discount-offer.md

[[2026-05-20]] Wed 15:13
QA notes: Configured Retention Offers ON and Discount Offer ON for too_expensive at 20% / 3 cycles. Customer flow on active Basic Monthly #683: Cancel Subscription > Too expensive > Continue opens Before You Go modal with exact heading and intro plus Keep Subscription / No thanks buttons. Offer card area remains Loading... after wait and page shows error: Failed to cancel subscription. Please try again. Discount card never renders, so Accept, discounted recurring amount, My Subscriptions row, admin notes, and Retention Analytics entries are blocked. Issue #61.
