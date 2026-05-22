---
id: 90
title: 'stage-07: 10 Payment Method, Auto-Renew Toggle, and Shipping'
status: closed
priority: high
created: 2026-05-19T22:56:10.656572189+02:00
updated: 2026-05-22T03:26:37.751183115+02:00
started: 2026-05-20T13:41:52.910522945+02:00
completed: 2026-05-20T15:00:56.027562581+02:00
tags:
    - qa
    - stage-07
class: standard
---

Source: stages/07-customer-portal/10-payment-method-and-shipping.md

[[2026-05-20]] Wed 15:00
QA notes: Manage payment methods link navigates to My Account > Payment methods, but cust1 has no saved methods and no Stripe card-on-file fixture, so Pro card details/update-payment/auto-renew off-on blocked (issue #54). Shipping update on #643 opens prefilled modal but Save Address fails and meta remains unchanged (issue #55). Cutoff clone #683 with next payment in 2 days displays current shipping address, hides Update Shipping Address, and shows 3-day cutoff message. Cancelled #668 has no auto-renew toggle visible; Reactivate remains visible under existing issue #45. PayPal/Paddle skipped per user.

[[2026-05-22]] Fri 03:26
Issue #54 fixed. Browser QA re-run: cust1 Payment methods shows Visa ending 4242; Subscription #633 shows Stripe/card-on-file + update link; update link redirects to Stripe Billing Portal; auto-renew toggled Off then On successfully. PayPal/Paddle skipped per instruction; Stripe secure card-entry iframe not automatable, but portal handoff verified.
