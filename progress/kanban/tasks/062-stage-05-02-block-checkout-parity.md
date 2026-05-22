---
id: 62
title: 'stage-05: 02 Block Checkout Parity'
status: closed
priority: high
created: 2026-05-19T22:56:06.844166906+02:00
updated: 2026-05-20T11:40:39.046304091+02:00
started: 2026-05-20T10:28:05.930741713+02:00
completed: 2026-05-20T11:40:39.046302899+02:00
tags:
    - qa
    - stage-05
claimed_by: mold-glade
claimed_at: 2026-05-20T11:40:39.046303981+02:00
class: standard
---

Source: stages/05-checkout/02-block-checkout-parity.md

[[2026-05-20]] Wed 11:40
QA done. Active checkout page #8 and cart page #7 confirmed as Woo blocks. Created/reset customer-block@example.test user #9. With allow_multiple_in_cart disabled, Block Cart showed Store API-style error but text differed from spec: 'Only one subscription plan can be checked out at a time. Remove the other subscription from your cart before adding this one.' This is covered by issue #23. Re-enabled setting and continued with Standard Weekly only. Block checkout summary showed product and 9.99 Every week / total 9.99, but Next charge date was missing and authorization notice not shown, consistent with existing summary defect #14. Direct bank transfer order placed successfully: order #340, related subscription #357 Pending, next payment 27 May 2026 (UTC+6), 9.99 / Every week. Marked order completed via WP-CLI; subscription #357 became Active, product #200, amount 19.99, period week, interval 1, next 2026-05-27 09:39:25. No duplicate active subscription found for customer-block.
