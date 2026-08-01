# SLT-REF-03 Failed renewal + retry: schedule, attempt count, grace-day timestamps, terminal state

> Code-verified reference note. Generated 2026-08-01 for the subscription-lifecycle QA run.
> Claims should carry `file:line` citations; anything marked UNVERIFIED was not confirmable in code.

# SLT-REF-03 — Failed renewal, retries, grace, cancellation (reference note)

## 1. Retry configuration — what the code actually does

`arraysubs_get_payment_retry_config(int $subscription_id)` — `arraysubs/src/functions/gateway-helpers.php:176-204`

```php
$defaults = ['enabled' => true, 'max_attempts' => 3, 'interval_seconds' => DAY_IN_SECONDS];
$config   = apply_filters('arraysubs_payment_retry_config', $defaults, $subscription_id);
// normalized: enabled = (bool), max_attempts = max(0,(int)), interval_seconds = max(HOUR_IN_SECONDS,(int))
```

| Gateway | Published config | Source |
|---|---|---|
| **Stripe** (`stripe`) | `enabled=true, max_attempts=3, interval_seconds=86400` — **hardcoded**, not admin-settable | `arraysubspro/.../Gateways/Stripe/StripeDelegate.php:476-483`, published via `publishRetryConfigForSubscription()` `:510-514`, filter registered `:118` |
| **Paddle** (`arraysubs_paddle`) | Registers **no** `arraysubs_payment_retry_config` filter (grep across `arraysubspro/src` returns only Stripe `:118` and Mollie `:158`). Therefore the **core defaults apply** if the local pipeline ever fails a Paddle charge — but see §5: the local pipeline never returns `failed` for Paddle. | `Gateways/Paddle/PaddleGateway.php` (no `getRetryConfig()` override; abstract default is `enabled=false,0,0` at `Abstracts/AbstractArraySubsGateway.php:666-673`, but it is never published) |
| Manual gateways (bacs/cheque/cod) | Never reach the failure path — `PaymentProcessor` returns `manual_required` (`PaymentProcessor.php:100-108`) which `RenewalProcessor::process()` treats as a *non-failure* (`RenewalProcessor.php:404-406`) | — |

> **DOC-vs-CODE CONFLICT (candidate bug).** `documentations/architecture/payment-retry-system.md` lines ~30-38 document admin fields `retry_enabled` / `retry_max_attempts` / `retry_interval_hours` on the Stripe settings page. **Those field keys do not exist anywhere in the codebase** (`grep -rn 'retry_enabled\|retry_max_attempts\|retry_interval_hours' arraysubs/src arraysubspro/src` → no matches). Stripe retry behaviour is not configurable through the UI. Do not write a test that flips those settings.

## 2. Retry scheduling algorithm

`RenewalProcessor::scheduleNextRetry()` — `arraysubs/src/Features/RecurringBilling/Services/RenewalProcessor.php:644-706`

```
if (!$config['enabled'])              -> subscription note, NO retry scheduled          (:649-658)
if ($attempts >= $config['max'])      -> "reached retry limit" note, NO retry scheduled (:661-675)
else:
   next_attempt_at = time() + interval_seconds                                          (:678)
   ActionScheduler::scheduleSingle(HOOK_PROCESS_RENEWAL, next_attempt_at,
                                   [$subscription_id], GROUP_RENEWALS)                   (:680-685)
   _payment_retry_attempts        = attempts + 1                                        (:687)
   _payment_retry_last_attempt_at = now (UTC mysql)                                     (:688)
   _payment_retry_next_attempt_at = gmdate of next_attempt_at                           (:689)
   subscription note event_type = 'retry_scheduled'                                     (:691-705)
```

**Retries are NOT spread** — `scheduleNextRetry()` calls `scheduleSingle()` directly with `time()+interval`, bypassing `RenewalScheduler::schedule()`. Only the invoice/charge/reminder legs get the crc32 offset (`renewal-spread-helpers.php:26-30`).

Callers: `handlePaymentAttemptFailure()` `:618` (normal gateway decline — **does not change subscription status**) and `handleFailure()` `:740` (order could not be created — **does force `arraysubs-on-hold` at `:727-730`**).

## 3. What holds what between attempts

| Object | State between retries |
|---|---|
| Renewal WC order | `failed` — `$order->update_status('failed', ...)` at `RenewalProcessor.php:594-597`. The SAME order is reused: `getPendingRenewalOrder()` queries statuses `pending, on-hold, failed` (`:831`). |
| Subscription post status | **unchanged** by the retry itself. It stays `arraysubs-active` until the hourly overdue sweep moves it. `process()` explicitly accepts `arraysubs-on-hold` too (`:287`) so retries keep firing after the hold. |
| `_next_payment_date` | **unchanged** (still the original due date) — this is what drives the grace-window SQL. |
| Failure meta | `_last_payment_failure`, `_last_payment_failure_reason`, `_last_payment_failure_category` (`:580-592`); `_renewal_failure_resolved*` deleted. |

All of it is cleared by `arraysubs_reset_payment_retry_attempts()` (`gateway-helpers.php:231-242`), called on success at `RenewalProcessor.php:400` and `OrderIntegration.php:1186-1188`.

## 4. Grace math mapped to real timestamps (this site: on_hold=1, cancel=3)

Helpers: `arraysubs_get_grace_days_before_on_hold()` `settings-helpers.php:714-717` (default 3 in code, **1 on this site**); `arraysubs_get_grace_days_before_cancel()` `:724-727` (default 7 in code, **3 on this site**).

> The class constants `Hooks::GRACE_DAYS_BEFORE_ON_HOLD = 3` and `GRACE_DAYS_BEFORE_CANCEL = 7` (`RecurringBilling/Services/Hooks.php:49,55`) are **vestigial** — no code reads them. Only the settings helpers are used.

### Phase 1 — ACTIVE/TRIAL → ON-HOLD (`processActiveToOnHoldBatch()` `Hooks.php:639-705`)

- SQL cutoff `on_hold_cutoff = gmdate(now − 1 day)`; selects `post_status IN (active,trial) AND _next_payment_date < cutoff AND _billing_period != 'lifetime'` (`:645-648`, query at `:596-618`).
- **Spread guard**: `if (time() < due_ts + offset + HOUR_IN_SECONDS) continue;` (`:661-670`).
- **Requires an unpaid renewal order to already exist.** If `hasUnpaidRenewalOrder()` is false the sweep *creates the invoice and `continue`s* — the subscription is **not** put on hold on that pass (`:673-678`).
- Settlement guard: `arraysubs_is_awaiting_gateway_settlement()` → skip (`:684-689`).
- On hold: `wp_update_post(status=arraysubs-on-hold)`, `_on_hold_date = now` (UTC mysql), `do_action('arraysubs_data_put_on_hold')` (`:692-701`).

**Effective on-hold moment** = first hourly `arraysubs_check_overdue_renewals` tick where `now > max(D + 1 day, D + offset + 1h)`. With offset ≤ 6 h, `D + 1 day` dominates. So: **≈ D + 24h, rounded up to the next hourly sweep.**

### Phase 2 — ON-HOLD → CANCELLED (`processOnHoldToCancelBatch()` `Hooks.php:715-760`)

- SQL cutoff `cancel_cutoff = gmdate(now − (1 + 3) = 4 days)`; selects `post_status = arraysubs-on-hold AND _next_payment_date < cutoff` (`:718-726`).
- **Additional** per-row gate on `_on_hold_date`: skip while `time() < on_hold_ts + 3*DAY_IN_SECONDS` (`:735-743`).
- Settlement guard again (`:747-752`).
- `RenewalProcessor::cancelOverdueSubscription()` (`:756`, impl `RenewalProcessor.php:139-174`): status → `arraysubs-cancelled`; `_end_date`, `_cancelled_date` = now; `_cancelled_by = 'system'`; `_cancellation_reason = 'overdue_payment'`; cancels every unpaid renewal order (`cancelPendingRenewalOrders()` `:182-263`); `RenewalScheduler::unschedule()`; `do_action('arraysubs_data_cancelled_overdue')`.

**Effective cancel moment** = `max(D + 4 days, on_hold_date + 3 days)`, rounded up to the next hourly sweep. Because on-hold lands at ≈ D+24h, both terms coincide at **≈ D + 4 days**.

### Phase 3 — end-of-period cancellations (`processWaitingCancellationBatch()` `Hooks.php:770-818`)

Unrelated to dunning: finalizes `_waiting_cancellation=1` subscriptions whose `_cancellation_scheduled_date <= now`. Recovery net for the exact-time `arraysubs_cancel_subscription` handler (`Hooks.php:214-245`); both take the same per-subscription lock so they cannot double-finalize.

## 5. Worked timeline — Stripe card `4000 0000 0000 0341` (attaches, declines every off-session charge)

`D` = `_next_payment_date`, `k` = spread offset (0…6 h).

| Real time | Event | Sub status | Order status | `_payment_retry_attempts` |
|---|---|---|---|---|
| `D + k − 6h` | invoice order created (`pending`) | active | pending | 0 |
| `D + k` | attempt #0 → decline → order `failed`, retry #1 queued for `D+k+24h` | **active** | failed | 1 |
| next hourly sweep after `D+24h` | phase 1 → **on-hold**, `_on_hold_date` written | on-hold | failed | 1 |
| `D + k + 24h` | attempt #1 → decline → retry #2 queued | on-hold | failed | 2 |
| `D + k + 48h` | attempt #2 → decline → retry #3 queued | on-hold | failed | 3 |
| `D + k + 72h` | attempt #3 → decline → `attempts(3) >= max(3)` → **note only, no further retry** | on-hold | failed | 3 |
| first sweep after `D + 96h` (and ≥ on_hold+72h) | phase 2 → **cancelled**, unpaid order → `cancelled`, all AS legs unscheduled | cancelled | cancelled | 3 (cleared only on a later success) |

Total automatic charge attempts against the gateway: **4** (initial + 3 retries).

## 6. Emails per failed attempt

`handlePaymentAttemptFailure()` fires **both** `arraysubs_renewal_payment_failed` (`:620`) and, when `_payment_gateway` is non-empty, `arraysubs_gateway_payment_failed` (`:625-632`).

`EmailManager::on_renewal_payment_failed()` **returns immediately when a gateway slug is set** (`EmailManager.php:630-638`) so the pair is not double-sent; `on_gateway_payment_failed()` (`:661-676`) → `on_payment_failed()` (`:614-618`) sends **`payment_failed`** (customer) + **`admin_payment_failed`** (admin). One pair per attempt → **4 pairs** across the sequence above.

Plus: `subscription_on_hold` at the hold transition (`EmailManager.php:370-372`) and `subscription_cancelled` + `admin_subscription_cancelled` at the cancel transition (`:374-381`).

There is **no deduplication** on the failure emails — 4 identical-subject customer emails is expected behaviour, not a bug.

## 7. Manual retry

`RenewalProcessor::manualRetry()` `:425-522`. Rejects on missing subscription, `waiting_cancellation`, no open renewal order, or `HOOK_PROCESS_RENEWAL` lock busy (`already_processing`). Forces `_payment_retry_attempts >= 1` so the pre-retry gateway verification always runs (`:473-476`). REST endpoints: `POST /wp-json/arraysubs/v1/subscriptions/{id}/retry-payment` (admin) and `POST /wp-json/arraysubs/v1/my-subscriptions/{id}/retry-payment` (customer).

**Manual retry does NOT increment past the cap and does NOT reschedule an automatic retry beyond the cap** — it calls `process()` which, on failure, re-enters `scheduleNextRetry()` and hits the `attempts >= max` branch.

