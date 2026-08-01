# SLT-REF-07 Early renew and late renew: date/amount effects, catch-up vs slip

> Code-verified reference note. Generated 2026-08-01 for the subscription-lifecycle QA run.
> Claims should carry `file:line` citations; anything marked UNVERIFIED was not confirmable in code.

# SLT-REF-07 — Early renew and late renew (reference note)

## PART A — EARLY RENEW (pro)

Code: `arraysubspro/src/Features/EarlyRenew/Services/EarlyRenewManager.php` (+ `PortalHooks.php`, `REST/EarlyRenewController.php`).

### Store gate

`isEnabled()` `:57-61` → `arraysubs_is_early_renew_allowed()` → `customer_actions.allow_early_renew`, **default false and currently FALSE on this site** (`arraysubs/src/functions/settings-helpers.php:843-846`). Any early-renew test must first flip it on.

### Eligibility — `getEligibility()` `:80-204`

| Blocked reason | Condition | Line |
|---|---|---|
| `disabled` | store setting off | `:97-99` |
| `status` | post status ≠ `arraysubs-active` (**trials are deliberately excluded**, `ALLOWED_STATUSES` `:43`) | `:107-109` |
| `lifetime` | `arraysubs_is_lifetime_subscription()` | `:111-113` |
| `pending_cancellation` | `_waiting_cancellation` set | `:115-120` |
| `skip_scheduled` | `_original_next_payment_date` non-empty (a skip is pending) | `:124-129` |
| `no_due_date` | `_next_payment_date` empty / unparsable | `:131-141` |
| `already_due` | `strtotime(_next_payment_date.' UTC') <= time()` — **you cannot early-renew something already due** | `:143-148` |
| `length_reached` | `_subscription_length > 0 && _completed_payments >= length` | `:152-157` |
| `invoice_pending` | an open renewal order already exists | `:159-164` |
| `gateway_unsupported` | `resolvePaymentMode()` returns `''` | `:166-173` |

### Gateway gating — `resolvePaymentMode()` `:330-365`

- Manual-payment subscription → `'manual'` (`:335`)
- Automatic → gateway must declare `supportsSubscriptionCapability('early_renewal')`; otherwise `''` (`:344-346`)
  - **Stripe: `early_renewal => true`** (`arraysubspro/.../Stripe/StripeDelegate.php:87`)
  - **Paddle: `early_renewal => false`** (`.../Paddle/PaddleGateway.php:113`, with the reasoning inline at `:109-112`: Paddle owns `next_billed_at`, an off-cycle transaction would not move it, so the customer would be double-billed)
  - Abstract default is `false` (`Abstracts/AbstractArraySubsGateway.php:79`)
- `_auto_renew = 'off'` on an automatic sub → mode `'manual'` (invoice, no auto-charge) (`:348-353`)
- Filter `arraysubspro_early_renew_payment_mode` (`:364`)

### What the operation does — `process()` `:213-322`

1. Takes the **subscription-mutation lock**; re-evaluates eligibility inside it (`:215-234`).
2. `RenewalProcessor::createRenewalInvoice($id)` — the normal core path, so `OrderCreation` stamps **`_renewal_scheduled_date` = the CURRENT `_next_payment_date`** (i.e. the original future due date), and `arraysubs_renewal_invoice_created` fires so recurring coupons / store credit / checkout-builder fields all apply (`:238-249`; stamp at `arraysubs/src/Features/RecurringBilling/Services/OrderCreation.php:215-219`).
3. Flags the order: `_arraysubs_early_renewal = 'yes'` (`ORDER_FLAG_META` `:50`), `_arraysubs_early_renewal_requested_at`, `_arraysubs_early_renewal_requested_by`, plus an order note naming the original due date (`:270-280`).
4. `do_action('arraysubspro_early_renewal_started', $sub_id, $order_id, $user_id)` (`:289`), then re-reads the order because those hooks may have changed totals (`:292`).
5. `PaymentProcessor::processPayment()` — same processor as a scheduled renewal (`:294`).
6. On failure → `abandonInvoice()` and HTTP 402 (`:297-305`). On success/`requires_action` → `buildResponse()` + `arraysubspro_early_renewal_processed` (`:307-318`).

### Effect on amount

`getEligibility()` previews `(new OrderCreation())->calculateRenewalTotal($subscription_id)` (`:180`) — **the ordinary full renewal amount. There is no early-payment discount and no proration.** The order itself is built by the same `OrderCreation::createRenewalOrder()` used by the scheduler, so retention discounts / store credit / recurring coupons apply exactly as they would on the scheduled cycle.

### Effect on the next payment date

Because the order carries `_renewal_scheduled_date = D_original`, `OrderIntegration::getRenewalScheduleBaseDate()` prefers that meta (`arraysubs/src/Features/Subscriptions/Services/OrderIntegration.php:1637-1643`) and `calculateAndSetNextPaymentDate()` advances **one full billing cycle from `D_original`, not from the payment moment** (`:1472-1526`). Paying 5 days early therefore does **not** shorten the paid-through period: `_next_payment_date` becomes `D_original + 1 cycle` exactly, and `getEligibility()` previews that same value via `arraysubs_calculate_next_payment_date($next_payment_date, $interval, $period)` (`:178`).

Synced subscriptions take precedence: `getSyncedFirstRenewalBaseDate()` runs first (`OrderIntegration.php:1631-1635`, impl `:1669-1722`) and derives the base from `_renewal_sync_first_full_renewal_date` advanced by `(_renewal_cycle_number − 2)` intervals — deliberately immune to a time-travelled `_renewal_scheduled_date`.

### Stale scheduled legs after an early renewal

`processRenewalPayment()` calls `scheduleSubscriptionRenewal()` which re-queues both legs via `RenewalScheduler::schedule()` — and `scheduleSingle()` unschedules the old ones first (`ActionScheduler.php:334`). So the pre-existing `arraysubs_process_renewal` at the old `D_original + k` is replaced. **But see the `<= time()` guard in Part B.**

## PART B — LATE RENEW (an action that runs after its due date)

### Does the charge still happen?

Yes. `RenewalProcessor::isRenewalDue()` only requires `_next_payment_date <= now` (`RenewalProcessor.php:530-542`) — there is **no upper bound**, no "too late" cutoff. A `arraysubs_process_renewal` action draining hours or days late still charges.

### Does the schedule catch up or slip?

**It catches up — the schedule is anchored, not payment-time-relative.** Three anchors, in priority order (`OrderIntegration::getRenewalScheduleBaseDate()` `:1629-1652`):

1. `getSyncedFirstRenewalBaseDate()` for `_renewal_sync_enabled = 'yes'` subs (`:1631-1635`)
2. the order's **`_renewal_scheduled_date`** (`:1637-1643`) — the logical due date stamped at invoice creation
3. the subscription's current `_next_payment_date` (`:1645-1649`)
4. `current_time('mysql', true)` as a last resort (`:1651`)

So a renewal due `2026-08-05 00:00` that is charged on `2026-08-08` still produces `_next_payment_date = 2026-09-05 00:00` — **no drift**. The `RenewalScheduler` docblock states this explicitly (`RenewalScheduler.php:49-57`).

### The one place lateness DOES matter — the missing re-schedule

```php
// OrderIntegration::scheduleSubscriptionRenewal() :1732-1748
$timestamp = strtotime($next_payment_date);
if ($timestamp <= time()) { return; }      // :1742-1744  <-- NO legs queued
RenewalScheduler::schedule($subscription_id, $timestamp);
```

If a renewal runs so late (or a date is time-travelled so far) that the **newly computed** `_next_payment_date` is already in the past, **no Action Scheduler legs are created at all**. The subscription then depends entirely on the hourly `arraysubs_generate_upcoming_renewals` + `arraysubs_check_overdue_renewals` sweeps. This is the single most common cause of "the second renewal never fired" in time-travel testing.

Same guard exists on cancellation-undo: `Hooks::rescheduleRenewalsOnUndo()` returns when `next_payment_ts <= time()` (`RecurringBilling/Services/Hooks.php:307-310`).

### Synced subscriptions: skipped-boundary catch-up

When sync is on and the computed boundary lands in the past, `advanceSyncedBoundaryPastDue()` walks it forward one interval at a time (max **600** advances) until it is in the future, and writes a subscription note naming how many billing dates passed unbilled (`OrderIntegration.php:1549-1620`). **Skipped cycles are NOT billed** — they are recorded and forfeited.

### Sweep-side lateness handling

| Sweep | Late-friendly behaviour |
|---|---|
| `generateUpcomingRenewals` | SQL is `_next_payment_date <= now + 6h`, so **past-due rows are always in the set** (`Hooks.php:397,411`). Spread-aware skip only blocks rows whose spread invoice moment has not arrived yet (`:446-459`). |
| `checkOverdueRenewals` phase 1 | If no unpaid renewal order exists, it **creates the invoice and skips the hold on that pass** — giving a late subscriber a fresh grace window (`Hooks.php:673-678`). |
| `RenewalRespreader` | **Deliberately skips past-due rows** (`RenewalRespreader.php:176,193-195`) — re-spreading would only delay an already-late renewal further. |

### Interaction with skip / pause

`SkipManager::skip()` rewrites `_next_payment_date` and stores the original in `_original_next_payment_date` (`arraysubs/src/Features/SkipRenewal/Services/SkipManager.php:136-156`); `undoSkip()` restores it (`:203-208`). A pending `_original_next_payment_date` **blocks early renew** (`EarlyRenewManager.php:124-129`) and blocks the respreader from re-anchoring while a future retry is promised (`RenewalRespreader.php:201-207`).

