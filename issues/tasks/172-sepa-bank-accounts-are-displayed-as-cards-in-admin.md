---
id: 172
title: SEPA bank accounts are displayed as cards in admin, My Account and emails
status: closed
priority: medium
created: 2026-11-26T01:18:08.74051+06:00
updated: 2026-11-26T03:33:48.761272+06:00
started: 2026-11-26T03:33:48.761272+06:00
completed: 2026-11-26T03:33:48.761272+06:00
tags:
    - stripe
    - sepa
    - display
    - my-account
    - admin
    - free
class: standard
---

Active QA task / day / plan: N/A — no active QA plan; user-requested investigation of a customer SEPA report, 2026-11-26 Asia/Dhaka. Environment: https://mirror-help.arrayhash.com, arraysubs main b67bb4b + arraysubspro main d95a1b4, WooCommerce 11.3.1, WooCommerce Stripe 11.2.0, Stripe test mode, split UPE, SEPA enabled, currency temporarily EUR.

Affected: subscription #131076 (captured with the temporary customer patch). Customer WP user 3312 / qa.sepac / qa.sepa.c.20261126@example.com / customer. URL: https://mirror-help.arrayhash.com/my-account/as-view-subscription/131076/ (customer session sepa-cust3).

Expected: 'SEPA Direct Debit ending in 3201' (or similar), labelled as a bank account / billing authorization.
Actual: My Account shows 'Payment Method: Stripe' and 'Card on File: ending in 3201'. StripeDelegate::formatPaymentMethodForDisplay(131076) returns {"title":"Card ending in 3201","brand":"","last4":"3201"}; the admin Gateway panel labels it 'Card on file' (customer's locale shows 'Registered Card'). The bridge also overwrote _payment_method_title 'SEPA Direct Debit' with 'Stripe'.

Sites: StripePaymentMethodsTrait.php:112-151 (brand defaults to Card, always '%1$s ending in %2$s', ignores _payment_method_type); src/resources/pages/subscription-detail/GatewayPanel.jsx:31 ('Card on file'); AdminDisplayHooks.php:327-343 (My Account 'Card on File:' with empty brand); StripeOrderContextBridge.php:221-223 (forces _payment_method/_title to 'stripe'/'Stripe'; Mollie bridge keeps the host title); customer-renewal-requires-verification.php:47 and Stripe log strings. Mollie already has 'SEPA Direct Debit ending in %s' (MollieDelegate.php ~1351-1358) to reuse.

**Resolved 2026-11-26:** Fixed on branch fix/stripe-sepa-subgateways (arraysubs, uncommitted as of 2026-11-26 Asia/Dhaka). Verified live on mirror-help with the branch deployed: admin Gateway panel shows 'BANK ACCOUNT ON FILE — SEPA Direct Debit ending in 3204' (card sub: 'CARD ON FILE — Visa ending in 4242'); My Account shows 'Bank Account on File: SEPA Direct Debit ending in 3201'; verification email uses the gateway title.
