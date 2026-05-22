---
id: 61
title: 'stage-05: 01 Classic Checkout — Standard Weekly Subscription'
status: closed
priority: high
created: 2026-05-19T22:56:06.744618256+02:00
updated: 2026-05-20T11:35:06.299057633+02:00
started: 2026-05-20T10:28:05.929632771+02:00
completed: 2026-05-20T11:35:06.299056221+02:00
tags:
    - qa
    - stage-05
claimed_by: mold-glade
claimed_at: 2026-05-20T11:35:06.299057543+02:00
class: standard
---

Source: stages/05-checkout/01-classic-checkout-basic-subscription.md

[[2026-05-20]] Wed 11:34
QA failed/blocked. Created temporary Classic Checkout QA shortcode page #338 and pointed Woo checkout to it for this task, then restored checkout page option to original #8 after testing. customer-classic@example.test user #8 created/reset to QA-classic-2026!. Browser verified Standard Weekly add to cart and cart line: renewals 9.99 every week, total 9.99. Classic checkout order review showed product, recurring 9.99 every week, and total/today 9.99, but Next charge date and authorization notice were missing. Filled all required billing fields and selected Direct bank transfer; Place order button stayed enabled with no visible validation errors but click/Enter did nothing: no redirect, no error, no processing. Logged critical issue #26. Could not complete order/subscription creation through Classic checkout browser flow.
