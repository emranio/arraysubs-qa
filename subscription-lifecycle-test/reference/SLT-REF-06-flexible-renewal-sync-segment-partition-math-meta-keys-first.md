# SLT-REF-06 Flexible Renewal Sync: segment partition math, meta keys, first charge/date, exclusivity, gateways

> Code-verified reference note. Generated 2026-08-01 for the subscription-lifecycle QA run.
> Claims should carry `file:line` citations; anything marked UNVERIFIED was not confirmable in code.

# SLT-REF-06 — Flexible Renewal Sync (reference note)

Two layers stack here:

- **Core Renewal Sync** (free): a *global* setting that snaps every new subscription's first renewal to a calendar boundary. `arraysubs/src/functions/renewal-sync-helpers.php`. Site config: `renewals.sync_to_billing_cycle = true`, `renewals.sync_first_charge_mode = "full"`.
- **Flexible Renewal Sync** (pro): a *per-product* override that splits the cycle into up to three day-segments and picks a different first-charge mode per segment. `arraysubspro/src/Features/FlexibleRenewalSync/Services/{SegmentPlan,Hooks}.php`.

## 1. Core sync: boundary and first-charge math

`arraysubs_calculate_renewal_sync_cycle_dates($start_utc, $interval, $period)` — `renewal-sync-helpers.php:235-289`. All boundary work happens in **site timezone (UTC+6)** and is converted back to UTC MySQL for storage (`:245,253,286-288`).

| Period | `cycle_start` | `next_payment` |
|---|---|---|
| `day` | today 00:00 site | `+interval days` (`:256-259`) |
| `week` | most recent `start_of_week` (option, mod 7) at 00:00 (`:262-265`) | `+interval weeks` (`:266`) |
| `month` | `first day of this month` 00:00 (`:276`) | `+interval months` (`:277`) |
| `year` | Jan 1 00:00 of the current year (`:270`) | `+interval years` (`:271`) |
| `lifetime` | both `''` (`:237-242`) | — |

If `next_payment <= start` it is pushed one more interval by `arraysubs_increment_renewal_sync_boundary()` (`:281-283`, impl `:525-541`).

`arraysubs_calculate_renewal_sync_prorated_amount()` — `:300-377`:
- `cycle_days = max(1, cycle_start->diff(next_payment)->format('%a'))` (`:333`)
- `remaining_days`: `cycle_days` if start ≤ cycle_start; `0` if start ≥ next_payment; otherwise **`max(1, days(start→next_payment) − 1)`** (`:335-342`) — note the deliberate `−1`
- `ratio = min(1, remaining/cycle_days)`; `amount = round(full * ratio, wc_get_price_decimals())`; a non-zero raw that rounds to 0 is bumped to one minor unit (`:344-351`)
- filter `arraysubs_renewal_sync_prorated_amount` (`:363-371`)

Gateway minimum bump (prorate mode only): `arraysubs_get_renewal_sync_gateway_minimum_amount()` `:417-466` — Stripe only, `BDT => 6500` minor units hardcoded, else `WC_Stripe_Helper::get_minimum_amount()` (fallback 50). Applied at `:198-210`.

### Core eligibility / exclusivity

`arraysubs_subscription_data_supports_renewal_sync()` `:102-118`:
```
sync enabled globally  AND  period != 'lifetime'  AND  trial_length <= 0
```
filterable via `arraysubs_subscription_data_supports_renewal_sync`.

### Core gateway gating — `arraysubs_is_renewal_sync_supported_gateway()` `:44-94`

| Gateway id | Supported? | Line |
|---|---|---|
| `stripe`, `arraysubs_stripe` | **YES** | `:76-77` |
| `paypal`, `paddle`, `arraysubs_paypal`, **`arraysubs_paddle`** | **NO — hard-coded false** | `:78-79` |
| any object exposing `supportsSubscriptionCapability()` | supported **only if** `supportsSubscriptionCapability('automatic_payments')` is FALSE | `:80-81` |
| `bacs, cheque, check, cod, invoice, manual, offline, bank_transfer, bank-transfer` | **YES** | `:56-72,82-83` |
| empty id **and** `$gateway === null` | **YES** — this is what makes back-end/programmatic subscription creation sync-eligible | `:52-54` |

Filter escape hatches: `arraysubs_renewal_sync_manual_gateway_ids` (`:59`), `arraysubs_renewal_sync_supported_gateway` (`:93`).

**Consequence for QA: a Paddle checkout can never produce a synced/segmented subscription.** `arraysubs_get_renewal_sync_context()` returns early with `reason = 'unsupported_gateway'` (`:167-170`), and the pro segment filter refuses to override that (`FlexibleRenewalSync/Services/Hooks.php:348-350`).

## 2. Flexible Renewal Sync — meta keys

`SegmentPlan` constants (`SegmentPlan.php:55-73`), all on the **product or variation** post:

| Constant | Meta key | Meaning |
|---|---|---|
| `META_ENABLED` | `_arraysubs_flex_sync_enabled` | `'yes'` = on |
| `META_SEG1_END` | `_arraysubs_flex_sync_seg1_end` | last day (1-based, inclusive) of the **first active** segment |
| `META_SEG2_END` | `_arraysubs_flex_sync_seg2_end` | last day of the **second active** segment — used **only when all three are active** |
| `META_SEG1_ACTIVE` | `_arraysubs_flex_sync_seg1_active` | `'yes'`/`'no'`; **anything other than the literal `'no'` counts as active**, including a missing key (`SegmentPlan.php:231`) |
| `META_SEG2_ACTIVE` | `_arraysubs_flex_sync_seg2_active` | ″ |
| `META_SEG3_ACTIVE` | `_arraysubs_flex_sync_seg3_active` | ″ |

Boundary metas are **positional, not segment-named** (`SegmentPlan.php:28-30`).

## 3. Nominal cycle length

`SegmentPlan::getNominalCycleDays($period, $interval)` `:118-125` — `PERIOD_DAYS = ['day'=>1,'week'=>7,'month'=>30,'year'=>365]` (`:85-90`) × `max(1,$interval)`. Unsupported period (incl. `lifetime`) → `0`. `MIN_CYCLE_DAYS = 3` (`:95`); a plan shorter than 3 nominal days is refused (`:224-226`).

Calendar overflow (31-day months, leap years) is **absorbed into the last active segment** — `resolveSegment()` falls through to `end($config['actives'])` for any day past the last boundary (`:344-350`).

## 4. Partition math for 1 / 2 / 3 active segments

`getPartition()` `:270-291` walks the actives, using `boundaries[$index]` for every segment except the last, whose `to` is always `cycle_days`:

| Active count | `boundaries` | Partition (1-based inclusive day ranges) |
|---|---|---|
| **3** | `[b1, b2]` from `sanitizeBoundaries()` `:153-169` | `1..b1` → 1st active seg; `b1+1..b2` → 2nd; `b2+1..cycle_days` → 3rd |
| **2** | `[b1]` from `sanitizeSingleBoundary()` `:178-187` | `1..b1` → 1st active seg; `b1+1..cycle_days` → 2nd |
| **1** | `[]` | `1..cycle_days` → that single segment |
| **0 active segments configured** | recomputed for three active segments | actives forced back to `[1,2,3]` (defensive) `:239-241` |

### Boundary defaults and clamping

`getDefaultBoundaries($cycle_days)` `:133-143` (thirds):
```
cycle_days = max(3, cycle_days)
seg1_end = max(1, round(cycle_days/3))
seg2_end = max(seg1_end+1, round(cycle_days*2/3));  seg2_end = min(seg2_end, cycle_days-1)
seg1_end = min(seg1_end, seg2_end-1)
return [max(1,seg1_end), seg2_end]
```
For a **monthly** product (`cycle_days = 30`): `seg1_end = round(10) = 10`, `seg2_end = round(20) = 20` → **days 1-10 / 11-20 / 21-30**.
For a **yearly** product (365): `122` and `243` → **1-122 / 123-243 / 244-365**.
For a **weekly** product (7): `round(7/3)=2`, `round(14/3)=5` → **1-2 / 3-5 / 6-7**.

`sanitizeBoundaries()` `:153-169` re-derives defaults when `seg1_end < 1 || seg2_end <= seg1_end || seg2_end >= cycle_days`, otherwise clamps `seg1_end ∈ [1, cycle_days-2]` and `seg2_end ∈ [seg1_end+1, cycle_days-1]`.

`sanitizeSingleBoundary($b, $cycle_days)` `:178-187`: `cycle_days = max(2, …)`; if `b < 1 || b >= cycle_days` → `max(1, round(cycle_days/2))` (monthly → **15**, so days 1-15 / 16-30).

### Day-in-cycle resolution

`getDayInCycle($start_utc, $cycle_start_utc)` `:303-324` — both converted to site TZ and floored to midnight; returns `cycle_start->diff(start)->format('%a') + 1`, or `0` when `start < cycle_start`. So **a purchase on the 1st of the month is day 1**.

`resolveSegment($day, $config)` `:338-351` — first boundary `>= day` wins, else last active segment.

## 5. First-charge amount per mode

`getSegmentMode()` `:359-369` — segment **1 → `full`**, **2 → `prorate`**, **3 → `next_cycle`** (`MODE_NEXT_CYCLE = 'next_cycle'`, `:78`).

Applied in `Hooks::filterRenewalSyncContext()` (`FlexibleRenewalSync/Services/Hooks.php:319-417`):

| Mode | `initial_unit_amount` | `proration_ratio` | `gateway_minimum_amount` | Line |
|---|---|---|---|---|
| **`full`** (seg 1) | `full_unit_amount` | `1.0` | forced `0.0` | `:389-392` |
| **`prorate`** (seg 2) | `arraysubs_calculate_renewal_sync_prorated_amount(full, cycle_start, next_payment, start)` — the same core formula, applied **even when the global `sync_first_charge_mode` is `full`** | `proration['ratio']` | recomputed via the Stripe-minimum bump (`:453-469`) | `:384-385`, `applyProratedFirstCharge()` `:432-472` |
| **`next_cycle`** (seg 3) | `full_unit_amount` (full price today) | `1.0` | forced `0.0` | `:389-392` |

## 6. First renewal date per mode

| Mode | `cycle_start_date` | `next_payment_date` | Line |
|---|---|---|---|
| `full` | unchanged (core boundary) | unchanged — the **upcoming** boundary | core `renewal-sync-helpers.php:183-184` |
| `prorate` | unchanged | unchanged — the upcoming boundary | ″ |
| `next_cycle` | **rewritten to the upcoming boundary** (`flex_covered_cycle_start` also stored) | **`SegmentPlan::pushBoundaryForward(upcoming_boundary, interval, period)`** = one further full cycle | `Hooks.php:394-414`; `pushBoundaryForward()` `SegmentPlan.php:382-404` |

`pushBoundaryForward()` converts the boundary to site TZ, applies `arraysubs_increment_renewal_sync_boundary()`, converts back to UTC (`SegmentPlan.php:388-403`). If it returns `''` the mode **falls back to `full`** rather than risking a wrong date (`Hooks.php:401-407`).

Worked monthly example, purchase **2026-08-25** (site TZ), monthly product, default thirds (1-10/11-20/21-30):
- `cycle_start = 2026-08-01 00:00 (+06)`, upcoming boundary `2026-09-01 00:00 (+06)` → stored UTC `2026-08-31 18:00:00`
- `day_in_cycle = 25` → segment **3** → mode `next_cycle`
- charge **full price today**; `cycle_start_date` ← `2026-08-31 18:00:00 UTC`; `next_payment_date` ← `2026-09-30 18:00:00 UTC` (= 2026-10-01 00:00 +06)
- Customer gets 2026-08-25 → 2026-09-01 as bonus access; cart shows the note from `appendCartItemSegmentNote()` (`Hooks.php:483-531`)

## 7. Exclusivity rules — what disables the segment plan

`SegmentPlan::getConfig()` returns `null` (feature off for that product) when **any** of:

| Condition | Line |
|---|---|
| `_arraysubs_flex_sync_enabled !== 'yes'` | `:104-106` |
| product has **Different Renewal Price**: `_enable_renewal_price === 'yes'` | `:209-211` |
| product has a **free trial**: `_trial_length > 0` | `:214-216` |
| nominal cycle `< 3` days (incl. `lifetime`, which yields 0) | `:222-226` |

Additional runtime bails in `Hooks.php`:

| Condition | Line |
|---|---|
| `subscription_data['product_id'] <= 0` | `:321-325` |
| **effective cycle length ≠ the stored plan's cycle length** (e.g. a Flexible-Subscription customer-chosen period) | `:336-343` (and mirrored in `filterSupportsRenewalSync()` `:292-299`) |
| upstream context already failed: `applies` false, or empty `cycle_start_date` / `next_payment_date` — **includes the unsupported-gateway bail** | `:348-350` |
| `day_in_cycle < 1`. If global sync is OFF (so sync only applied because of this product), the whole context is torn down: `applies=false`, `reason='flex_day_unresolved'`, amounts reset to full | `:355-369` |
| `resolveSegment()` returns `< 1` | `:373-375` |

`filterSupportsRenewalSync()` (`:262-302`) is what lets a flex-sync product be synced **even when the global `renewals.sync_to_billing_cycle` is off** — it only ever flips `false → true`, never the reverse, and still refuses `lifetime` / `trial_length > 0` (`:277-279`).

## 8. Gateway gating for the pro feature

The pro feature adds **no gateway logic of its own** — it inherits the core table in §1 verbatim, because it bails whenever `$context['applies']` is already false (`Hooks.php:348-350`) and `applies` is set false with `reason='unsupported_gateway'` for Paddle/PayPal.

| Gateway | Flexible Renewal Sync usable? |
|---|---|
| Stripe (`stripe`) | **Yes** |
| Paddle (`arraysubs_paddle`) | **No** — `renewal-sync-helpers.php:78-79` |
| bacs / cheque / cod | **Yes** |
| PayPal (`arraysubs_paypal`, out of scope) | No |

Note the checkout-time gateway resolution: `arraysubs_get_current_renewal_sync_gateway_context()` (`renewal-sync-helpers.php:473-515`) reads `$_POST['payment_method']`, then the session's `chosen_payment_method`, then **falls back to the first available gateway** on a fresh checkout. **Switching the payment method radio on the checkout page re-prices the first charge**, so a segment/proration assertion must be made after explicitly selecting the gateway.

## 9. Saved-config normalization (admin side)

`Hooks::persistFlexSyncMeta()` `:196-252`:
- feature unchecked → `META_ENABLED` deleted, but boundaries are **retained** if both were submitted `> 0` (`:198-208`)
- if no segment checkbox is ticked, **all three are forced active** (`:220-222`)
- 3 active → `sanitizeBoundaries()` or defaults when either value < 1 (`:238-243`)
- 2 active → `sanitizeSingleBoundary($seg1_end)`, and `seg2_end = max($seg2_end, 2)` (`:244-247`)
- always writes `META_SEG1_END = max(1, seg1_end)` and `META_SEG2_END = max(2, seg2_end)` (`:250-251`)

Simple products save on `woocommerce_process_product_meta` prio 15; variations on `woocommerce_save_product_variation` prio 15 (`:44-45`).
