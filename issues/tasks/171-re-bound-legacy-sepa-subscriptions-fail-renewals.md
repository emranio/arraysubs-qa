---
id: 171
title: Re-bound legacy SEPA subscriptions fail renewals with renewal_order_binding_invalid because open renewal orders keep stripe_sepa_debit
status: closed
priority: medium
created: 2026-11-26T01:17:45.205876+06:00
updated: 2026-11-26T03:33:48.744643+06:00
started: 2026-11-26T03:33:48.744642+06:00
completed: 2026-11-26T03:33:48.744642+06:00
tags:
    - stripe
    - sepa
    - renewal
    - dunning
    - free
class: standard
---

Active QA task / day / plan: N/A — no active QA plan; user-requested investigation of a customer SEPA report, 2026-11-26 Asia/Dhaka. Environment: https://mirror-help.arrayhash.com, arraysubs main b67bb4b + arraysubspro main d95a1b4, WooCommerce 11.3.1, WooCommerce Stripe 11.2.0, Stripe test mode, split UPE, SEPA enabled, currency temporarily EUR. Customer's 3-file SEPA patch was applied temporarily (reverted after testing).

Affected: subscription #131054 (parent #131053), open manual renewal #131065. Customer WP user 3311 / qa.sepa / qa.sepa.a.20261126@example.com / customer.

Steps: 1) SEPA subscription created on unpatched code (manual invoice #131065 already emailed). 2) With the alias patch, re-capture context from the parent order: do_action('arraysubs_gateway_meta_retry', 131054, 131053, '', 1) — this correctly wrote stripe/cus_/pm_/active/sepa_debit and created no charge. 3) Run do_action('arraysubs_process_renewal', 131054).

Expected: either the open invoice is charged automatically or left for manual payment, without a failure.
Actual: order #131065 → failed with 'renewal_order_binding_invalid: The fresh order is not one exact payable Stripe renewal for this subscription. No charge was attempted.'; customer got '[ArraySubs] Renewal Payment Failed', admin got Payment Failed + Failed order; _payment_retry_attempts=1 and next retry +1 day, which will fail the same way. No money moved.

Root cause: renewal orders copy the subscription's _payment_method at creation (OrderCreation.php:46, 276-283) and StripeRenewalPaymentTrait.php:546 validateLockedRenewalOrder requires exactly 'stripe' before prepareRenewalOrderMeta re-stamps it. Same exact check in StripeIntentResolutionTrait.php:978, StripePaymentMethodsTrait.php:527, StripeRequiresActionTrait.php:1722, StripeAsyncFailureTrait.php:57/63. Code-verified second trigger: a customer paying a renewal on the Pay page with the split SEPA gateway re-stamps the order to stripe_sepa_debit (WC class-wc-form-handler.php:539); if that payment fails, every later automatic retry fails the same way until cancellation.

Fix direction: accept the Stripe family in validateLockedRenewalOrder (it already re-stamps to 'stripe'), or canonicalize open renewal orders when a subscription is re-bound.

**Update 2026-11-26 (recovery verified live, customer patch + one-line fix):** Already-invoiced legacy subscriptions ARE recoverable with no customer action. Fresh legacy case: sub #131172 (parent #131171, user 3314 / qa.sepae / qa.sepa.e.20261126@example.com / customer), manual invoice #131184 emailed on unpatched code. Recovery A (migration-style, data only): `do_action('arraysubs_gateway_meta_retry', 131172, 131171, '', 1)` re-captured stripe/cus_/pm_/sepa_debit with no charge; then `_payment_method` of the open invoice set to `stripe`; `arraysubs_process_renewal` charged it off-session (pi_3UflqhJG5OzSNVs20SdsTllq, mandate reused), order completed via webhook, sub advanced (`_completed_payments=2`), customer received "Payment received". Recovery B (code fix, no order edit): StripeRenewalPaymentTrait.php validateLockedRenewalOrder accepting `stripe` OR `stripe_*` order payment methods charged an untouched `stripe_sepa_debit` invoice #131196 (pi_3UflsbJG5OzSNVs21fwojP3N) and re-stamped it to `stripe` itself. Exactly one PaymentIntent per invoice in both runs. Fix should include: the family check at :546 (same literal check at StripeIntentResolutionTrait.php:978, StripePaymentMethodsTrait.php:527, StripeRequiresActionTrait.php:1722, StripeAsyncFailureTrait.php:57/63), a migration that re-captures from the parent order for `_payment_gateway` matching `stripe_*`, and a reset of `_payment_retry_attempts` for subs that already hit binding_invalid. Patches reverted; site restored.

**Resolved 2026-11-26:** Fixed on branch fix/stripe-sepa-subgateways (arraysubs, uncommitted as of 2026-11-26 Asia/Dhaka). Verified live on mirror-help with the branch deployed: legacy sub #131241 (stripe_sepa_debit, manual invoice #131251 emailed on old code) was repaired by the generation-2 recovery sweep automatically via cron after deploy: canonical stripe routing + credentials written from the parent order (no charge), then invoice #131251 charged off-session and completed via webhook (customer received Payment received). Family check in validateLockedRenewalOrder also accepts stripe_* renewal orders.
