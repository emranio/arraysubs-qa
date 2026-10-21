---
id: 70
title: 'Paddle: site URL checks fail when the same data is read over HTTPS and in a non-HTTPS scheduled run'
status: closed
priority: medium
created: 2026-10-20T09:45:31.225184104+02:00
updated: 2026-10-21T01:33:16.820453305+02:00
started: 2026-10-20T09:47:15.285079316+02:00
completed: 2026-10-21T01:33:16.820451862+02:00
tags:
    - paddle
    - webhook
    - cron
    - site-url
class: standard
---

Active QA task ID and scheduled day: N/A (found 2026-10-20 while fixing #65; user asked to fix it the same day). Fix log: qa/issues-65-69-fix-log.md.
Affected subscription IDs and order IDs: N/A (code-level; the same defect was reproduced live for Stripe on fixture renewal 69693, see #65).
Affected WordPress user/customer: N/A.
Exact route: Paddle custom data and contracts that store `site_url` => get_site_url() and later compare it with hash_equals(get_site_url(), ...): PaddlePaymentProcessingTrait (968, 1055), PaddleWebhooksTrait (722), PaddlePlanSwitchFinalizationTrait (569, 653), PaddleProductSync (598, 954); writers in PaddlePlanSwitchPricingTrait, PaddleProductsTrait, PaddleProductSync, PaddleDiscountResolver, PaddlePlanSwitchRefundsTrait; plan-switch hash input PaddlePlanSwitchPricingTrait:1385.
Reproduction (verified for the WordPress side on staging, 2026-10-20 07:39 UTC): `php -r` bootstrap of this site without HTTPS (how the system cron runs wp-cron.php) gives get_site_url() = http://mirror-help.arrayhash.com while home_url() and HTTPS requests give https://. get_site_url() passes the stored siteurl through set_url_scheme(), which follows is_ssl().
1. Data written during an HTTPS request (checkout, admin, webhook) records https://...; the same check in a scheduled run compares against http://... and fails, or the reverse.
2. The live Stripe case: a WooCommerce Stripe Pay-page PaymentIntent recorded https://, the cron-run renewal attempt compared http:// and fell back to the #65 dead end (fixed in #65 with a scheme-insensitive check).
Expected: the same site is recognised in every context.
Actual: Paddle custom data / contracts written in one context are rejected as another site's in the other context; a hash that includes the URL differs between contexts.
Proof: code references above; staging CLI output "cli get_site_url=http://mirror-help.arrayhash.com home_url=https://mirror-help.arrayhash.com is_ssl=false".
Scope notes: Stripe ArraySubs metadata uses home_url() (keeps the stored scheme) and is not affected; only WooCommerce Stripe's own site_url needed the #65 fix.

[[2026-10-21]] Wed 01:33

Closed 2026-10-20 (UTC) after fix + verification. Changes are in the working tree and NOT committed (user request). Full record: qa/issues-65-69-fix-log.md.

Fix 2026-10-20 (uncommitted): new `src/functions/site-url-helpers.php`: `arraysubs_get_site_identity_url()` (the saved `siteurl`, the same in every request) for site URLs stored with provider data or hashed, and `arraysubs_is_this_site_url()` (scheme-insensitive, trailing slash and case ignored) for checks. Applied to every Paddle writer, hash input (checkout creation key, plan-switch key, discount fingerprint) and comparison (10 files), and to Stripe's Pay-page intent check (#65).
Verified 2026-10-20 by `qa/fix-65-69/scripts/fix70-probe.php` → `qa/fix-65-69/evidence/fix70-probe.txt`: run through WP-CLI and through plain php as www-data without HTTPS (like the system cron): `get_site_url()` differs (https:// vs http://) while the identity URL, URL matching and the Paddle checkout and plan-switch keys are identical. Staging has no active Paddle subscriptions (9 on-hold, 38 cancelled), so no end-to-end Paddle run.
Review rounds 1-3 (uncommitted, probes; details in the fix log): Paddle catalogue and plan-switch contract fingerprints hash the scheme-less identity key (`arraysubs_get_site_identity_key()`), the reservation/restore rebuild uses all 13 contract keys (R2-4) and the binding stores the key exactly as hashed (R3-3, safe for %XX paths); Mollie's writer and its three checks and PayPal's `site_key` use the identity URL/key (R2-5); completion evidence passes the identity URL (R2-6); a Mollie payment that does not belong to its matched order is ignored entirely (R3-11); the Stripe webhook endpoint check and the stored Pay-page confirmation URL compare without the scheme (R3-5, R3-12). Staging has no active Paddle subscriptions (9 on-hold, 38 cancelled), so no end-to-end Paddle run. The two active PayPal subscriptions (28650, 28663) were created with the old scheme-including `site_key` and will fail the site check after this change (no backward compatibility, AGENTS.md).
Review round 4: the ignored Mollie payment is reported to the webhook router as ignored (R4-9), and a renewal's own Mollie payment that settles after the order was paid another way adds one order note asking the store to check for a double payment and refund one (R4-11).
Review round 5: the renewal's own Mollie payment settling while the order was paid, or set to be paid, another way (also with the Mollie card gateway on the Pay page, which used to fail the webhook with HTTP 500) adds one order note (note first, then the de-dup marker) and is acknowledged as ignored (R5-6, R5-7, R5-11, N11); refunding that duplicate payment no longer refunds the order or cancels the subscription, and refund mismatches report ignored (N9); a payment naming an order or subscription this store does not have is ignored (N10).

Review rounds 6-8: a Mollie dashboard refund reaches WooCommerce only for the payment that paid the order (empty transaction id included), and payments naming no order of this store are ignored (R6-C3/C15, N6).
