# SLT-REF-05 Reminder scheduling: renewal-upcoming, trial-ending, expiring-soon days_before math

> Code-verified reference note. Generated 2026-08-01 for the subscription-lifecycle QA run.
> Claims should carry `file:line` citations; anything marked UNVERIFIED was not confirmable in code.

# SLT-REF-05 — Reminder / days_before scheduling (reference note)

## 1. Renewal-upcoming reminder (`emails.renewal_upcoming.days_before` = 3)

**This is the only `days_before` value that is actually scheduled on this site.**

`EmailManager::scheduleRenewalReminder(int $subscription_id)` — `arraysubs/src/Features/Emails/Services/EmailManager.php:760-794`

```php
$days_before  = (int) arraysubs_get_setting('emails.renewal_upcoming.days_before', 3);   // :762
$next_payment = get_post_meta($subscription_id, '_next_payment_date', true);             // :763
if (empty($next_payment) || $days_before <= 0) return;                                    // :765-767
$reminder_time  = strtotime("-{$days_before} days", strtotime($next_payment));            // :770
$reminder_time += arraysubs_get_renewal_spread_offset($subscription_id);                  // :776  <-- SAME crc32 offset
if ($reminder_time > time()) {                                                            // :779
    ActionScheduler::unscheduleAll(HOOK_SEND_RENEWAL_REMINDER, [$id,$days], GROUP_EMAILS); // :781-785
    ActionScheduler::scheduleSingle(HOOK_SEND_RENEWAL_REMINDER, $reminder_time,
                                    [$id, $days_before], GROUP_EMAILS);                    // :787-792
}
```

**Fire moment = `_next_payment_date − 3 days + spread_offset`.** Never scheduled when that moment is already in the past — this is the #1 reason a time-travelled reminder test produces nothing.

### Every place that (re)schedules it

| Trigger | Line |
|---|---|
| status → `arraysubs-active` with `initial_payment` context | `EmailManager.php:333` |
| status → `arraysubs-active` from pending/trial/auto-draft | `:343` |
| `arraysubs_data_created` while status is active | `:467-484` |
| renewal payment complete | `:545` |
| trial converted | `:1002` |
| subscription reactivated | `:1018` |
| respread sweep (settings change / upgrade migration) | `RenewalRespreader.php:232-234` |

Unscheduled by `RenewalScheduler::unschedule()` `:103-107` (uses `unscheduleAllForSubscriptionArg` because the args are 2-element).

### Send-time guards — `EmailManager::send_renewal_reminder()` `:802-842`

1. Subscription must still be exactly `arraysubs-active` (`:806`) — **a trial subscription will NOT get the reminder from this handler**, even though the email has trial copy.
2. `_next_payment_date` must be non-empty (`:810-814`).
3. Per-cycle dedupe key `sent_key = "{$next_payment}|{$days_before}"` compared to `_arraysubs_renewal_reminder_sent_for` (`:816-820`). **Re-running the action after a successful send is a no-op unless `_next_payment_date` changes.**
4. Rate limit: `emails.renewal_reminder_rate_limit` (default **0 = unlimited**, confirmed live). When >0 and the minute bucket is full, the action **defers** to `now + 60..180 s` instead of dropping (`:828-836,855-873`).
5. On success writes `_arraysubs_renewal_reminder_sent_for` and `_arraysubs_renewal_reminder_sent_at` (`:838-841`).

### Which email actually goes out

`RenewalReminderEmail::detectReminderContext()` `:120-132`:
- post status `arraysubs-trial` → `trial` context → subject `[{site_title}] Your trial for {product_name} ends soon`, date label "Trial End Date" (`:141-146`). *(unreachable via the scheduled handler because of guard #1, but reachable if the email is triggered directly)*
- `_end_date` meta set and valid → `expiring` context → subject `…is ending soon`, label "End Date" (`:149-155`)
- otherwise → `renewal` context → `…renews soon`, label "Next Payment Date" (`:157-160`)

## 2. Trial-ending (`emails.trial_ending.days_before` = 3) — **NOT WIRED**

There is no `TrialEndingEmail` class, no template, and no scheduler. The key exists only in defaults (`arraysubs/src/functions/settings-helpers.php:246-251`) and in REST validation (`EasySetup/REST/SetupController.php:80,158`; `MainAdmin/REST/SettingsController.php:578,593`).

What customers on a trial actually receive is driven by `emails.renewal_upcoming.days_before` **only if** they are in `arraysubs-active` status at send time, which a trial is not. **Practical conclusion: a subscription sitting in `arraysubs-trial` gets NO pre-expiry reminder from the scheduled pipeline.** Treat any test asserting a trial-ending email as an expected-fail / bug ticket, and cite `EmailManager.php:806`.

## 3. Expiring-soon (`emails.expiring_soon.days_before` = 7) — **NOT WIRED**

Handler exists: `EmailManager::send_expiring_soon_email()` `:892-917`, registered on `arraysubs_send_expiring_soon` at `:125`. Its logic (if it ever ran):
- subscription must be `arraysubs-active` or `arraysubs-trial` (`:896-898`)
- `days_before` read from `emails.expiring_soon.days_before` default 7 (`:900`) — **used only for the dedupe key and the template arg, never for scheduling**
- target date = `_end_date` if valid, else `_next_payment_date` (`get_expiring_soon_target_date()` `:925-936`)
- dedupe key `"{$target_date}|{$days_before}"` in `_arraysubs_expiring_soon_sent_for` (`:907-911`)

**Nothing schedules `arraysubs_send_expiring_soon`.** Verified by exhaustive grep (`HOOK_SEND_EXPIRING_SOON` appears only at `ActionScheduler.php:128,700,1160,1254,1286` and `EmailManager.php:125`). `unscheduleAllForSubscription()` even documents the omission: *"HOOK_HOLD_SUBSCRIPTION and HOOK_SEND_EXPIRING_SOON are intentionally absent: nothing anywhere schedules them"* (`ActionScheduler.php:626-628`).

To exercise the email in a test you must schedule it by hand:

```bash
cd /home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public
wp eval '\ArraySubs\Supports\ActionScheduler::scheduleSingle(
  \ArraySubs\Supports\ActionScheduler::HOOK_SEND_EXPIRING_SOON,
  time()-60, [SUBID], \ArraySubs\Supports\ActionScheduler::GROUP_EMAILS);' --allow-root
wp action-scheduler run --hooks=arraysubs_send_expiring_soon --force --allow-root
```

## 4. Store-credit expiring (pro)

Warning window is the hardcoded constant `CreditExpiration::DAYS_BEFORE_EXPIRY_WARNING = 7` (`arraysubspro/src/Features/StoreCredit/Services/CreditExpiration.php:34`), **not** a setting. The daily `arraysubs_expire_store_credits` job (3am, only when `store_credit.expiration_days > 0`, `:58-78`) calls `scheduleExpiringNotifications()`, which queues `arraysubs_send_credit_expiring` at `now + 60 s` per credit log and stamps `_expiry_notified` (`:261-269`).

## 5. Practical cheat-sheet for reminder tests

| Want | Set `_next_payment_date` to | Then |
|---|---|---|
| reminder to fire immediately | `now + 3 days − offset − 5 minutes` … i.e. anything that makes `D − 3d + k` land in the past **at schedule time** the scheduler refuses. Instead: set `_next_payment_date` to a **future** value `> now + 3 days − k`, call `EmailManager::scheduleRenewalReminder()`, then rewrite the AS action time. | see SLT-REF-10 §time-travel |
| simplest reliable path | set `_next_payment_date = now + 3 days + k + 1 min`, run `wp eval '\ArraySubs\Features\Emails\Services\EmailManager::scheduleRenewalReminder(SUBID);'`, then `UPDATE wp_actionscheduler_actions SET scheduled_date_gmt/scheduled_date_local` back to the past, then `wp action-scheduler run --hooks=arraysubs_send_renewal_reminder --force` | — |
| re-send after a send | you must change `_next_payment_date` (the dedupe key) or delete `_arraysubs_renewal_reminder_sent_for` | `EmailManager.php:816-820` |

