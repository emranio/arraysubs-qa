---
id: 170
title: Succeeded SEPA renewal stuck on-hold when the official Stripe webhook is missed; reconcile sweep marks it 'taken' instead of paid
status: closed
priority: high
created: 2026-11-26T01:17:45.189807+06:00
updated: 2026-11-26T03:33:48.727198+06:00
started: 2026-11-26T03:33:48.727198+06:00
completed: 2026-11-26T03:33:48.727198+06:00
tags:
    - stripe
    - sepa
    - webhook
    - reconcile
    - renewal
    - free
class: standard
---

Active QA task / day / plan: N/A — no active QA plan; user-requested investigation of a customer SEPA report, 2026-11-26 Asia/Dhaka. Environment: https://mirror-help.arrayhash.com, arraysubs main b67bb4b + arraysubspro main d95a1b4, WooCommerce 11.3.1, WooCommerce Stripe 11.2.0, Stripe test mode, split UPE, SEPA enabled, currency temporarily EUR. Customer's 3-file SEPA patch was applied temporarily (reverted after testing).

Affected: subscription #131076, renewal order #131124, PaymentIntent pi_3UflYBJG5OzSNVs20ogFJhw6 / charge py_3UflYBJG5OzSNVs20lEKi2lc (€5, succeeded at Stripe). Customer WP user 3312 / qa.sepac / qa.sepa.c.20261126@example.com / customer. Admin route: wp-admin/admin.php?page=wc-orders&action=edit&id=131124.

Steps: 1) Patched SEPA subscription; bind a PM that settles after ≥3 min (IBAN AT321904300235473204, offline-mandate SetupIntent). 2) Run the renewal: Woo Stripe payment_intent.processing moves the order to on-hold (settling markers set). 3) Make the official Woo Stripe endpoint miss charge.succeeded (temporarily wrong test_webhook_secret; ArraySubs' own endpoint left working). 4) After the PI succeeds, run do_action('arraysubs_gateway_reconcile').

Expected: the backstop sweep completes the renewal (order paid with py_ transaction, subscription advanced).
Actual: order note 'Stripe took payment pi_3UflYB… for this renewal, but the order could not be marked paid with it because the order or its subscription no longer matches that payment…'; order stays on-hold; transaction id and _stripe_intent_id cleared; _arraysubs_stripe_paid_after_order set; subscription _completed_payments unchanged and next date not advanced; immediate cancellation of the subscription is refused while this is unresolved. Customer charged, renewal unpaid until the merchant marks it paid by hand. Neither the ArraySubs endpoint (still receiving events) nor the sweep completed it.

Root cause (code-verified): ProviderPaymentCompletion.php:97-102 refuses orders where needs_payment() is false, and Woo Stripe always puts a processing SEPA renewal on-hold (class-wc-stripe-webhook-handler.php ~1396-1408), while WC payment_complete() accepts on-hold. Woo Stripe also ignores payment_intent.succeeded for on-hold orders, so its charge.succeeded is the only completion path; after the sweep detaches the payment, a redelivered charge.succeeded can no longer find the order. Related: extractChargeIdFromIntent / ensureStripeChargeTransactionForOrder (StripeRenewalPaymentTrait.php ~1001-1075) accept only ch_, so SEPA py_ ids are replaced by pi_ when ArraySubs completes an order, which breaks later Woo Stripe refunds.

Fix direction: let ProviderPaymentCompletion complete an on-hold order whose settling intent matches (_arraysubs_stripe_settling_intent_id / _arraysubs_settlement_txn); accept py_ charge ids; do not label a lost webhook as 'no longer matches'. Applies to deployed code for any Stripe bank debit, not only after the SEPA patch.

**Resolved 2026-11-26:** Fixed on branch fix/stripe-sepa-subgateways (arraysubs, uncommitted as of 2026-11-26 Asia/Dhaka). Verified live on mirror-help with the branch deployed: renewal #131337 (sub #131290) on-hold with the Woo Stripe webhook secret deliberately broken was completed by the reconcile sweep — note 'Reconciled from the gateway: this renewal settled but no notification arrived', txn py_, Renewal Payment Successful email, _completed_payments advanced.
