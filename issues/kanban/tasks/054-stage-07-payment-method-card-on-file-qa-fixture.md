---
id: 54
title: 'stage-07: Payment method card-on-file QA fixture missing'
status: closed
priority: high
created: 2026-05-20T15:00:46.896736453+02:00
updated: 2026-05-22T03:26:53.886484316+02:00
started: 2026-05-22T03:03:52.296911544+02:00
completed: 2026-05-22T03:26:53.886483104+02:00
tags:
    - qa
    - stage-07
    - customer-portal
    - payment-methods
claimed_by: mold-glade
claimed_at: 2026-05-22T03:26:53.886484216+02:00
class: standard
---

Task 10 expected cust1 to own a Stripe auto-gateway subscription with saved card details, update-payment-method link, and auto-renew toggle. Actual: Subscription #633/#683 show Direct bank transfer + Manage payment methods only; My Account > Payment methods shows No saved methods found. PayPal/Paddle intentionally skipped per user. Stripe-hosted card update and auto-renew off/on cannot be verified without a saved Stripe method fixture.

[[2026-05-22]] Fri 03:05
Plan: verify Stage 07 Task 10, inspect pro payment method/auto-renew rendering, then create missing Stripe fixture instead of testing PayPal/Paddle. Use configured Woo Stripe test key to create/attach a Stripe test payment method for cust1, create matching WooCommerce saved card token, bind Subscription #633 to Stripe gateway meta (_payment_gateway=stripe, _gateway_customer_id, _gateway_payment_method_id, card brand/last4/expiry, _gateway_status=active, _auto_renew=on), and enable automatic_payments.allow_auto_renew_toggle. Browser verify Payment methods page lists card, #633 shows card-on-file, update link redirects to stripe.com, auto-renew toggle off/on works. If browser POSTs fail with known Alumnium jQuery AJAX TypeError, patch only those portal calls to fetch and retest.

[[2026-05-22]] Fri 03:26
Fix/QA: created Stripe test fixture for cust1/subscription #633. Added Stripe customer user meta wp__stripe_customer_id=cus_UYpDOxCwl8vQtA, attached Stripe PM pm_1TZhYsJG5OzSNVs2OmyLYUID, created Woo saved card token #6, bound subscription #633 to Stripe gateway/card meta, enabled automatic_payments.allow_auto_renew_toggle. Fixed portal JS failures by switching payment-method update and pro auto-renew toggle POSTs from jQuery ajax to fetch with X-WP-Nonce. Built arraysubs and arraysubspro assets. Browser QA: My Account > Payment methods shows Visa ending 4242; #633 detail shows Stripe + Visa ending 4242 + Update payment method; Update payment method redirects to Stripe Billing Portal; Stripe portal shows saved card and Add payment method page (secure iframe not automatable); auto-renew disabled and re-enabled successfully, final state On.
