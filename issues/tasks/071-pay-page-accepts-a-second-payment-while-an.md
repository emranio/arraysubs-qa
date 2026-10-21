---
id: 71
title: Pay page accepts a second payment while an automatic renewal debit is still in flight
status: closed
priority: medium
created: 2026-10-20T14:13:53.59959905+02:00
updated: 2026-10-21T02:09:42.479820969+02:00
started: 2026-10-20T19:27:41.310263071+02:00
completed: 2026-10-21T02:09:42.479819176+02:00
tags:
    - pay-page
    - mollie
    - stripe
    - settlement
    - double-charge
class: standard
---

Active QA task ID and scheduled day: N/A (found 2026-10-20 by review round 3 of the #65-#70 fixes; out of their scope). Fix log: qa/issues-65-69-fix-log.md (R3-13).
Affected subscription IDs and order IDs: N/A (code-level; no live reproduction yet).
Affected WordPress user/customer: N/A.
Exact route: renewal order Pay page (`/checkout/order-pay/{id}/`) and Store API `POST /wc/store/v1/checkout/{id}` for a renewal order whose automatic payment is still settling.
Code: `OrderIntegration::guardRenewalOrderNeedsPayment()` (arraysubs/src/Features/Subscriptions/Services/OrderIntegration.php:155) only checks the subscription lifecycle (`renewalLifecycleAllowsPayment()`: status + not waiting cancellation). It does not look at a payment already in flight for the order.
Reproduction (from code, to confirm live):
1. Mollie: an automatic renewal charge with a SEPA mandate returns `pending` (`MollieDelegate::buildPending()`); `RenewalProcessor::handleManualPaymentPending()` leaves the order `pending` for days.
2. Stripe: an automatic ACH/SEPA renewal returns PaymentIntent `processing`; the order stays `pending` (with `_arraysubs_pending_deadline`) until WooCommerce Stripe's `payment_intent.processing` webhook puts it on hold.
3. In that window the customer opens the renewal order's Pay page (My Account > Orders > Pay, or the failed-payment email link) and pays with a card.
Expected: the Pay page refuses (or warns) while a renewal payment for the order is settling, like the grace sweeps and `RenewalProcessor::hasSettlingRenewalPayment()` do.
Actual: WooCommerce `needs_payment()` stays true, so the card payment goes through; the bank debit settles later as well, and the renewal is paid twice.
Proof: code references above. Retry Payment now says "The renewal payment is not complete yet…", but the Pay page itself has no check.
Scope notes: a renewal waiting for 3-D Secure (`requires_action`) or the customer's own abandoned Pay-page attempt (#65) must stay payable, so the check must look at a settling payment (settlement window / processing debit), not just at `_arraysubs_pending_deadline`, which ambiguous states also set.

[[2026-10-20]] Tue 18:46
More consequences found by review round 5 of the #65–#70 fixes (R5-7, Mollie, end-to-end probe with a stubbed Mollie client, rolled back): when the customer switches the pending Mollie SEPA renewal to another method on the Pay page and does not finish (order pending, payment method stripe) or the card is declined (order failed), the renewal's own Mollie debit is then the only money taken, but it cannot complete the order (ProviderPaymentCompletion and verifyRecentChargeForOrder require a Mollie gateway on the order). In the pending variant the 21-day deadline sweep then marks the order failed ('never confirmed by the gateway') and sends 'Payment failed' to the customer and admin although Mollie took the payment (corrected by review round 6: the declined-card variant only loses the deadline silently). BACS chosen on the Pay page (on-hold) followed by the merchant confirming it gives a double payment. Since round 5 these cases get a de-duplicated order note ('Mollie took payment … but the order was not marked paid because its payment details have changed since …') instead of being dropped with a misleading log line; crediting the payment or blocking the Pay page while the debit is in flight is still open here.

[[2026-10-21]] Wed 02:09
Closed 2026-10-21 (UTC) after fix + verification. Changes are in the working tree and NOT committed (user request). Full record: qa/issues-65-69-fix-log.md (section 'Issues #71–#78' and timeline from 17:26).
Fix: new arraysubs/src/Features/AutomaticPayments/Services/RenewalPaySettlementGuard.php. The Pay page (classic form and Store API order-pay), My Account Pay/Cancel and the customer portal refuse a second payment while the renewal's own payment is settling (Mollie pending debit; ArraySubs' Stripe PaymentIntent in processing via a settling marker; WooCommerce Stripe's #66 settlement window on an on-hold order; the PayPal/Paddle provider billing window) or was already received but not applied ('payment already received, contact the store'). It stays payable for a renewal waiting for authentication, a failed or abandoned attempt, a manual invoice and after the window. Related: Mollie payments whose create response was lost are recorded; Stripe/Mollie taken-payment holds; a full refund of a taken payment detaches it so a new payment charges afresh.
Verification: fixer + adversarial verifier + repair, follow-up round, final rounds 7-8 (rolled-back probes). Live 2026-10-20 on fixture order 68903 (customer 591): put into a Stripe settling state, the Pay page showed 'A payment for this renewal is already being processed or confirmed…' with no payment methods and My Account showed only View (screenshots qa/stripe-dev-support-2026-10-18/screenshots/r7-71-*); restored, payable again.
