# SLT-REF-02 Action Scheduler hook catalog: names, args, groups, recurrence, force-run commands

> Code-verified reference note. Generated 2026-08-01 for the subscription-lifecycle QA run.
> Claims should carry `file:line` citations; anything marked UNVERIFIED was not confirmable in code.

# SLT-REF-02 — Action Scheduler hooks (reference note)

Canonical definitions: `arraysubs/src/Supports/ActionScheduler.php`. Hook constants `:62-226`, group constants `:235-260`, hook→group map `getGroupForHook()` `:1272-1301`, hook→post-meta map `getMetaKey()` `:1244-1264`.

## Groups

| Constant | Value | Line |
|---|---|---|
| `GROUP_RENEWALS` | `arraysubs-renewals` | `:235` |
| `GROUP_BILLING` | `arraysubs-billing` | `:240` |
| `GROUP_EMAILS` | `arraysubs-emails` | `:245` |
| `GROUP_STATUS` | `arraysubs-status` | `:250` |
| `GROUP_MAINTENANCE` | `arraysubs-maintenance` | `:255` |
| `GROUP_GATEWAY` | `arraysubs-gateway` | `:260` |

## Live hooks (something actually schedules them)

| Hook name | Args shape | Group | Recurrence / who schedules | Handler |
|---|---|---|---|---|
| `arraysubs_process_renewal` | `[$subscription_id]` | `arraysubs-renewals` | single, at `due + spread_offset`; also re-queued at `now+2min` on invoice-lock contention (`RenewalProcessor.php:321-326`) and at `now + retry interval` on failure (`RenewalProcessor.php:680-685`) | `RecurringBilling\Services\Hooks::processRenewal()` `Hooks.php:63,1155-1169` |
| `arraysubs_generate_renewal_invoice` | `[$subscription_id]` | `arraysubs-billing` | single, at `due + offset − 6h` (`RenewalScheduler.php:150-156`) | `Hooks::generateRenewalInvoice()` `Hooks.php:77,1177-1215` |
| `arraysubs_generate_upcoming_renewals` | recurring parent: `[]`; chained batches: `[$cursor]` | `arraysubs-billing` | **recurring, every HOUR**, first run `now+60s` (`Hooks.php:130-136`); each full batch re-enqueues itself at `now+10s` with the last ID (`Hooks.php:361-367`) | `Hooks::generateUpcomingRenewals()` `Hooks.php:74,337-374` |
| `arraysubs_check_overdue_renewals` | recurring parent: `[]`; chained: `[$phase, $cursor]` where phase ∈ 1..3 | `arraysubs-billing` | **recurring, every HOUR**, first run `now+120s` (`Hooks.php:139-145`). Parent (phase 1, cursor 0) also kicks phase 2 at `now+5s` and phase 3 at `now+10s` (`Hooks.php:533-546`) | `Hooks::checkOverdueRenewals()` `Hooks.php:81,518-582` |
| `arraysubs_process_trial_conversions` | recurring parent: `[]`; chained: `[$cursor]` | `arraysubs-billing` | **recurring, DAILY at 2am site time** (`strtotime('tomorrow 2am')`, `Hooks.php:121-127`) | `Hooks::processTrialConversions()` `Hooks.php:69,1261-1293` |
| `arraysubs_daily_maintenance_run` | `[]` | `arraysubs-maintenance` | **recurring, DAILY at 3am** (`Hooks.php:148-154`) | `Hooks::dailyMaintenance()` `Hooks.php:92,179-201` |
| `arraysubs_cancel_subscription` | `[$subscription_id]` | `arraysubs-status` | single, at the scheduled end-of-period cancel time (`arraysubs/src/functions/cancellation-helpers.php:165,198,371`) | `Hooks::handleScheduledCancellation()` `Hooks.php:89,214-245` |
| `arraysubs_expire_subscription` | `[$subscription_id]` | `arraysubs-status` | single, from Fixed Period Membership (`arraysubspro/src/Features/FixedPeriodMembership/Services/Hooks.php:871,939,948`) | `Hooks::expireSubscription()` `Hooks.php:66,1225-1252` |
| `arraysubs_resume_subscription` | `[$subscription_id]` | `arraysubs-status` | single, from retention-pause / SkipRenewal pause (`CancellationFlow/Services/Hooks.php:330,400`; `SkipRenewal/Services/PauseManager.php:266,388`) | two handlers: `CancellationFlow\Services\Hooks::handleScheduledResume()` `:51` and `SkipRenewal\Services\Hooks::handleAutoResume()` `:41` |
| `arraysubs_process_skipped_cycle` | `[$subscription_id]` | `arraysubs-billing` | single (`SkipRenewal/Services/Hooks.php:199`) | `SkipRenewal\Services\Hooks::handleSkippedCycle()` `:42` |
| `arraysubs_send_renewal_reminder` | `[$subscription_id, $days_before]` | `arraysubs-emails` | single, at `due − days_before + spread_offset` (`EmailManager.php:787-792`); re-queued `now+60..180s` when the per-minute budget is spent (`EmailManager.php:829-835`) | `EmailManager::send_renewal_reminder()` `EmailManager.php:123,802-842` |
| `arraysubs_respread_renewals` | `[$cursor]` | `arraysubs-maintenance` | single chain, kickoff at `now+10s`, batches at `now+15s` (`RenewalRespreader.php:61-73,127-133`) | `RenewalRespreader::handleBatch()` `:48,106-140` |
| `arraysubs_backfill_retention_logs` | `[$offset]` | `arraysubs-maintenance` | single chain (`RetentionAnalytics/Services/Hooks.php:65,109,193`) | same file `:124` |
| `arraysubs_expire_store_credits` | `[]` | `arraysubs-maintenance` | **recurring, DAILY at 3am** — **only when `store_credit.expiration_days > 0`** (`arraysubspro/src/Features/StoreCredit/Services/CreditExpiration.php:58-78`) | `CreditExpiration::processExpiredCredits()` `:45` |
| `arraysubs_send_credit_expiring` | `[$customer_id, $credit_log_id]` | `arraysubs-emails` | single at `now+60s`, queued by the daily credit sweep (`CreditExpiration.php:261-266`) | `CreditExpiration::sendExpiringNotification()` `:48` |
| `arraysubs_cleanup_webhook_events` | `[]` | `arraysubs-gateway` | **recurring, DAILY at 2:00am** (`arraysubspro/src/Features/AutomaticPayments/Services/Hooks.php:1254-1260`) | same file `:95` |
| `arraysubs_gateway_reconcile` | `[]` | `arraysubs-gateway` | **recurring, EVERY 6 HOURS**, first run `now+1h` (`AutomaticPayments/Services/Hooks.php:1265-1271`) | `Hooks::reconcileGateways()` `:96,1307-1322` |
| `arraysubs_prune_job_logs` | `[]` | `arraysubs-maintenance` | single, chained from `arraysubs_daily_maintenance` (`arraysubspro/src/Features/Audits/Services/JobLogPruner.php:105,133`) | `:55` |

## Lock-only namespaces (NEVER scheduled — do not look for them in the queue)

| Constant | Value | Args | Purpose |
|---|---|---|---|
| `HOOK_SUBSCRIPTION_MUTATION` | `arraysubs_subscription_mutation` | `[$subscription_id]` | `ActionScheduler.php:182`; used by `arraysubs_acquire_subscription_mutation_lock()` `subscription-helpers.php:58,76` |
| `HOOK_RENEWAL_INVOICE_CREATION` | `arraysubs_renewal_invoice_creation` | `[$subscription_id]` | `ActionScheduler.php:194` |
| `HOOK_ORDER_PAID_PROCESSING` | `arraysubs_order_paid_processing` | `[$order_id]` | `ActionScheduler.php:206` |

Canonical lock ordering (documented `RenewalProcessor.php:83-85`, `EarlyRenewManager.php:243-248`): **mutation → order-paid → invoice-creation.** Lock TTL = `LOCK_EXPIRATION = 600 s` (`ActionScheduler.php:279`), deliberately below the AS failure period filtered to 900 s in `arraysubs/src/Supports/ActionSchedulerTuning.php`.

## DEAD hooks — declared, mapped, but nothing schedules them (see SLT-REF-10)

| Hook | Status |
|---|---|
| `arraysubs_process_trial_conversion` (singular) | constant `:68`, group-mapped `:1276`, meta-mapped `:1249`, swept on teardown `:620` — **no scheduler, no handler** |
| `arraysubs_hold_subscription` | constant `:104` — **no scheduler, no handler.** `unscheduleAllForSubscription()` deliberately omits it (`:626-628`) |
| `arraysubs_cleanup_old_data` | constant `:134` — **no scheduler, no handler** |
| `arraysubs_send_expiring_soon` | constant `:128`, **handler exists** (`EmailManager.php:125,892-917`) but **nothing ever schedules it** |
| `arraysubs_send_payment_failed` | constant `:122`, **handler exists** (`EmailManager.php:124,881-885`) but **nothing ever schedules it** (failure emails travel via the `arraysubs_gateway_payment_failed` action instead) |

## Execution safety for this QA suite

This install has no `wp action-scheduler list` command. Read queue rows with the direct, read-only SQL queries in the task files and with Tools -> Scheduled Actions.

Hook-wide and group-wide runner commands are intentionally omitted: they can claim unrelated SLT and non-SLT work and are forbidden by the suite isolation contract. When a task explicitly authorizes manual execution, record the target row and its args first, then use **Run** in Tools -> Scheduled Actions for one exact action ID at a time. Renewal tests run the target invoice ID first and the target charge ID second. Re-snapshot after every state change.

`wp action-scheduler status --allow-root` remains a read-only runner-status check; it does not list action rows.

## Post-meta pointers written when a per-subscription action is scheduled

`ActionScheduler::getMetaKey()` `:1246-1257` — useful for asserting a leg exists without the admin UI:

`_renewal_action_id`, `_renewal_invoice_action_id`, `_trial_conversion_action_id`, `_cancel_action_id`, `_hold_action_id`, `_expire_action_id`, `_renewal_reminder_action_id`, `_expiring_soon_action_id`, `_resume_subscription_action_id`, `_skip_cycle_action_id`.
