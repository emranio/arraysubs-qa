---
id: 56
title: Stripe portal card updates are always refused for existing subscriptions
status: closed
priority: high
created: 2026-10-19T11:18:06.978798998+02:00
updated: 2026-10-20T00:35:28.094937613+02:00
started: 2026-10-19T20:36:21.120137985+02:00
completed: 2026-10-20T00:35:28.094936641+02:00
tags:
    - stripe
    - payment-method
    - webhook
    - staging-qa
class: standard
---

Active QA task ID and scheduled day: S03-A (board task #9 in qa/stripe-dev-support-2026-10-18/kanban), phase 2, 2026-10-19.
Plan markdown path: qa/stripe-dev-support-2026-10-18/plan.md.
Affected subscription IDs and order IDs: subscription 68418, initial order 68417 (completed, 4242).
Affected WordPress user/customer: ID 579, sqa-p2-decline / sqa-p2-decline@example.com, role customer. Stripe customer cus_VT8uqNP3wxz3vs.
Exact route: https://mirror-help.arrayhash.com/my-account/as-view-subscription/68418/ -> "Update payment method" -> Stripe billing portal (billing.stripe.com) -> Add payment method, "Use as default" checked. Browser session sqa-decline (customer). Core 4791832 + Pro edaa401, official WooCommerce Stripe Gateway 10.9.0 test mode.
Reproduction: buy SQA Daily Stripe Plan (product 68271) with 4242 4242 4242 4242; open the subscription in My Account; click Update payment method; in the Stripe portal add card 4000 0000 0000 0341 as default; return to the subscription.
Expected: the new default card is verified and bound to the subscription (_gateway_payment_method_id becomes the new pm_), and the next renewal uses it.
Actual: Stripe shows Visa 0341 as the customer's default (invoice_settings.default_payment_method = pm_1USCekJG5OzSNVs2zsEjOcs6), but ArraySubs keeps pm_1USCcyJG5OzSNVs2dlaGkRoG (4242). The webhook log shows three refusals: "Refused to bind Stripe payment method pm_1USCekJG5OzSNVs2zsEjOcs6 to subscription #68418: renewal_payment_method_binding_changed". The customer page still says "Visa ending in 4242". No error is shown to the customer.
Cause (code reading, verified against the log): StripeWebhookEventsTrait::handlePaymentMethodUpdated() (arraysubs/src/Features/AutomaticPayments/Gateways/Stripe/Traits/StripeWebhookEventsTrait.php ~448) calls verifyAutomaticRenewalPaymentMethod($subscription_id, ['payment_method_id' => <new pm>]). That verifier (StripePaymentMethodsTrait.php ~380-392) re-reads the stored gateway meta and returns renewal_payment_method_binding_changed whenever the stored pm differs from the one being verified. For a webhook that introduces a new card the stored pm is always the old one, so every genuine change is refused; only a re-attach of the already-stored pm passes (which is why checkout binding works).
Proof: qa/stripe-dev-support-2026-10-18/evidence/s03-portal-bind-refused-log.txt; snapshots/sub-68418-s03-after-portal-update.json; screenshots s03-decline-portal-after-add.png (portal: 0341 Default) and s03-portal-card-not-bound.png (My Account: 4242).
Scope notes / counterexamples: initial checkout binding (same pm as stored) succeeds for 68281/68304/68418. customer.updated is processed with subscription 0, so it does not bind either. Impact: customers who replace an expiring/failing card through the only exposed "Update payment method" path keep being charged on the old card, and a failed renewal cannot be recovered by updating the card there. Blocks the S03/S05 "replace payment method" paths for this run.
Repro video: N/A.

Impact confirmed 2026-10-19 09:22Z: renewal invoice 68443 for subscription 68418 (action 100899) was charged on the old card pm_1USCcyJG5OzSNVs2dlaGkRoG (pi_3USCkHJG5OzSNVs215qwirPA succeeded) although the customer's Stripe default is pm_1USCekJG5OzSNVs2zsEjOcs6 (0341).

Deep re-verification 2026-10-19: CONFIRMED WITH CORRECTIONS (high, high confidence). Re-reproduced on fresh sub 68677 (Mastercard 4444 via portal refused 10:59:00-01Z; renewal 68726 charged old Visa 4242). Corrections: verifier call is StripeWebhookEventsTrait.php:455-458; checkout binding works because StripeOrderContextBridge::capture writes the pm without verification (not 'same-pm passes'); WooCommerce My Account add + Make default is refused too (woocommerce_payment_token_added listener is never fired); customer.updated is a structural no-op (payload has no 'customer', invoice_settings.default_payment_method never read); refused REST deliveries return 500 and Stripe redelivers them (seen again 11:59-12:11Z); keep the equality check's role against cross-subscription fan-out in mind when fixing. Full report: qa/stripe-issues-56-61-deep-verification-2026-10-19.md

Closed 2026-10-19 (UTC) after fix + live verification. Changes are in the working trees and NOT committed (user request). Full record: qa/stripe-issues-56-64-fix-log.md.

Fix 2026-10-19 (uncommitted; log qa/stripe-issues-56-64-fix-log.md): `customer.updated` with a changed `invoice_settings.default_payment_method` re-reads the Stripe customer and moves to the new default (after Stripe confirms it is attached and reusable; compare-and-swap write) the subscriptions billed to the previous default, the subscription whose billing-portal visit is open (`_arraysubs_stripe_portal_requested_at`, 1 h), and subscriptions whose card Stripe no longer has attached. Adding a card alone binds nothing; an order-scoped SetupIntent binds only an unbound subscription; same-card events only refresh display meta. When the renewal is overdue and unpaid, a renewal attempt is queued about a minute later on the new card, and an open authentication PI on the old card is cancelled first. Files: core `StripeWebhookEventsTrait.php`, `StripePaymentMethodsTrait.php`, `StripeWebhooksTrait.php`, `StripeDelegate.php`, `StripeIntentResolutionTrait.php`.
Verified live 2026-10-19: T4b (My Account "Make default": 69245 -> Mastercard 4444; adding a card alone bound nothing), T4a (billing portal from 69234 -> Visa 5556), T4c (portal-scoped: 68677 -> Mastercard 3222 while another card was the Stripe default), B1 (68709 / renewal 69304: card changed while 3DS was pending -> superseded PI cancelled with 0 received, new PI succeeded once). Natural renewals on the new cards are listed in the fix log's observation table.
Review round 2 (2026-10-19 20:26-20:56): a card change no longer charges an on-hold subscription or a renewal whose payment is settling; a running renewal attempt no longer blocks the new-card attempt; a capture of an off-session charge made with the old card no longer reverts a newer card (snapshot rule + compare-and-swap rebind). Live re-test PASS: fixture 68753 / renewal 69364 (3DS pending → My Account "Make default" Visa 4242 → old PI cancelled with 0 received, new PI succeeded once).
Review round 3: the new-card attempt never replaces a retry that is already queued (pending-only lookup) and waits 5 minutes while a renewal attempt is running.
Review round 4: a new-card attempt now goes ahead of a retry queued for later (a hold could remove that retry first). Live PASS 2026-10-19 22:34 on 69202 / renewal 69436: +24 h retry replaced, Visa 4242 charged once.
