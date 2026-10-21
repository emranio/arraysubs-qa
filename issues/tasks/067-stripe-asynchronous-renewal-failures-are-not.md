---
id: 67
title: 'Stripe: asynchronous renewal failures are not recorded as payment failures or retried'
status: closed
priority: medium
created: 2026-10-19T23:53:16.065010468+02:00
updated: 2026-10-21T01:33:16.562092303+02:00
started: 2026-10-20T09:17:55.075780186+02:00
completed: 2026-10-21T01:33:16.562090921+02:00
tags:
    - stripe
    - retry
    - dunning
    - sepa
    - webhook
    - staging-qa
class: standard
---

Active QA task ID and scheduled day: N/A (found by code review round 2 during the #56-#64 fix work, 2026-10-19). Fix log qa/stripe-issues-56-64-fix-log.md; review notes qa/stripe-dev-support-2026-10-18/evidence/fix/rev2-retry-settings-2.md.
Affected subscription IDs and order IDs: N/A (not reproduced live; the staging store has no delayed-settlement renewals).
Affected WordPress user/customer: N/A.
Exact route: Stripe webhook payment_intent.payment_failed for an ArraySubs off-session renewal that first went to processing (SEPA Direct Debit, ACH, BACS).
Reproduction (code reading):
1. An automatic renewal is charged off-session with a bank debit; Stripe answers processing, ArraySubs marks the order pending with a deadline (StripeRenewalPaymentTrait processing branch).
2. Days later Stripe sends payment_intent.payment_failed.
3. StripeWebhookEventsTrait::handlePaymentFailed() classifies the code (stripeFailureCode()), logs it and fires arraysubs_gateway_payment_failed. Its listeners only add notes and send the failure email; nothing records `_last_payment_failure` / `_last_payment_failure_category` or calls the retry scheduler.
Expected: the failure is recorded like a synchronous decline: category stored, retry scheduled by the store schedule (or not, for hard declines), next-retry time shown.
Actual: no retry; the renewal waits for the customer or the grace period.
Proof: code references above.
Scope notes: synchronous card renewals are recorded and retried in RenewalProcessor (verified live 2026-10-19, T2 and the round-2 F8 test).

[[2026-10-21]] Wed 01:33

Closed 2026-10-20 (UTC) after fix + live verification. Changes are in the working tree and NOT committed (user request). Full record: qa/issues-65-69-fix-log.md; investigation qa/fix-65-69/evidence/inv-67-async-failures.md.

Fix 2026-10-20 (uncommitted): new `StripeAsyncFailureTrait::recordAsynchronousRenewalFailure()` called from `handlePaymentFailed()`: only the renewal attempt's own PaymentIntent (ArraySubs metadata, attempt result), re-read from Stripe, under `HOOK_SUBSCRIPTION_MUTATION`; superseded and already recorded attempts are ignored; WooCommerce Stripe's failed `py_`/`ch_` transaction id, `_stripe_charge_id` and `_arraysubs_pending_deadline` are compare-deleted; then `RenewalProcessor::recordProviderPaymentFailure()` (category, notes, retry ladder, failure events, emails). Core: `handlePaymentAttemptFailure()` writes `_arraysubs_recorded_failure_attempt` per provider attempt and `recordProviderPaymentFailure()` returns `duplicate` for it (Mollie's caller handles it), refuses orders not pending/on-hold/failed, a non-current invoice and externally billed subscriptions. Lock busy → the official metadata retry worker re-runs the event (EVENT_PAYMENT_FAILED added). Processing deadlines per method (card 2, ACH/BECS 7, BACS/ACSS 10, SEPA 21 days); the reconcile sweep asks `reconcileUnsettledRenewal()` (extend a still-processing debit up to 30 days, record a failure whose webhook was lost). Classifier: bank-debit codes (closed/no account/frozen/restricted → invalid_card hard; not authorized/blocked/disputed/refer → card_declined hard; limits → generic_decline; generic_could_not_process → processing_error); `stripeFailureCode()` prefers a known top-level code over `decline_code=generic_decline` (Stripe's ACH shape); neutral reason wording. Also found and fixed: renewal PIs with a cached bank-debit mandate sent `payment_method_options[<type>][mandate]`, which Stripe rejects (`parameter_unknown`, verified with a test request that created nothing); now only the top-level `mandate`.
Verified live 2026-10-20: fixture customer 598 / sub 69756 bound to a test ACH method (account 000222222227) with mandate. Renewal 69774 → ArraySubs PI with the mandate → processing → on-hold `py_…`, deadline +7 days → Stripe failure → recorded once (category, "Scheduled retry 1 of 3", attempts 1, transaction id + deadline cleared, order failed, one customer email, one WC "Failed order"; duplicate event on the second endpoint skipped). Retry 1 on the same order → new PI → failed → recorded as `insufficient_funds`, attempts 2, retry 2 queued. Sync decline webhook (#65's PI3) → `duplicate`, attempts not increased.
Review rounds 1-3 (uncommitted, probes; details in the fix log): a `duplicate` from a conclusive sweep releases the leftover deadline and re-queues the used-up retry at its planned time (L3, live on 69693). Retry Payment reports `pending` ("not complete yet…"), `requires_action` (wording for the customer or the admin), `already_processing` or `not_attempted` (not due, still settling, not chargeable) with HTTP 409 instead of "successful" or a stale decline; the customer portal, the admin subscription page and Renewal Failures show them as information; Billing-context recovery re-arms the schedule and does not count them as declines; Pro no longer logs a 409 Retry Payment as a failed portal action; the manual-retry note no longer shows as "Renewal Payment Successful" in the payment timeline (R2-2/R2-3/R3-2/R3-8). After this run's own decline a stop (hard decline, limit, retries off) withdraws a renewal attempt still queued unless it is for a payment method chosen since; a soft decline replaces a queued recheck with the configured interval; the limit note waits while the last planned retry is still queued (R3-1).
Review round 4 (uncommitted, rolled-back probes; details in the fix log): a later-reported failure of the renewal attempt's own payment (a Stripe bank debit returned days later, Mollie) now gets the same stop handling as an in-run decline, so a hard decline, the used-up limit or retries off withdraw the attempt still queued for that method (R4-1); an attempt queued for a card saved while a failing attempt ran survives a stop even when that attempt stopped before charging (R4-2); billing-context recovery no longer re-arms a charge after its retry's decline stopped the retries (R4-3); a queued attempt that finds the renewal or the subscription busy is re-queued two minutes later instead of being used up (R4-4); the manual-retry counter restore no longer overwrites a failure recorded meanwhile (R4-10).
Review round 5 (uncommitted, rolled-back probes; details in the fix log): a Mollie payment that fails after it was submitted no longer stays as the order's transaction id, so Retry Payment and the next retry charge the same renewal order instead of creating a second one; the reconcile sweep clears an unconfirmed payment's transaction id at the deadline the same way (R5-1). Before the due date a stop keeps the cycle's own charge leg, so a Pro early renewal whose bank debit fails later still renews on its due date (R5-8). A kept attempt for a newly chosen method no longer triggers the retry-limit note and exhausted event (R5-2); the failed attempt's method is taken from the order only when this run recorded it (R5-3); a busy re-queue is skipped for on-hold subscriptions and ends without charging when the lock holder stopped the retries meanwhile (R5-4/R5-9); a manual retry no longer writes the retry counter (N3); stop notes say when a queued attempt still runs (N4); an early run that finds the renewal lock busy goes straight back to its due moment (N7); Mollie records the mandate it actually used on the order (N2).

Review rounds 6-8: the busy re-queue mark applies only to the attempt it marked and the stop record only to its due date (R6-C11); Mollie records a failure whose webhook was lost at the next sweep (R6-C6/C9); the gateway reports the payment method it verified, so a card bound mid-run is judged correctly (C1); a Stripe payment refunded after it was taken for an unpaid renewal no longer completes that renewal for free, and refunding it never cancels the subscription.
