---
id: 134
title: 'stage-14: 04 Subscription Detail Cards'
status: closed
priority: medium
created: 2026-05-19T22:56:17.857754798+02:00
updated: 2026-05-24T16:16:15.212477546+02:00
started: 2026-05-23T08:06:53.446714904+02:00
completed: 2026-05-23T11:39:13.932303595+02:00
tags:
    - qa
    - stage-14
class: standard
---

Source: stages/14-admin-subscriptions/04-subscription-detail-cards.md

[[2026-05-23]] Sat 11:39
QA notes: Seeded missing card fixtures: #683 _waiting_cancellation/_cancellation_scheduled_date/_cancellation_reason plus shipping totals 9.99/7.99; #633 checkout builder Company Size field/meta and _last_gateway_transaction_id. Browser-verified #683 Scheduled Cancellation card: Pending badge, scheduled cancel 22 June 2026 6:56 PM (UTC+6), reason Too expensive; Retention Offers shows Discount Offer shown/accepted timestamps; header shows Undo Scheduled Cancellation/Edit Subscription/Login as Customer; Skip & Pause visible with Skip Renewal and Pause Subscription actions; Subscription Shipping shows recurring Flat rate, initial .99, renewal .99, paid Yes. #482 Coupon Discount shows RECURRING, renew20for3, 20%, total cycles 3, remaining 3, captured date, no deleted/expired badge. #633 Stripe gateway card shows CONNECTED, Visa 4242, expiry 05/2027 after Resync from Gateway, customer ID cus_UYpDOxCwl8vQtA, last transaction pi_stage14_qa; Detach Gateway modal copy matched and Cancel left gateway attached. #987 plain subscription hides scheduled cancellation, coupon, payment gateway, checkout builder, shipping cards; Skip & Pause remains visible. PayPal/Paddle skipped.

[[2026-05-24]] Sun 16:16
Issue #103 fixed: cancelled subscription detail Skip & Pause > Vacation Mode now says 'Subscription is cancelled' instead of active. Verified on #508 with agent-browser + agent-browser. Screenshot qa/artifacts/issue-103-cancelled-vacation-mode.png.
