# Review round 3 (read-only, one agent): round-2 patches for Stripe fixes #56-#64

Finished 2026-10-19 21:29 UTC. Code reading plus `php -l` (clean); no writes.
Condensed from the agent's report; file:line references are as the agent read them.

## HIGH

- **H1 (predates round 2).** One Stripe read failure during a retry ends all automatic retries.
  After a decline the PaymentIntent stays on the order (`StripeRenewalPaymentTrait.php:302`), so
  every retry first calls `resolveExistingIntent()` (`:98-100`). A retrieve error returns `pending`
  with a 2-day deadline (`StripeIntentResolutionTrait.php:125-140`), including
  `stripe_empty_response` (401) and Woo Stripe's `stripe_api_outage` (timeouts, 5xx).
  `RenewalProcessor::handleManualPaymentPending()` (`:713-770`) schedules nothing; after the
  deadline the reconciler returns early for a `failed` order (`PaymentHooksReconciliationTrait.php:392`).
  Scenario: insufficient funds on day 0, retry 1 hits a timeout, no further attempt, hold, cancel.
  Suggested fix: keep `pending` but queue a bounded follow-up `HOOK_PROCESS_RENEWAL` (+1 h).

## MEDIUM

- **M1.** `resource_missing` also covers a test/live mode or account mismatch ("No such
  PaymentMethod ... a similar object exists in live mode"), now hard `missing_payment_method`:
  an hour in test mode fails every renewal hard and tells customers to replace working cards.
  Suggested fix: hard only when the customer is readable in the same account and the message
  lacks "similar object exists".
- **M2a.** The abandoned-attempt cancel has no recency guard: a +60 s new-card attempt can cancel
  a customer's live 3DS on the Pay page (no double charge; zero-money read-back holds).
- **M2b.** A second Pay-page visit: a `requires_action` PI is not reusable, so Woo Stripe creates a
  new PI without ArraySubs metadata; if that is abandoned the stored intent classifies `invalid`
  and the order dead-ends like H1.

## LOW

- **L1.** `as_next_scheduled_action()` returns true while any action runs, even when a pending one
  also exists (`action-scheduler/functions.php:362-366`): `queueRenewalAttemptWithNewCard()` can
  replace a pending retry (one retry step lost); the next-retry helper reports `time()` during any run.
- **L2.** The +60 s attempt can be dropped: `Hooks::processRenewal()` returns on a busy lock
  (`Hooks.php:1203-1205`), `RenewalProcessor::process()` on a busy mutation lock (`:305-306`).
- **L3.** `hasSettlingRenewalPayment()` runs only when `getPendingRenewalOrder()` found nothing and
  ignores a pointer order that is paid but not yet processed (`_subscription_payment_processed`
  not yes, replay pending).
- **L4.** The hold-wait window ignores queue lateness (each retry is scheduled from when the
  previous run finished), so the last retry can still pass the bound on WP-Cron queues.

## Context (predates round 2)

Grace sweeps honour only `_arraysubs_pending_deadline`. A SEPA payment made on the Pay page and
processed by Woo Stripe (transaction id, on-hold, no deadline) is still put on hold, and on
cancellation its order is cancelled.

## Checked and correct (agent's list)

Null-response handling at all call sites; soft/hard mapping; new B1 guards (excluding on-hold);
empty-stamp fix and attempt-snapshot rule; compare-and-swap set/delete; settling-invoice guard
matches Woo Stripe `payment_intent.processing` and Mollie; hold-wait bounded; PayPal/Paddle
unaffected; M2 guard `pm_` only; payment-complete hook no-op for off-session successes;
abandoned-attempt read-back and reconcile before replacement; settings validation; no breakage
in normal success, soft-decline ladder, 3DS links, Pay-page rebind, initial checkout, plan
switches, Mollie/PayPal/Paddle shared code.
