---
id: 62
title: Paying a failed Stripe renewal with an unsaved card rebinds the subscription to an unusable payment method
status: closed
priority: high
created: 2026-10-19T16:00:56.960918073+02:00
updated: 2026-10-20T00:35:28.11039214+02:00
started: 2026-10-19T20:36:21.122748638+02:00
completed: 2026-10-20T00:35:28.110391348+02:00
tags:
    - stripe
    - payment-method
    - recovery
    - staging-qa
class: standard
---

Active QA task ID and scheduled day: deep re-verification of #56-#61, round 2 gap C (2026-10-19). No board task; report: qa/stripe-issues-56-61-deep-verification-2026-10-19.md.
Plan markdown path: qa/stripe-dev-support-2026-10-18/plan.md.
Affected subscription IDs and order IDs: 68788 (renewal 68819 paid with a new card, then renewal 68965); 68770 (renewal 68822 paid with Link, see #63).
Affected WordPress user/customer: 588 sqa-dv59-before@example.com, 589 sqa-dv59-after@example.com, role customer.
Exact route: plain order-pay page from the "Payment failed" email or My Account > Pay (https://mirror-help.arrayhash.com/checkout/order-pay/68819/?pay_for_order=true&key=...), "Use a new payment method", card 4242 4242 4242 4242; browser sessions dv59-before / dv2c-cust (customer). Core 4791832 + Pro edaa401, WooCommerce Stripe Gateway 10.9.0 (Link enabled, WooCommerce Subscriptions not active).
Reproduction: let an automatic Stripe renewal decline (card 0341) so the order carries _arraysubs_charge_attempt_gateway=stripe; before the 10-minute verification sweep locks it (#59), pay it on the order-pay page with a new card; then let the next renewal run.
Expected: the recovery payment either saves the new card for off-session use and rebinds to it, or leaves the existing reusable binding untouched; automatic billing keeps working.
Actual: StripeOrderContextBridge::capture silently rebinds the subscription to the one-time PaymentMethod pm_1USEmEJG5OzSNVs2C0j7Paip (customer=null, PI setup_future_usage=null), no note. Next renewal 68965 (action 101226, 12:50:41Z) failed closed with "renewal_payment_method_binding_invalid: The stored Stripe payment method is not an exact reusable off-session match ... No charge was attempted." (no PaymentIntent, no attempt marker). The error classifies as "unknown" (not hard), so futile retry 1 of 3 was scheduled (101234) and the readable "update your payment method" note was skipped. The customer then paid 68965 with another new card (pi_3USG5BJG5OzSNVs20cFl4rQm / pm_1USG58JG5OzSNVs2lRuRO6Co, also customer=null): no rebind (marker-less order is treated as intentionally manual). My Account keeps showing "Renewals On" and "Card on File: Visa ending in 4242". Every later cycle repeats the fail-closed attempt.
Cause (code reading, verified live): StripeOrderContextBridge.php:97-114 skips only renewal orders WITHOUT the attempt marker, then persists _stripe_source_id as _gateway_payment_method_id (127, 159, 174-177) without checking Stripe attachment or reusability. Renewal order-pay never saves the card: StripeRecurringPaymentPolicy::orderRequiresReusableMethod is false for renewals (107-116), so vaulting/off-session setup filters are no-ops; Link hides the save checkbox. Verification fails at StripePaymentMethodsTrait.php:371 (remote customer empty), before ChargeAttemptGuard::beginAttempt. binding_invalid is unmapped in arraysubs_classify_payment_failure_reason.
Proof: qa/stripe-dev-support-2026-10-18/evidence/dv2-c-rebind-*.txt|json (timeline, Stripe state, notes, gateway log, mail), dv59 evidence for 68819.
Scope notes / counterexamples: needs the attempt marker (68965 was paid without one and was not rebound); not express-specific (Link variant in #63). No self-service or admin UI repair exists (portal and My Account card changes are refused by #56; Resync changes nothing). Fails closed: no double charge. Exposure today is the window before the sweep locks the order; fixing #59 makes this the normal outcome, so fix together (save/attach the card on renewal order-pay, or bind only an attached reusable PM, comparing _stripe_source_id with the order's snapshot).
Repro video: N/A.

Closed 2026-10-19 (UTC) after fix + live verification. Changes are in the working trees and NOT committed (user request). Full record: qa/stripe-issues-56-64-fix-log.md.

Fix 2026-10-19 (uncommitted; log qa/stripe-issues-56-64-fix-log.md): automatic Stripe renewal orders (`StripeRecurringPaymentPolicy::isAutomaticRenewalOrder()`) require a reusable method on the Pay page (classic and Store API), except the last installment of a fixed-length plan. The order-context bridge rebinds a renewal only to a card attached to the customer and reusable, never reverts a newer binding, and treats a Stripe read failure as retryable; otherwise it keeps the binding and adds one warning note. Files: core `StripeRecurringPaymentPolicy.php`, `StripeOrderContextBridge.php`.
Verified live 2026-10-19: T1 (68473 paid with a new 4242 card: card saved and attached, 68439 rebound to `pm_1USLi8…`); T9 (68788, still bound to the old one-time card: renewal 69261 failed as `missing_payment_method` without retry; paid with a new Mastercard -> rebound to `pm_1USM9B…`). Sub 68770 keeps the damaged Link binding from before the fix; its 2026-10-20 renewal is an observation point in the fix log.
Review round 2: subscriptions that never had `_payment_method_updated_at` no longer skip the capture of a new card (strtotime(" UTC") bug); rebinds use compare-and-swap and drop display details the new card lacks. Live PASS 2026-10-19 20:49: 69222 paid on its Pay page with a new 4242 card → one charge, 69202 rebound to `pm_1USNTx…`.
