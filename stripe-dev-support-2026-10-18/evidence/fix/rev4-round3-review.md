# Review round 4 (read-only, one agent): round-3 patches for Stripe fixes #56-#64

Code reading, `php -l` (clean) and two read-only probes. No HIGH findings.

## MEDIUM
- **M1.** `queueRenewalAttempt()` refuses whenever any `arraysubs_process_renewal` is pending, however far away. With a long schedule (24/72/168 h) and a 3-day grace, a card added at D+30 h waits behind retry 2 at ~D+96 h; GracePolicy does not wait for it (planned 96 h > 73 h), the hold at D+72 h unschedules it, and the new card is never tried. Defaults (24/24/24, 3 days) are safe.
  Related LOW: a +5 min attempt queued while a run is in progress is replaced when that run soft-declines (`scheduleNextRetry()` → `scheduleSingle()` dedupe), moving it out a full interval while the note says "about 5 minutes".
  Suggested fix: queue at time()+delay when the pending attempt is later; optionally keep the earlier attempt in `scheduleNextRetry()`.

## LOW
- **L1.** `_arraysubs_pending_deadline` is cleared only in `clearTerminalIntentForReplacementWhileLocked()`. The superseded path and reconcile-before-retry start fresh charges without clearing a deadline left by an unreadable read → sweeps skip the subscription and new-card attempts are refused for up to 2 days. Fix: delete it right before `ChargeAttemptGuard::beginAttempt()`.
- **L2.** `hasSettlingRenewalPayment()` blocks while a paid current invoice lacks `_subscription_payment_processed`; the pointer is cleared only on cancellation and only retryable processing errors are replayed, so a refunded, lifecycle-blocked order can stop billing silently. Fix: ignore refunded/cancelled pointers; log once when blocking on an old unprocessed pointer.
- **L3.** Soft `resource_missing` also covers a Stripe account migration where the customer does not exist: every renewal is a processing error, the ladder runs out, the customer is never asked to update. Consider soft only for "similar object exists".

## Checked and correct (agent's list)
Pending-only lookup and GROUP_RENEWALS everywhere; scheduleSingle dedupe cancels only pending actions; unreadable recheck bounded to a day; 30-minute deferral anchored to the first sighting; every queued attempt resolves the stored intent first (no double charge); settling guard in createRenewalInvoice and processUnderLock; lifecycle unscheduling and status gates; early-run requeue only for future dates; M2 guard leaves real off-session 3DS links alone; Woo Stripe reuse rules; GracePolicy planned-sum indexing matches scheduleNextRetry; PayPal/Paddle unaffected; logger hook timing; normal success/decline flows unchanged.
