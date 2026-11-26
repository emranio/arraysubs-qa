---
id: 173
title: Billing context recovery cannot recover SEPA subscriptions and can bind a card to them
status: closed
priority: medium
created: 2026-11-26T01:18:08.756804+06:00
updated: 2026-11-26T03:33:48.778844+06:00
started: 2026-11-26T03:33:48.778843+06:00
completed: 2026-11-26T03:33:48.778843+06:00
tags:
    - stripe
    - sepa
    - recovery
    - free
class: standard
---

Active QA task / day / plan: N/A — no active QA plan; user-requested investigation of a customer SEPA report, 2026-11-26 Asia/Dhaka. Environment: https://mirror-help.arrayhash.com, arraysubs main b67bb4b + arraysubspro main d95a1b4, WooCommerce 11.3.1, WooCommerce Stripe 11.2.0, Stripe test mode, split UPE, SEPA enabled, currency temporarily EUR.

Affected: N/A for live records (code-verified, not executed live to avoid binding test cards). Relevant legacy example: subscription #131054 before re-capture (user 3311 / qa.sepa / qa.sepa.a.20261126@example.com / customer).

Expected: a pre-fix SEPA subscription can be recovered safely from its parent order (cus_ + sepa pm_ + mandate) without charging.
Actual (code): RecoveryMigration is a one-shot sweep gated on arraysubs_billing_recovery_generation >= 1 with no admin/REST/CLI trigger (RecoveryMigration.php:53-55); RecoveryScanner only selects registry keys, so alias slugs like stripe_sepa_debit are never candidates (RecoveryScanner.php:70-90, 121-125); RecoveryWorker reads the PM only from subscription meta (RecoveryWorker.php:100), never the parent order's _stripe_source_id; its fallback lists type=card only and binds the newest card (StripeContextRecoveryTrait.php:241, RecoveryWorker.php:216-229); the detached-PM SetupIntent is hard-coded to ['card'] without mandate_data (StripeContextRecoveryTrait.php:211); persistContext does not canonicalize _payment_gateway/_payment_method (RecoveryWorker.php:243-252); trial status excluded (RecoveryScanner.php:50).

Live-verified safe path instead: with the alias in place, do_action('arraysubs_gateway_meta_retry', sub, parent_order, '', 1) re-captured #131054 from its parent order with no charge (see the binding_invalid issue for the open-invoice caveat).

**Resolved 2026-11-26:** Fixed on branch fix/stripe-sepa-subgateways (arraysubs, uncommitted as of 2026-11-26 Asia/Dhaka). Verified live on mirror-help with the branch deployed: RecoveryScanner includes Stripe sub-gateway aliases, RecoveryWorker re-captures from the paid checkout order before resolving (rolled back on transient failure, errored on terminal), persistContext canonicalises routing, SetupIntent/PM listing are type-aware and bank-debit methods are never replaced by another mandate; GENERATION bumped to 2. Live: sub #131241 repaired_future_renewal/renewal_pending; two PayPal demo subs skipped as gateway_not_recoverable.
