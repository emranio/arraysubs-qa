---
id: 67
title: 'stage-05: 07 Guest Checkout with Auto-Create OFF'
status: closed
priority: high
created: 2026-05-19T22:56:07.553066875+02:00
updated: 2026-05-20T12:13:19.434665273+02:00
started: 2026-05-20T10:28:05.942773639+02:00
completed: 2026-05-20T12:13:19.43466374+02:00
tags:
    - qa
    - stage-05
claimed_by: mold-glade
claimed_at: 2026-05-20T12:13:19.434665162+02:00
class: standard
---

Source: stages/05-checkout/07-guest-checkout-with-auto-create-off.md

[[2026-05-20]] Wed 12:13
QA done. Disabled arraysubs checkout.auto_create_account and confirmed Woo guest checkout/sign-up options enabled. Confirmed guest-claim@example.test did not exist before checkout. Guest checkout with Basic Monthly succeeded without forced registration: order #440 total 9.99, related subscription #441 Pending, next 20 June 2026 (UTC+6), 9.99 / Every month, page remained guest. After checkout no WP user existed. Subscription #441 initially had customer_id=0 and _customer_email=guest-claim@example.test. Registered same email via WP-CLI as user #13 (guest-claim / Test1234!Claim); claim-by-email linked subscription #441 to customer_id=13. Browser login as guest-claim succeeded; My Account > Subscriptions showed Basic Monthly, Pending, total 9.99 Every month, View link present. Note: customer portal list showed next payment as dash even though order page showed next date. Restored checkout.auto_create_account=true for later tasks.
