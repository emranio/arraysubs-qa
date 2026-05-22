---
id: 74
title: 'stage-05: 14 Mixed Cart and Block Checkout'
status: closed
priority: high
created: 2026-05-19T22:56:08.413617479+02:00
updated: 2026-05-20T13:30:33.187045174+02:00
started: 2026-05-20T10:28:06.012975408+02:00
completed: 2026-05-20T13:30:33.187044042+02:00
tags:
    - qa
    - stage-05
claimed_by: mold-glade
claimed_at: 2026-05-20T13:30:33.187045074+02:00
class: standard
---

Source: stages/05-checkout/14-mixed-cart-and-block-checkout.md

[[2026-05-20]] Wed 13:30
QA complete. Preconditions: allow_mixed_cart=true, allow_multiple_in_cart=true; Stripe test mode visible and BACS enabled; PayPal/Paddle left untouched/skipped. Customer customer-mixed@example.test. Browser block cart: added Basic Monthly #197 and Standard Tee #447; cart showed both line items, no mixed-cart error, total 4.99. Block checkout #8 summary showed Basic Monthly with Renewals 9.99 Every month, duration/shipping recurring text; Standard Tee showed only 5.00 and one-time total; subtotal/total 4.99. Placed BACS order #582; thank-you page showed Basic Monthly and Standard Tee and exactly one related subscription #590. After payment complete: order #582 completed with two line items (Basic Monthly 9.99, Standard Tee 5), arraysubs_get_subscription_ids_for_order returned [590], subscription #590 active product #197 parent order #582 recurring 9.99. Negative: set allow_mixed_cart=false, fresh cart with Basic Monthly then Standard Tee showed error 'This cart already contains a subscription. Remove the subscription first or enable mixed checkout before adding regular products to the same order.' Expected wording differs, already tracked by qa/issues #22. Restored allow_mixed_cart=true; adding Standard Tee succeeded and cart total returned to 4.99.
