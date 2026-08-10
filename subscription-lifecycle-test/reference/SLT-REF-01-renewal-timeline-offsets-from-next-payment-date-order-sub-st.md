# SLT-REF-01 Renewal timeline: offsets from _next_payment_date, order/sub statuses, invoice lead

> Code-verified reference note. Generated 2026-08-01 for the subscription-lifecycle QA run.
> Claims should carry `file:line` citations; anything marked UNVERIFIED was not confirmable in code.

# SLT-REF-01 — Renewal timeline (reference note)

All paths below are relative to `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/`.

## 0. The single most important thing: the RENEWAL SPREAD OFFSET

**Nothing on this site fires exactly at `_next_payment_date`.** Every scheduled renewal leg is shifted by a deterministic per-subscription offset.

| Item | Value | Source |
|---|---|---|
| Setting | `renewals.spread_window_hours`, default **6** | `arraysubs/src/functions/renewal-spread-helpers.php:48` |
| Stored on this site? | **No** — key absent from `arraysubs_settings`, so the default 6 applies (verified: `wp eval 'arraysubs_get_setting("renewals.spread_window_hours",6)' --allow-root` → `6`) | live check |
| Window cap | `min(configured_hours, floor(cycle_hours*3600/4))` — 25% of the billing cycle | `renewal-spread-helpers.php:63-70` |
| Effective window | For any cycle ≥ 1 day this evaluates to **21600 s (6 h)** (day cycle = 24h → 24*3600/4 = 21600) | `renewal-spread-helpers.php:66-69` + `RenewalScheduler::getBillingCycleInHours()` `arraysubs/src/Features/RecurringBilling/Services/RenewalScheduler.php:222-235` |
| Offset formula | `offset = ((int) sprintf('%u', crc32('arraysubs-spread-' . $subscription_id))) % $window`, filterable via `arraysubs_renewal_spread_offset`, clamped to ≥ 0 | `renewal-spread-helpers.php:88-108` |

Offsets are **positive-only and stable forever** for a given subscription ID. Verifiable outside WP:

```bash
php -r 'foreach([100,101,1234,5000] as $id){$h=(int)sprintf("%u",crc32("arraysubs-spread-".$id));printf("id=%d offset=%ds (%s)\n",$id,$h%21600,gmdate("H:i:s",$h%21600));}'
# id=100 offset=16068s (04:27:48)
# id=101 offset=21522s (05:58:42)
# id=1234 offset=3957s  (01:05:57)
# id=5000 offset=20566s (05:42:46)
```

`_next_payment_date` itself is **never** shifted by the spread — only the Action Scheduler execution timestamps.

## 1. Timeline for one healthy cycle

Let `D` = `_next_payment_date` (UTC MySQL string on the `arraysubs_data` post), `k` = spread offset (seconds), `L` = invoice lead = **6 h** here.

| Moment | What happens | Code |
|---|---|---|
| `D + k − L` = **D + k − 6h** | `arraysubs_generate_renewal_invoice` [`$subscription_id`] fires | scheduled at `RenewalScheduler::scheduleInvoice()` `RenewalScheduler.php:135-156`; timestamp from `getInvoiceTimestamp()` `RenewalScheduler.php:165-188` |
| ↳ | Handler re-checks: post status ∈ {`arraysubs-active`,`arraysubs-trial`}, no existing pending renewal order, `shouldGenerateInvoiceNow()`, filter `arraysubs_should_generate_renewal_invoice` | `arraysubs/src/Features/RecurringBilling/Services/Hooks.php:1177-1215` |
| ↳ | `RenewalProcessor::createRenewalInvoice()` → `OrderCreation::createRenewalOrder()` creates a WooCommerce order with `status = pending` | `RenewalProcessor.php:34-131`, `OrderCreation.php:88-90` |
| ↳ | Order meta stamped: `_is_renewal_order=yes`, `_subscription_id`, `_subscription_renewal`, `_renewal_cycle_number`, `_renewal_scheduled_date = D` (the *logical* due date, the anchor for the next cycle) | `OrderCreation.php:210-219` |
| ↳ | Subscription meta `_pending_renewal_order_id` set; **subscription status is NOT changed** (stays active/trial through the whole grace period) | `RenewalProcessor.php:114-122` |
| ↳ | `do_action('arraysubs_renewal_invoice_created', $sub_id, $order_id)` | `RenewalProcessor.php:125` |
| `D + k` | `arraysubs_process_renewal` [`$subscription_id`] fires | scheduled in `RenewalScheduler::schedule()` `RenewalScheduler.php:45-66` |
| ↳ | `RenewalProcessor::process()` gates: not externally billed, status ∈ {active,trial,on-hold}, not waiting-cancellation, **`isRenewalDue()` requires `strtotime(_next_payment_date) <= time()`** | `RenewalProcessor.php:271-304`, `isRenewalDue()` at `:530-542` |
| ↳ | Picks up the pending order (statuses pending/on-hold/failed, newest first); if none exists it creates one inline under the invoice lock | `RenewalProcessor.php:306-344`, `getPendingRenewalOrder()` `:822-874` |
| ↳ | If `_payment_retry_attempts > 0`: runs `arraysubs_pre_retry_charge_verification` first (never re-charges an already-charged cycle) | `RenewalProcessor.php:360-393` |
| ↳ | `PaymentProcessor::processPayment()` → zero-total short-circuit, else `arraysubs_is_automatic_payment` → `arraysubs_process_automatic_renewal_payment`, else manual fallback | `arraysubs/src/Features/RecurringBilling/Services/PaymentProcessor.php:32-109` |
| on success | `$order->payment_complete()` → order goes `processing`/`completed` → `arraysubs_order_paid` fires | `arraysubs/src/Features/Subscriptions/Services/OrderIntegration.php:543-553` |
| ↳ | `OrderIntegration::activateSubscriptionsFromOrder()` (under per-order lock `HOOK_ORDER_PAID_PROCESSING`) → `processRenewalPayment()` | `OrderIntegration.php:883-994`, `:1150` |
| ↳ | `_last_payment_date` set; `_completed_payments` incremented **only when order total > 0**; `_pending_renewal_order_id` and `_on_hold_date` deleted; all retry meta cleared | `OrderIntegration.php:1153-1188` |
| ↳ | Next `_next_payment_date` computed from `_renewal_scheduled_date` (**not** payment time) | `getRenewalScheduleBaseDate()` `OrderIntegration.php:1629-1652` → `calculateAndSetNextPaymentDate()` `:1472-1526` |
| ↳ | `RenewalScheduler::schedule()` re-queues both legs for the new date — **but only if the new date is in the future** | `scheduleSubscriptionRenewal()` `OrderIntegration.php:1732-1748` |
| ↳ | Status forced to `arraysubs-active` if it was on-hold/trial | `OrderIntegration.php:1216-1233` |
| ↳ | `do_action('arraysubs_renewal_payment_complete')` → payment-successful email + renewal reminder rescheduled | `OrderIntegration.php:1236`; `EmailManager::on_renewal_payment_complete()` `arraysubs/src/Features/Emails/Services/EmailManager.php:524-546` |

## 2. How `invoice_before_due` = 6 hours is actually applied

`arraysubs_get_invoice_hours_before_due()` converts value+unit → hours (`value*24` when unit = `days`): `arraysubs/src/functions/settings-helpers.php:735-745`. Site value: `6` + `hours` → **6**.

Three places consume it, and they must agree or the sweep front-runs the scheduled leg:

1. **`RenewalScheduler::getInvoiceTimestamp()`** (`RenewalScheduler.php:165-188`)
   - `lead = 6`
   - **Clamp**: if `cycle_hours > 0 && lead >= cycle_hours` → `lead = 6` (a no-op at this site's setting, but it means a lead configured in *days* on a daily plan silently reverts to 6 h).
   - `invoice_ts = (D + k) − lead*3600`
   - **Guard**: `if (invoice_ts <= time() && D+k > time()) return time() + 60;` — i.e. when you time-travel a due date to "6 h from now or less", the invoice job is queued for **now + 1 minute**, not in the past.
2. **`Hooks::shouldGenerateInvoiceNow()`** (`RecurringBilling/Services/Hooks.php:1097-1124`) — same clamp (`configured_hours >= cycle_hours → 6`), returns true when `_next_payment_date <= now + effective_hours*3600` (past-due included).
3. **Hourly recovery sweep** `doGenerateUpcomingRenewalsBatch()` (`Hooks.php:389-495`) — SQL cutoff is `now + 6h`, then a **spread-aware skip**: `if (now < due_ts + offset − effective_hours*3600) continue;` (`Hooks.php:446-459`).

## 3. Status matrix

| Object | Value at each stage |
|---|---|
| Renewal WC order | `pending` (created) → `processing`/`completed` (paid) or `failed` (charge declined, `RenewalProcessor.php:594-597`) or `cancelled` (subscription cancelled for non-payment, `RenewalProcessor.php:258`) |
| Subscription | `arraysubs-active` throughout invoice + first grace window → `arraysubs-on-hold` (sweep phase 1) → `arraysubs-cancelled` (sweep phase 2). Registered slugs: `arraysubs-pending`, `arraysubs-active`, `arraysubs-on-hold`, `arraysubs-cancelled`, `arraysubs-expired`, `arraysubs-trial` — `arraysubs/src/Features/Subscriptions/Services/SubscriptionCPT.php:94-156` |

> There is **no `arraysubs-paused` post status.** Pause is meta-driven only.

## 4. Stripe caveat that changes the observed timeline

For an automatic-payment subscription with auto-renew on, `EmailManager::on_renewal_invoice_created()` **returns without sending the renewal-invoice email** (`EmailManager.php:495-513`). So on Stripe the customer sees nothing at `D+k−6h`; the first mail is the payment-successful (or payment-failed) email at `D+k`. On bacs/cheque/cod or `_auto_renew='off'`, the invoice email *does* go out at `D+k−6h`.
