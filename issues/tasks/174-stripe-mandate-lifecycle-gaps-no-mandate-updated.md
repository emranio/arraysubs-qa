---
id: 174
title: 'Stripe mandate lifecycle gaps: no mandate.updated handling and stale _gateway_mandate_id forwarded after a payment-method change'
status: closed
priority: medium
created: 2026-11-26T01:18:08.772583+06:00
updated: 2026-11-26T03:33:48.795275+06:00
started: 2026-11-26T03:33:48.795275+06:00
completed: 2026-11-26T03:33:48.795275+06:00
tags:
    - stripe
    - sepa
    - mandate
    - webhook
    - free
class: standard
---

Active QA task / day / plan: N/A — no active QA plan; user-requested investigation of a customer SEPA report, 2026-11-26 Asia/Dhaka. Environment: https://mirror-help.arrayhash.com, arraysubs main b67bb4b + arraysubspro main d95a1b4, WooCommerce 11.3.1, WooCommerce Stripe 11.2.0, Stripe test mode, split UPE, SEPA enabled, currency temporarily EUR.

Affected: N/A (code-verified). Live context: renewals of #131076 omitted the mandate parameter and Stripe used the PM's own mandate (charge mandate mandate_1UflBC… = checkout mandate), so the normal path works.

Gaps (code-verified): 1) No endpoint handles mandate.updated → inactive: not in Woo Stripe's events, not in StripeWebhookProvisioner.php:316-339, event_map or official map; WebhookRouter acks unknown events; the pre-charge check never inspects the mandate; _gateway_status stays active. 2) _gateway_mandate_id is cached only from order _stripe_mandate_id (SetupIntent/trial or acss/Indian-card paths) and never cleared when bindStripePaymentMethod binds a new pm_ (StripePaymentMethodsTrait.php:441-497); StripeRenewalPaymentTrait.php:324-330 then sends pm_B with mandate_A. Exact Stripe rejection for that mismatch not yet confirmed in test mode.

Expected: revoked mandates flag the subscription (needs new payment method) before dunning; mandate meta cleared/refreshed on PM change.

**Resolved 2026-11-26:** Fixed on branch fix/stripe-sepa-subgateways (arraysubs, uncommitted as of 2026-11-26 Asia/Dhaka). Verified live on mirror-help with the branch deployed: handleMandateUpdated (mandate.updated, inactive, bank-debit only) marks the binding errored and drops the cached mandate; simulated on sub #131241: next renewal became a manual invoice with the Invoice email sent and no charge attempted. Stale _gateway_mandate_id cleared on PM change/rebind. Provisioner event list gained mandate.updated and the auto-healer updated the Stripe endpoint (fingerprint matched within a minute of deploy).
