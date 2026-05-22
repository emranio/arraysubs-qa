---
id: 65
title: 'stage-05: 05 Different Renewal Price Summary'
status: closed
priority: high
created: 2026-05-19T22:56:07.202536185+02:00
updated: 2026-05-20T12:04:17.328897749+02:00
started: 2026-05-20T10:28:05.940481106+02:00
completed: 2026-05-20T12:04:17.274478028+02:00
tags:
    - qa
    - stage-05
claimed_by: mold-glade
claimed_at: 2026-05-20T12:04:17.328897438+02:00
class: standard
---

Source: stages/05-checkout/05-different-renewal-price-summary.md

[[2026-05-20]] Wed 12:04
QA done. Product #209 Stepped Weekly config verified via WP-CLI: regular/current price 19.99, _enable_renewal_price yes, _renewal_price 29.99, _renewal_price_after 3, period week interval 1. Created/reset customer-stepped@example.test user #12. Cart showed Stepped Weekly 9.99, first payment 9.99, renewals 'After 3 renewals: 9.99 Every week', total 9.99. Checkout showed same wording; expected manual wording '9.99 every 1 week for the first 3 payments, then 9.99 every 1 week' was not present. Logged issue #27. Order placed via block checkout/direct bank transfer: order #413 total 9.99, related subscription #427 Pending then marked Completed via WP-CLI. Subscription #427 became Active, product #209, recurring amount 19.99, next 2026-05-27 10:02:54, different renewal meta stored as _different_renewal_price=29.99 and _different_renewal_price_after=3, billing period week interval 1.
