---
id: 169
title: Stripe split-UPE SEPA (stripe_sepa_debit) subscriptions never capture billing context; renewals become manual invoices
status: closed
priority: high
created: 2026-11-26T01:17:25.863669+06:00
updated: 2026-11-26T03:33:48.709161+06:00
started: 2026-11-26T03:33:48.70916+06:00
completed: 2026-11-26T03:33:48.70916+06:00
tags:
    - stripe
    - sepa
    - gateway
    - renewal
    - free
class: standard
---

Active QA task / day / plan: N/A — no active QA plan; user-requested investigation of a customer report (Agustín, ArraySubs 2.1.2 + Woo Stripe 11.2.0 SEPA), run 2026-11-26 Asia/Dhaka. Environment: https://mirror-help.arrayhash.com, arraysubs main b67bb4b + arraysubspro main d95a1b4 (pulled and built on server), WooCommerce 11.3.1, WooCommerce Stripe 11.2.0, Stripe test mode, split UPE (Optimized Checkout off — ArraySubs forces this for recurring carts), SEPA enabled, store currency temporarily EUR, block checkout. QA product #131051 'QA SEPA Daily Plan' (daily €5, now draft). Browser: agent-browser isolated guest sessions sepa-cust/sepa-cust3/sepa-cust4 (became customers at checkout). Code paths verified by 5 code readers + adversarial verifiers against the deployed source.

Affected: subscription #131054 (parent order #131053, renewal #131065). Customer WP user 3311 / qa.sepa / qa.sepa.a.20261126@example.com / customer.
URL: https://mirror-help.arrayhash.com/checkout/ (block checkout), product /product/qa-sepa-daily-plan/.

Steps: 1) Enable SEPA in Woo Stripe with Optimized Checkout off (gateway id becomes stripe_sepa_debit). 2) Buy a subscription choosing 'SEPA Direct Debit', IBAN AT611904300234573201. 3) After the order completes, force the renewal (RenewalScheduler schedule/scheduleInvoice to now, run arraysubs_generate_renewal_invoice + arraysubs_process_renewal).

Expected: subscription stores _payment_gateway=stripe, cus_, pm_, _gateway_status=active; renewal creates an off-session SEPA PaymentIntent.
Actual: subscription meta _payment_gateway=stripe_sepa_debit, _payment_method=stripe_sepa_debit, no _gateway_customer_id/_gateway_payment_method_id/_gateway_status; arraysubs_is_automatic_payment_subscription()=false; GatewayRegistry::get('stripe_sepa_debit')=null. Renewal #131065 note 'Renewal order created. Awaiting manual payment.' and '[ArraySubs] Renewal Invoice' email with pay link (Mailpit 'Invoice for subscription #131054').

Root cause: StripeOrderContextBridge.php:71 and :89 accept only 'stripe'; StripeDelegate has no getGatewayAliases(); PaymentHooksPaymentFiltersTrait.php:49 then classifies the sub manual (PaymentProcessor.php:93). Checkout side already treats stripe_* as Stripe (StripeSubscriptionCompatibility.php:1836, 1877, 2066) and disables Optimized Checkout for recurring carts (128-133), so every non-card reusable method hits this. Never supported in any version (git log -S stripe_sepa_debit empty in both repos), so not an old-install artifact.

Customer patch (bridge 71/89 + alias ['stripe_sepa_debit'] + StripeContextResolver.php:181) was applied temporarily on the server and verified: new sub #131076 (order #131075, user 3312 qa.sepac) stored stripe/cus_/pm_/active/sepa_debit/3201; renewals #131086 and #131097 created off-session PIs (payment_method_types [sepa_debit], Stripe reused mandate_1UflBC… from checkout), went on-hold, completed via webhook. Patch reverted afterwards.

Remaining scope for a real fix: one Stripe-family predicate (stripe or stripe_*) instead of a hard-coded SEPA alias — also covers iDEAL/Bancontact with SEPA tokens, Sofort, Klarna (EUR) and ACH/Bacs/BECS/ACSS (other currencies); StripeWebhooksTrait.php:88 resolveEntityIds, :580, :645; PaymentHooksGatewayMetaTrait.php:187; StripeWebhookEventsTrait.php:640 (SetupIntent/trial binding); renewal-sync-helpers.php:76 (Renewal Sync hides SEPA); arraysubs_subscription_has_persisted_automatic_payment_context known-slug list.

**Resolved 2026-11-26:** Fixed on branch fix/stripe-sepa-subgateways (arraysubs, uncommitted as of 2026-11-26 Asia/Dhaka). Verified live on mirror-help with the branch deployed: fresh SEPA checkout (order #131289 → sub #131290) captured stripe/cus_/pm_/sepa_debit/3201/active with no patch; renewal #131301 charged off-session and completed via webhook; card regression sub #131312 renewal #131322 charged ch_ and completed.
