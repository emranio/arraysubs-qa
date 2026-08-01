# SLT-REF-08 Plan switching: classification, prorate_immediately math, next payment date, emails

> Code-verified reference note. Generated 2026-08-01 for the subscription-lifecycle QA run.
> Claims should carry `file:line` citations; anything marked UNVERIFIED was not confirmable in code.

# SLT-REF-08 — Plan switching (reference note)

Site config: `plan_switching.enabled = true`, upgrades/downgrades/crossgrades all allowed, `proration_type = prorate_immediately`, `allow_customer_switch = true`, `auto_downgrade_timing = on_expire`; `proration.switch_fees.{upgrade,downgrade,crossgrade} = 0`, `proration.minimum_charge = 0`, `proration.rounding_method = round`.

Code: `arraysubs/src/Features/PlanSwitching/Services/{ProrationCalculator,PlanManager,Hooks,AutoDowngradeHandler}.php`, REST at `arraysubs/src/Features/PlanSwitching/REST/SwitchController.php`.

## 1. Classification — `ProrationCalculator::determineSwitchType()` `:345-392`

Comparison is on **normalized daily rate**, not sticker price.

```php
current_daily = current_price / getCycleDays(current_period, current_interval);
new_daily     = new_price     / getCycleDays(new_period,     new_interval);
tolerance     = current_daily * 0.05;                              // :383
if ($new_daily > $current_daily + $tolerance) return 'upgrade';    // :385-386
if ($new_daily < $current_daily - $tolerance) return 'downgrade';  // :387-388
return 'crossgrade';                                               // :391
```

- `getCycleDays()` `:417-428` — `['day'=>1,'week'=>7,'month'=>30,'year'=>365] * interval`; unknown period → 30.
- Current-side values prefer the **subscription's** actual terms over product meta: `_recurring_amount` (if > 0), `_billing_period`, `_billing_interval` (`:358-372`).
- New-side values come from product meta `_subscription_period` / `_subscription_interval` and `resolveProductPrice()` (`:352-355,348-349`).
- **The tolerance band is ±5% of the CURRENT daily rate.** e.g. $10/month (0.3333/day) → anything in `[0.31667, 0.35)` per day (≈ $9.50–$10.50/month) is a **crossgrade**, not an upgrade/downgrade. A $10/mo → $100/yr switch is `0.3333 → 0.27397` daily → **downgrade**, even though the invoice is bigger.

## 2. `prorate_immediately` math — `ProrationCalculator::calculate()` `:44-202`

Inputs:
```
cycle_days        = getCycleDays(billing_period, billing_interval)              :86
days_used         = getDaysUsed(_last_payment_date)                             :87   // (now - last_payment)/86400, round(...,2), floored at 0  :437-447
days_remaining    = max(0, cycle_days - days_used)                              :88
current_daily     = current_price / cycle_days                                  :91
credit_amount     = round(current_daily * days_remaining, 2)                    :94
is_cycle_change   = (period != new_period) || (interval != new_interval)        :97
new_cycle_days    = getCycleDays(new_period, new_interval)                      :100
new_daily         = new_price / new_cycle_days                                  :101
```

> `days_used` is measured from **`_last_payment_date`**, not from the cycle start. On a subscription that has never been renewed this is the initial-payment date; if `_last_payment_date` is empty it returns **0**, so `days_remaining = cycle_days` and the customer is credited the entire cycle (`:439-441`).

### Branch A — same billing cycle (`is_cycle_change == false`), `:159-165`
```
charge_amount         = round(new_daily * days_remaining, 2)
net_amount            = round(charge_amount - credit_amount, 2)
new_next_payment_date = $next_payment_date            // UNCHANGED
```

### Branch B — cycle change (`is_cycle_change == true`), `:154-158`
```
charge_amount         = new_price                     // FULL new plan price
net_amount            = max(0, new_price - credit_amount)
new_next_payment_date = gmdate('Y-m-d H:i:s', strtotime("+{new_interval} {new_period}"))   // FROM NOW
```

### Negative net (downgrade credit) `:167-171`
```
if (net_amount < 0) { refund_amount = abs(net_amount); net_amount = 0; }
```
The "refund" is **not** a gateway refund — `PlanManager::updateSubscriptionProduct()` routes it to `createDowngradeCredit()`, which just accumulates the `_store_credit` meta on the subscription and adds a note + `arraysubs_downgrade_credit_created` action (`PlanManager.php:247-250,279-305`).

### Switch fee `:179-186`
`getSwitchFee($switch_type)` reads `proration.switch_fees[$type]` (`:473-478`; all **0** here). When > 0 it is stored in `switch_fee` and, for non-`apply_at_renewal` modes, **added on top of `net_amount`**.

### Other proration modes (for contrast)
| Mode | credit | charge | `new_next_payment_date` | Line |
|---|---|---|---|---|
| `no_proration` | 0 | `new_price` | `now + new_interval new_period` | `:137-142` |
| `apply_at_renewal` | 0 | 0 | **unchanged**; also sets `new_renewal_price` | `:143-149` |

## 3. What happens to `_next_payment_date`

`PlanManager::updateSubscriptionProduct()` `:180-270`:
- `_product_id` / `_variation_id`, `_recurring_amount` (**per-unit**, `:204-209`), `_quantity`, `_billing_period`, `_billing_interval` all rewritten (`:196-218`)
- if `proration['new_next_payment_date']` is non-empty → `_next_payment_date` overwritten, then `RenewalScheduler::unschedule()` + `RenewalScheduler::schedule()` (`:221-232`)
- **`_end_date` is deleted** along with `_cancellation_reason`, `_cancellation_reason_details`, `_schedule_end`, `_retention_offer_accepted` (`:253-257`) — a plan switch silently clears a fixed end date
- `arraysubs_cancel_subscription`, `arraysubs_expire_subscription`, `arraysubs_resume_subscription` unscheduled (`:260-264`)
- subscription post title rewritten (`:235-245`)
- `do_action('arraysubs_plan_switch_completed', $sub_id, $old_product_id, $new_product_id, $switch_type)` (`:267`)

**Net effect at this site's settings (`prorate_immediately`):** same-cycle switch → `_next_payment_date` **unchanged**; cycle-changing switch → `_next_payment_date` = **now + one new cycle**.

## 4. Order flow — `SwitchController` `:660-780`

1. `apply_at_renewal` → stores a pending switch and returns `deferred: true`; the payload is applied only after the next renewal order is paid (`OrderIntegration::applyPendingSwitch()` `arraysubs/src/Features/Subscriptions/Services/OrderIntegration.php:1196-1199,1267+`).
2. Immediate modes (`prorate_immediately`, `no_proration`) with `net_amount > 0` → `PlanManager::createProrationOrder()` (`:702-703`).
3. **The proration order is ALWAYS manual.** `PlanManager::attemptAutoPayment()` does nothing but add the note *"Proration order awaiting manual payment from customer or admin."* (`PlanManager.php:164-169`) — **even on Stripe with a saved card there is no off-session charge.** The endpoint returns `requires_payment: true` + `checkout_url` and **stops**; the subscription is NOT switched yet (`SwitchController.php:713-730`).
4. `updateSubscriptionProduct()` runs only when no payment was required or the order was already paid (`:734-735`); otherwise it runs later from `PlanSwitching\Services\Hooks::onProrationOrderPaid()` (`Hooks.php:609+`, gated on `_arraysubs_order_type === 'plan_switch'`).

Proration order meta (`PlanManager.php:96-106`): `_subscription_id`, `_arraysubs_subscription_id`, `_arraysubs_order_type = 'plan_switch'`, `_arraysubs_switch_type`, `_arraysubs_old_product_id`, `_arraysubs_new_product_id`, `_arraysubs_proration_data` (the full array), `_arraysubs_switch_quantity`. Line items are `WC_Order_Item_Fee` — proration fee and, when configured, a separate switch fee (`:71-94`).

## 5. Which emails fire on a plan switch

| Path | Emails |
|---|---|
| Manual customer/admin switch (`prorate_immediately`) | **NONE.** `onPlanSwitchCompleted()` writes `_plan_switch_history`, adds a subscription note, and fires `do_action('arraysubs_send_plan_switch_email', …)` at `PlanSwitching/Services/Hooks.php:601` — **for which there is no listener anywhere in either plugin** (exhaustive grep). No status transition occurs either, so no lifecycle email is produced. |
| Deferred `apply_at_renewal` | The normal renewal emails for the paid renewal order (`payment_successful`), nothing switch-specific. |
| **Auto-downgrade** | `arraysubs_auto_downgrade_completed` (`AutoDowngradeHandler.php:410-415`) → `EmailManager::on_auto_downgrade_completed()` (`EmailManager.php:116,1030-1036`) → **`auto_downgrade`** customer email (`customer-auto-downgrade.php`, subject `[{site_title}] Your subscription #{subscription_id} has been changed to {new_product_name}`). Lifecycle emails around the downgrade are actively **suppressed** via `AutoDowngradeHandler::shouldSuppressLifecycleEmail()` / `isProcessing()` (`EmailManager.php:317-321,375-377,384-386,411-420`). |
| Retention downgrade offer | `arraysubs_retention_offer_accepted` only emails for `discount`/`coupon` types (`EmailManager.php:1050-1052`); a `downgrade` offer sends nothing. |

## 6. Auto-downgrade timing (`auto_downgrade_timing = on_expire`)

`AutoDowngradeHandler::shouldHandleLifecycleTransition()` `:138-175`:
- `on_expire` → only fires when the new status is exactly **`arraysubs-expired`** (`:143-147`)
- `on_cancel` → eligible cancellation transitions (`:149-153`)
- `on_trial_expire` → this lifecycle path returns **false**; the trial path is `shouldHandleTrialConversion()` (`:183-192`) invoked from `TrialConverter::convertTrialToActive()` (`arraysubs/src/Features/RecurringBilling/Services/TrialConverter.php:94-101`)
- Skipped when a retention offer was accepted (`:161-166`) or no `_arraysubs_auto_downgrade_product` target is configured on the product/variation (`:169-172`, resolver `:432+`)

**Consequence: with `on_expire`, dunning-driven cancellation (`arraysubs-cancelled`) does NOT trigger auto-downgrade.** Only a genuine expiry (`_subscription_length` reached, or a fixed end date) does.

