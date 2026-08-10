# SLT-REF-10 Known landmines: fragile, order-dependent, and time-travel-hostile code with file:line

> Code-verified reference note. Generated 2026-08-01 for the subscription-lifecycle QA run.
> Claims should carry `file:line` citations; anything marked UNVERIFIED was not confirmable in code.

# SLT-REF-10 — Known landmines (reference note)

Ordered by how likely each one is to poison a test result. Paths relative to `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/`.

## L1 — The renewal spread offset makes "fires at the due date" false

`arraysubs/src/functions/renewal-spread-helpers.php:88-108` + `RenewalScheduler.php:58,142`. `renewals.spread_window_hours` is **absent from the stored settings**, so the default **6 h** applies (verified live). Every subscription's charge leg is queued at `due + crc32('arraysubs-spread-<ID>') % 21600`. Any assertion of the form "the renewal ran within N minutes of `_next_payment_date`" will fail for most subscription IDs. **Always compute the offset first** (formula and a runnable PHP one-liner are in SLT-REF-01 §0), or set `renewals.spread_window_hours = 0` for the test window — but note that saving that setting triggers `RenewalRespreader::kickoff()` and rewrites the queued legs of **every live subscription on the shared site** (`RenewalRespreader.php:83-98`). Prefer computing the offset.

## L2 — Moving `_next_payment_date` does NOT move the queued Action Scheduler rows

Nothing observes meta writes. `wp action-scheduler run` only claims actions whose `scheduled_date_gmt <= now`, and `--force` bypasses the *runner* guard, not the due filter. Editing only the meta produces **no** renewal at all.

Correct suite procedure — rewrite the meta, re-queue only the target, and park both rows far enough in the future that the minute cron cannot race the browser:

```bash
cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public
wp eval '
$id = SUBID;
$due = gmdate("Y-m-d H:i:s", time() - HOUR_IN_SECONDS);
update_post_meta($id, "_next_payment_date", $due);
\ArraySubs\Features\RecurringBilling\Services\RenewalScheduler::unschedule($id);
\ArraySubs\Features\RecurringBilling\Services\RenewalScheduler::schedule($id, time() + 12 * HOUR_IN_SECONDS);
printf("invoice_id=%s renewal_id=%s\n", get_post_meta($id, "_renewal_invoice_action_id", true), get_post_meta($id, "_renewal_action_id", true));
' --allow-root
```

The stored `$due` is past so `RenewalProcessor::isRenewalDue()` accepts the target. The scheduler timestamp is deliberately future, preventing cron from claiming either row before evidence is captured. Query the two exact IDs, then use **Run** in Tools -> Scheduled Actions for the invoice ID first and the renewal ID second, re-snapshotting before each click. Hook/group drains are forbidden.

## L3 — `scheduleSubscriptionRenewal()` silently refuses past dates

`arraysubs/src/Features/Subscriptions/Services/OrderIntegration.php:1742-1744`:
```php
if ($timestamp <= time()) { return; }
```
After a paid renewal, if the newly computed `_next_payment_date` is still in the past (very common when you back-date a due date by more than one cycle), **no legs are queued for the next cycle**. The subscription only recovers via the hourly sweeps. Same guard on cancellation-undo at `RecurringBilling/Services/Hooks.php:307-310`. Symptom: "cycle 2 renewed, cycle 3 never did."

## L4 — Two divergent "is this email enabled" maps

`EmailManager::is_email_enabled()` (`arraysubs/src/Features/Emails/Services/EmailManager.php:711-740`) and `arraysubs_is_email_enabled()` (`arraysubs/src/functions/email-helpers.php:50-80`) contain **different key sets**, and both return `true` for unmapped ids. Turning `emails.trial_started.enabled` or `emails.trial_converted.enabled` off in ArraySubs settings has **no effect** on the emails those names describe. The only universally effective switch is the WooCommerce per-email checkbox, honoured inside `BaseSubscriptionEmail::trigger()` at `Abstracts/BaseSubscriptionEmail.php:136`.

## L5 — Dead scheduled hooks with live handlers

`arraysubs_send_expiring_soon` (`EmailManager.php:125,892-917`) and `arraysubs_send_payment_failed` (`:124,881-885`) both have registered handlers but **nothing schedules them** (exhaustive grep; `ActionScheduler.php:626-628` even documents the former). Also fully dead: `arraysubs_process_trial_conversion` (singular), `arraysubs_hold_subscription`, `arraysubs_cleanup_old_data`. Any test asserting an automatic expiring-soon email will fail by design.

## L6 — `arraysubs_send_plan_switch_email` has no listener

`arraysubs/src/Features/PlanSwitching/Services/Hooks.php:601` fires it; **no `add_action` exists anywhere**. Manual plan switches produce **zero customer email**. Do not write a plan-switch email test without flagging it as an expected-fail.

## L7 — Proration orders are never auto-charged, even on Stripe

`PlanManager::attemptAutoPayment()` only adds a note (`arraysubs/src/Features/PlanSwitching/Services/PlanManager.php:164-169`). The switch REST endpoint returns `requires_payment: true` + `checkout_url` and **does not switch the plan** until that order is paid (`REST/SwitchController.php:713-730`). Expect a manual pay step in every immediate-proration switch test.

## L8 — Sweep phase 1 does not put a subscription on hold on its first eligible pass

`RecurringBilling/Services/Hooks.php:673-678`: when no unpaid renewal order exists, the sweep **creates the invoice and `continue`s**. The on-hold transition therefore needs at least two natural hourly sweep passes. Phase 2 and phase 3 are queued only by phase 1 cursor 0 (`:533-546`). In this suite, observe those natural passes; do not replace them with a hook/group drain.

## L9 — Chain-overlap transients suppress repeated sweep kickoffs for 10 minutes

`generateUpcomingRenewals()` `:351-357`, `checkOverdueRenewals()` `:549-555`, `processTrialConversions()` `:1269-1275`, `RenewalRespreader::handleBatch()` `:117-123` all bail at cursor 0 when `get_transient($flag) > time() - 600`. Back-to-back invocations inside 10 minutes can be silent no-ops. Do not delete these global transients during the shared-site QA window; wait for the natural chain and record its action IDs/timestamps.

## L10 — Per-subscription execution locks last 10 minutes

`ActionScheduler::LOCK_EXPIRATION = 600` (`arraysubs/src/Supports/ActionScheduler.php:279`). A crashed or killed drain leaves `arraysubs_action_lock_<sha1>` transients that block that subscription's renewal actions for up to 10 minutes; the handlers return silently (`Hooks.php:1158-1160`, `RenewalProcessor.php:86-88`). If a rerun "does nothing," suspect a stale lock: `ActionScheduler::cleanupExpiredLocks()` runs only in the 3am daily maintenance (`Hooks.php:186`), or call `ActionScheduler::forceReleaseLock($hook, $args)` (`:945`).

## L11 — Per-cycle email dedupe meta blocks re-sends

| Meta | Blocks | Line |
|---|---|---|
| `_arraysubs_renewal_reminder_sent_for` (`"{$next_payment}\|{$days}"`) | renewal reminder | `EmailManager.php:816-820` |
| `_arraysubs_expiring_soon_sent_for` | expiring-soon | `:907-911` |
| `_arraysubs_trial_started_email_sent` | trial started | `:976-982` |
| `_arraysubs_pending_cancel_email_sent_for` (`_cancellation_scheduled_date`) | pending-cancel notices | `:422-441` |
| order meta `_arraysubs_renewal_invoice_email_sent` | renewal invoice | `:555-573` |
| order meta `_arraysubs_renewal_payment_success_email_sent` | payment successful | `:530-539` |

Re-running the same cycle after a successful send produces **no mail**. Delete the meta or move the date.

## L12 — Vestigial grace constants that do not match the settings

`RecurringBilling/Services/Hooks.php:42,49,55` declare `INVOICE_HOURS_BEFORE_DUE = 6`, `GRACE_DAYS_BEFORE_ON_HOLD = 3`, `GRACE_DAYS_BEFORE_CANCEL = 7`. **No code reads them** — the settings helpers are used everywhere. Do not derive expected timings from the constants (they say 3/7; the site runs 1/3).

## L13 — The invoice lead clamps to 6 h whenever `lead >= cycle`

`RenewalScheduler::getInvoiceTimestamp()` `:177-179` and `Hooks::shouldGenerateInvoiceNow()` `:1112-1116` both silently reset the lead to **6 hours** when the configured lead is ≥ the billing cycle. Setting `invoice_before_due_value = 2, unit = days` on a **daily** plan (cycle 24 h) therefore yields 6 h, not 48 h. Cycle lengths use the nominal table `day=24, week=168, month=720, year=8760` hours (`RenewalScheduler.php:227-234`).

## L14 — Paddle overwrites the core-computed next payment date, without rescheduling

`PaddleGateway.php:1379-1384` calls `markOrderPaid()` (which triggers the whole core fan-out including `_next_payment_date` recomputation and `RenewalScheduler::schedule()`) and **then** `syncNextPaymentDate()` (`:2289-2306`) rewrites `_next_payment_date` from Paddle's `next_billed_at` **without touching Action Scheduler**. The queued legs can therefore point at a different moment than the stored date until the next hourly sweep. Never assert AS-timestamp/meta agreement on a Paddle subscription.

## L15 — Paddle writes `_last_payment_failure` as a Unix timestamp

`PaddleGateway.php:1435`: `update_post_meta($subscription_id, '_last_payment_failure', time());` — everywhere else this meta is a UTC MySQL string (`RenewalProcessor.php:580`, `handleFailure()` `:733`). Anything that renders or compares this value (admin timeline, `can_retry_payment` gating) will see `1785000000` instead of a date on Paddle failures. **Genuine bug candidate.**

## L16 — Stripe `requires_action` never enters dunning

`RenewalProcessor::process()` treats `requires_action` as a success-ish state (`:404-406` → `handleManualPaymentPending()` `:552-567`, returns `true`). No `failed` order status, no `payment_failed` email, no retry, `_payment_retry_attempts` stays 0 — but the grace clock still cancels the subscription at ≈ D+4 days. Expect exactly one email (`renewal_requires_verification`) for the whole SCA sequence.

## L17 — Docs contradict code on Stripe retry configuration

`documentations/architecture/payment-retry-system.md` documents `retry_enabled` / `retry_max_attempts` / `retry_interval_hours` Stripe settings fields. **They do not exist** (`grep -rn` over both plugins returns nothing). `StripeDelegate::getRetryConfig()` is hardcoded `true / 3 / DAY_IN_SECONDS` (`:476-483`). Trust the code.

## L18 — `arraysubs_get_payment_retry_config()` floors the interval at 1 hour

`arraysubs/src/functions/gateway-helpers.php:202`: `interval_seconds = max(HOUR_IN_SECONDS, …)`. Any filter that tries to shorten retries for testing cannot go below 3600 s.

## L19 — Retry actions are not spread; renewal legs are

`scheduleNextRetry()` calls `ActionScheduler::scheduleSingle()` directly with `time() + interval` (`RenewalProcessor.php:678-685`), while the charge/invoice/reminder legs go through `RenewalScheduler` and pick up the crc32 offset. Retry N therefore lands at `first_failure_time + N*24h`, **not** at `D + k + N*24h`. Mixing the two mental models produces off-by-`k` expectations.

## L20 — Plan switching deletes `_end_date`

`PlanManager::updateSubscriptionProduct()` `:253-257` deletes `_end_date`, `_cancellation_reason`, `_cancellation_reason_details`, `_schedule_end`, `_retention_offer_accepted` on **every** switch. A fixed-period membership silently loses its end date; `RenewalReminderEmail`'s "expiring" context also stops applying (`RenewalReminderEmail.php:126-129`).

## L21 — Proration credit is measured from `_last_payment_date`, which may be empty

`ProrationCalculator::getDaysUsed()` `:437-447` returns **0** for an empty `_last_payment_date`, so `days_remaining = cycle_days` and the customer is credited a whole unused cycle. Programmatically created QA subscriptions frequently lack this meta.

## L22 — The renewal reminder never fires for a trial subscription

`EmailManager::send_renewal_reminder()` `:806` requires `post_status === 'arraysubs-active'` exactly, yet `RenewalReminderEmail` has a full trial-copy branch (`:141-146`) and `emails.trial_ending.days_before` exists as a setting. A trial subscription therefore gets **no** pre-expiry reminder from the scheduled pipeline. Bug candidate (see SLT-REF-04 B3).

## L23 — Renewal-invoice email is suppressed for Stripe

`EmailManager::on_renewal_invoice_created()` `:504-510` returns early for automatic-payment subscriptions with auto-renew on. Any "invoice email 6 hours before due" test must use bacs/cheque/cod, or set `_auto_renew = 'off'`.

## L24 — Shared-site collision hazards

Several mechanisms are global, not per-subscription:
- saving `renewals.spread_window_hours` / `invoice_before_due_value` / `invoice_before_due_unit` kicks a **site-wide** respread chain (`RenewalRespreader.php:89-97`)
- the hourly sweeps are cursor-paged at `renewals.sweep_batch_size` (default **50**, `Hooks.php:399-400,627-629`) starting from the **lowest post ID** — with 354 existing subscriptions, a newly created (high-ID) test subscription is reached only after several chained batches
- the chain-overlap transients in L9 are global
- `arraysubs_renewal_spread_migrated` is already set to `1.8.11` (verified `wp option get arraysubs_renewal_spread_migrated --allow-root`), so the one-time upgrade respread (`Hooks.php:160-167`) will not re-run; deleting it would kick a site-wide respread

## L25 — Timezone handling is mixed but currently safe

Some call sites parse dates as `strtotime($date)` (`RenewalProcessor::isRenewalDue()` `:538`, `ProrationCalculator::getDaysUsed()` `:444`), others as `strtotime($date . ' UTC')` (`Hooks.php:447,663`, `EarlyRenewManager.php:137`). These agree **only because WordPress forces `date_default_timezone_set('UTC')`**. Do not "fix" a test by relying on the bare form, and never change the PHP process timezone. Sync boundaries, by contrast, are computed in **site time (UTC+6)** and converted to UTC for storage (`renewal-sync-helpers.php:245,253,385-392`) — a monthly boundary of "1st 00:00 site" is stored as `…-<last day> 18:00:00` UTC. Expect that 6-hour shift in every stored synced date.
