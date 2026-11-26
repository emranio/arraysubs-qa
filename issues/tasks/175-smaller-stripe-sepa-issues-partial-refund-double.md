---
id: 175
title: 'Smaller Stripe/SEPA issues: partial refund double-recorded, day-21 settlement grace, Renewal Sync hides SEPA'
status: open
priority: low
created: 2026-11-26T01:18:08.788835+06:00
updated: 2026-11-26T01:18:08.788835+06:00
tags:
    - stripe
    - sepa
    - refunds
    - renewal-sync
    - free
class: standard
---

Active QA task / day / plan: N/A — no active QA plan; user-requested investigation of a customer SEPA report, 2026-11-26 Asia/Dhaka. Environment: https://mirror-help.arrayhash.com, arraysubs main b67bb4b + arraysubspro main d95a1b4, WooCommerce 11.3.1, WooCommerce Stripe 11.2.0, Stripe test mode, split UPE, SEPA enabled, currency temporarily EUR.

Affected: N/A (code-verified by investigator + adversarial verifier; not reproduced live).

1) Partial refund double-counted: when the ArraySubs endpoint processes charge.refunded first, syncExternalStripeRefundToWooCommerce stores _stripe_refund_id only on the refund record (StripeRefundsTrait.php ~371-401); Woo Stripe's duplicate check reads the order-level key (class-wc-stripe-webhook-handler.php ~1029-1031) and records the refund again. Full refunds are blocked by WC's remaining-amount check; partial ones can push the order to fully refunded. Not SEPA-specific.
2) Settlement grace: _arraysubs_pending_deadline / _arraysubs_settlement_until expire at 21 days for sepa_debit and are only extended lazily by the 6-hourly sweep; orderIsAwaitingSettlement (PaymentHooksRenewalsTrait.php:319-338) has no grace, so the hourly overdue sweep can put a still-processing SEPA subscription on hold for up to 6 h (RenewalPaySettlementGuard already uses a 1-day STRIPE_RECONCILE_GRACE).
3) Renewal Sync: arraysubs_is_renewal_sync_supported_gateway() (renewal-sync-helpers.php:76) supports only 'stripe'/'arraysubs_stripe', so stripe_sepa_debit is hidden/rejected at checkout whenever Renewal Sync applies.
4) charge.failed before payment_intent.payment_failed adds a misleading 'SCA authentication failed' note on SEPA renewals (EmailManager.php:427 area) — cosmetic.

**Update 2026-11-26:** Items 2 (settlement grace, now Stripe-only and tied to the settling intent) and 3 (Renewal Sync accepts stripe_* via arraysubs_is_stripe_family_gateway_id) fixed on branch fix/stripe-sepa-subgateways. Item 1 (partial refund double-recorded when the ArraySubs endpoint runs first) and item 4 (SCA note wording) are NOT fixed yet.
