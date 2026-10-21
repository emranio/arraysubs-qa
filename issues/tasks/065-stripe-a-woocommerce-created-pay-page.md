---
id: 65
title: 'Stripe: a WooCommerce-created Pay-page PaymentIntent stops later automatic renewal attempts'
status: closed
priority: medium
created: 2026-10-19T23:52:54.567847236+02:00
updated: 2026-10-21T01:33:16.316779567+02:00
started: 2026-10-20T08:36:08.81237034+02:00
completed: 2026-10-21T01:33:16.316778004+02:00
tags:
    - stripe
    - recovery
    - retry
    - pay-page
    - staging-qa
class: standard
---

Active QA task ID and scheduled day: N/A (found by code review round 3 during the #56-#64 fix work, 2026-10-19). Plan: N/A; fix log qa/stripe-issues-56-64-fix-log.md (section Review round 3, item R3-M2b); review notes qa/stripe-dev-support-2026-10-18/evidence/fix/rev3-round2-review.md.
Affected subscription IDs and order IDs: N/A (not reproduced live; code-level).
Affected WordPress user/customer: N/A.
Exact route: customer Pay page /checkout/order-pay/<renewal order>/?pay_for_order=true&key=... for an automatic Stripe renewal that failed.
Reproduction (code reading):
1. An automatic Stripe renewal declines; its PaymentIntent PI1 stays on the order (`_stripe_intent_id`) and is the attempt result.
2. The customer opens the Pay page, starts 3D Secure with another card and leaves. PI1 is now requires_action.
3. Before any retry runs, the customer opens the Pay page again. WooCommerce Stripe does not reuse a requires_action intent (woocommerce-gateway-stripe/includes/payment-methods/class-wc-stripe-upe-payment-gateway.php:4137-4172) and creates PI2 with its own metadata; `_stripe_intent_id` becomes PI2. The customer leaves again, or the card declines.
4. The next retry calls StripeIntentResolutionTrait::resolveExistingIntent() for PI2: an authentication state classifies as `invalid` (no ArraySubs metadata, attempt result is PI1); a declined PI2 reaches clearTerminalIntentForReplacementWhileLocked(), which refuses because the attempt result names PI1. Both return pending (2-day deadline) and charge nothing; nothing schedules another attempt.
Expected: the retry cancels or ignores the customer's unfinished zero-money intents (PI1 and PI2) and charges the payment method on file.
Actual: no charge; the renewal stays unpaid until the customer pays, or the grace period holds and cancels the subscription.
Proof: code references above.
Scope notes: a single abandoned Pay-page attempt (PI1 reused) is handled since 2026-10-19 (isAbandonedCustomerAttemptIntent(), live PASS on 69222 and 69406). Any stored intent that ArraySubs did not create hits the same dead end.

[[2026-10-21]] Wed 01:33

Closed 2026-10-20 (UTC) after fix + live verification. Changes are in the working tree and NOT committed (user request). Full record: qa/issues-65-69-fix-log.md.

Fix 2026-10-20 (uncommitted): `resolveExistingIntent()` recognises a PaymentIntent WooCommerce Stripe created for the renewal on the Pay page (`isCustomerPayPageIntent()`: no ArraySubs metadata, `order_id` = order number, this site's `site_url` compared without the scheme, the order's Stripe customer, single linked subscription). An open one holding no money is cancelled (read back cancelled with zero money), a cancelled one released; then `_stripe_intent_id` and `_arraysubs_pending_transaction_id` point back to the attempt's own PaymentIntent, which is resolved as before (`releaseCustomerPayPageIntent()`). With no earlier attempt the order-scoped search runs before a fresh charge. The search lists the order's other open customer intents (`customer_attempts`) and `closeCustomerPayPageAttempts()` cancels them before any replacement; a customer intent with money taken or in flight rules a replacement out. Nothing is cancelled until 30 minutes after the latest customer activity: the Pay-page submit (new `_arraysubs_customer_pay_started_at`, classic `woocommerce_before_pay_action` + Store API `woocommerce_store_api_checkout_order_processed`), the requires-action sighting, or the intent's creation; until then the attempt reports pending and queues itself for that time. A cancellation Stripe does not confirm queues a recheck in an hour.
Verified live 2026-10-20 (fixture customer 597, sub 69676, renewal 69693, card 0341): round 1 reproduced the #65 state (PI1 reused by visit 1, PI2 created by visit 2 and stored); a retry 30 s later deferred to 30 min after the last visit. The deferred attempt, run by the system cron, then hit the old dead end: Woo Stripe recorded `https://` and the non-HTTPS cron saw `http://` (fixed with the scheme-insensitive check, see #70); a traced replay showed the rest of the path working. Round 2 (system cron, 08:13 UTC): customer intent PI4 cancelled (abandoned), PI3 retired, card on file charged (declined as expected for 0341) → next retry queued, no deadline, attempt count 3; the decline's webhook was deferred by the busy lifecycle lock and resolved as `duplicate`.
Review rounds 1-3 (uncommitted; probes + live where noted, details in the fix log): the site check accepts the saved `siteurl` or `home` without the scheme, and ArraySubs' own Stripe metadata is written with the identity URL (round 1 L8). A Stripe Pay-page submit holds the renewal's charge lock and is refused with "A payment for this renewal is already being processed or confirmed…" while another payment holds it; an automatic attempt that meets a fresh Pay-page payment looks again at the stamp + 30 min (L1, live both directions on 69693). The no-attempt branch keeps the customer intent until the order-scoped search clears it (L2). An inconclusive order-scoped search, here and in the reconcile-before-retry check, re-queues the renewal hourly for a day (`_arraysubs_stripe_search_unresolved_since`); an unconfirmed cancellation of a customer intent re-queues hourly for a day per intent (`_arraysubs_stripe_cancel_unconfirmed`); such a recheck no longer charges again after a later decline (R3-1, see #67). These rechecks were verified by rolled-back probes with canned Stripe responses (a live run would need a Stripe outage).
Review round 4: live on 69693 (customer 597): while the automatic charge lock was held, the Pay-page submit was refused with "A payment for this renewal is already being processed or confirmed. Please check back later; if the order is still unpaid then, you can pay it here." and nothing was charged (`fix-65-69/screenshots/r3-9-paypage-refused-neutral.png`).
Review round 5: no change to the Stripe Pay-page intent handling; the retry-ladder changes in this round (see #67) keep the Pay-page attempt flow intact.

Review rounds 6-8: the Pay page also refuses a second payment while the renewal's own debit is settling, for every gateway (see #71); checked live 2026-10-20 on fixture order 68903 (refused while settling, payable again afterwards).
