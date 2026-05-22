---
id: 71
title: 'stage-05: 11 Stripe Test Card Flow (Pro)'
status: closed
priority: high
created: 2026-05-19T22:56:08.017921313+02:00
updated: 2026-05-20T13:08:27.980438106+02:00
started: 2026-05-20T10:28:06.010555968+02:00
completed: 2026-05-20T13:08:27.980437184+02:00
tags:
    - qa
    - stage-05
claimed_by: mold-glade
claimed_at: 2026-05-20T13:08:27.980437975+02:00
class: standard
---

Source: stages/05-checkout/11-stripe-test-card-flow.md

[[2026-05-20]] Wed 13:08
QA attempted/blocked. Confirmed ArraySubsPro active and Woo Stripe gateway enabled in test mode; PayPal/Paddle skipped. Woo Stripe settings include test publishable/secret keys and test_webhook_secret; gateway title visible at checkout as 'Payment methods Test Mode'. Browser path: customer-stripe@example.test added Basic Monthly #197 and reached checkout. Expected hosted Stripe Checkout Session did not occur; checkout renders inline Woo Stripe secure Payment Element iframe. Alumnium could see secure iframe but could not type card details. Direct WebDriver fallback inspected Stripe frames and still found no reachable card inputs; clicking Place Order returned 'Your payment information is incomplete.' Paid card, trial SetupIntent, SCA, and cancel-session subtests could not be completed. Filed qa/issues #32.
