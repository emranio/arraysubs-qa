---
id: 32
title: SLTREC3 vs SLTREC3NOINIT on block checkout - N-cycle counting and the exact renewal where the discount stops
status: todo
priority: critical
created: 2026-08-02T03:43:05.721219012+02:00
updated: 2026-08-02T03:43:16.032664447+02:00
tags:
    - checkout
    - day-02
due: "2026-08-04"
estimate: 1h 45m
depends_on:
    - 10
    - 11
    - 12
    - 25
    - 5
    - 16
class: standard
---

> **SLT-CPN-03** · group `checkout` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Prove the N-cycle contract on two subscriptions bought the same evening: `SLTREC3` (25%, recurring, 3 cycles, **count initial = yes**) gives 2 discounted renewals then stops; `SLTREC3NOINIT` (same, **count initial = no**) gives 3. They must sit on different subscriptions - only one captured coupon is stored per subscription (`_applied_coupon_id`).

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: new registered (CREATES `slt-cpncyc`, `slt-cpncyc2`)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01/02/03/04, SLT-PROD-01, SLT-CPN-01 done.
- Two fresh accounts: both buy the same product and `auto_migrate_on_checkout=true` (C08); one coupon per account.
- Both checkouts **18:00-19:00 site (UTC+6) on 2026-08-04**, same hour so the ladders align. Satisfies C02; keeps every invoice leg (12:00-19:00 site) clear of SLT-SYN-04's D3 09:00-11:00 bracket.
- Coupon expiry must be `2026-08-14` (C13).
- Sessions `cpn03-admin`, `cpn03-a`, `cpn03-b` exclusive; A and B never share one - sessions share a cart (C09).

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core $10.00 day/1 (both) |
| Accounts | slt-cpncyc = `SLTREC3`; slt-cpncyc2 = `SLTREC3NOINIT` |
| Card | 4242 4242 4242 4242 |

| Leg | Sub A (remaining) | Sub B (remaining) |
|---|---|---|
| checkout 08-04 | $7.50, 3->**2** | $7.50, stays **3** |
| renewal #1 08-05 | $7.50 -> 1 | $7.50 -> 2 |
| renewal #2 08-06 | $7.50 -> 0 | $7.50 -> 1 |
| renewal #3 08-07 | **$10.00 full** | $7.50 -> 0 |
| renewal #4 08-08 | $10.00 | **$10.00 full** |

## Steps
1. `cpn03-admin`: create `slt-cpncyc` and `slt-cpncyc2` (Customer, **Send User Notification** unticked, `SltQa!2026#Pass`, billing per SLT-SETUP-03).
2. Open both coupons; screenshot each ArraySubs group; confirm **Count initial checkout** TICKED on SLTREC3 and UNTICKED on SLTREC3NOINIT, both cycles = 3, expiry 2026-08-14. Record coupon IDs.
3. `mailpit-agent latest-id` -> `M0`.
4. `cpn03-a`: log in as `slt-cpncyc`; assert `/cart/` EMPTY; add SLT Daily Core; at `/checkout/` expand **Add a coupon**, apply `SLTREC3`; confirm Discount **-$2.50**, Total **$7.50**; pay with Stripe; record parent order and SUB_A.
5. `cpn03-b`: same as `slt-cpncyc2` with `SLTREC3NOINIT`; confirm Total **$7.50**; record parent order and SUB_B.
6. For both: `wp post meta list <ID> --allow-root | grep _coupon`; copy the capture notes verbatim; compute `k = crc32('arraysubs-spread-'.ID) % 21600`; record invoice window `due+k-6h`, charge `due+k`.
7. **Mornings 08-06, 08-07, 08-08 and 08-09 after 09:00 site**: for each new renewal order screenshot the item table, run `wp eval '$o=wc_get_order(<REN>);foreach($o->get_fees() as $f){echo $f->get_name()."|".$f->get_total();}echo "|".$o->get_total();' --allow-root`, re-read `_coupon_remaining_cycles`, copy the newest note, and append every ID to `slt-catalog-registry`.

## Expected results
1. Both checkouts total exactly **$7.50** (subtotal $10.00, discount -$2.50).
2. SUB_A: `_coupon_count_initial=yes`, `_coupon_original_cycles=3`, `_coupon_remaining_cycles=2` (via `maybeCountInitialCheckoutCycle()` on `arraysubs_order_paid`), `_coupon_initial_cycle_pending` DELETED, note `Coupon "sltrec3" counted against the initial checkout. 2 discount cycle(s) remaining.` SUB_B: same but `count_initial=no`, remaining **3**, `_coupon_initial_cycle_pending` ABSENT, no such note.
3. SUB_A renewals #1 and #2: total **$7.50**, fee `Recurring Discount: sltrec3` = -2.50; remaining 1 then 0.
4. At remaining 0: `Coupon "sltrec3" has been fully used. All discount cycles have been consumed. Future renewals will be charged at full price.`
5. **SUB_A renewal #3, charged the evening of 2026-08-07, totals $10.00 with ZERO fee items** (`applyRecurringCoupons()` returns on `original>0 && remaining<=0`). **Drop-off proved by the watch run on the morning of 2026-08-08 = watch day D6.**
6. SUB_B renewals #1/#2/#3 each total **$7.50** with fee `Recurring Discount: sltrec3noinit` = -2.50; remaining 2, 1, 0.
7. **SUB_B renewal #4, charged the evening of 2026-08-08, totals $10.00 with ZERO fee items. Drop-off proved by the watch run on the morning of 2026-08-09 = watch day D7.**
8. Discounted charges: SUB_A = 3, SUB_B = 4 - that delta IS the `count_initial` contract. Both stay `arraysubs-active`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription x2 | parents paid | both customers | `is active` | `wait-new $M0 120 "is active"` twice |
| 2 | admin_new_subscription x2 | parents paid | admin_email | `New subscription #` | `mailpit-agent list 30` |
| 3 | payment_successful | each renewal | each customer | `Payment received for subscription #<ID>` | `wait-new <prev> 900 "Payment received"` |
| 4 | **NONE EXPECTED**: none at exhaustion, no renewal_invoice ever | remaining 0 / invoice legs | - | - | no listener on `arraysubs_coupon_expired_on_subscription`; renewal_invoice suppressed for auto-renew subs |

## Evidence to capture
- Screenshots `SLT-CPN-03-NN-<slug>.png`: both coupon groups, both $7.50 checkouts, the two drop-off renewals.
- Coupon IDs, SUB IDs, `k` values, a per-day table of renewal order IDs and totals, every `_coupon_remaining_cycles` reading, every note verbatim.

## Pass criteria
- [ ] Both checkouts $7.50; SUB_A remaining 2, SUB_B remaining 3 after parent payment
- [ ] SUB_A: exactly 2 discounted renewals, then $10.00 on 2026-08-07
- [ ] SUB_B: exactly 3 discounted renewals, then $10.00 on 2026-08-08
- [ ] Exhaustion note verbatim; correct fee name on every discounted renewal; zero mail at exhaustion

## Isolation / teardown
- Watch: D4 both still discounted; **D6 (08-08) SUB_A at $10.00**; **D7 (08-09) SUB_B at $10.00**; D8-D9 both $10.00.
- Leaves two accounts and two active daily subscriptions - add all four to SLT-SETUP-99A's cancel list. Neither coupon may be edited mid-window. Close `cpn03-*` sessions only.


---

### Verified environment facts (2026-08-01/02 — do not re-derive)

- **Nothing fires at `_next_payment_date`.** Every scheduled leg is shifted by
  `crc32('arraysubs-spread-'.$subscription_id) % 21600` (0-6 h). Charge fires at `due + offset`,
  invoice at `due + offset - 6h`. The stored date never moves. **Assert a window, not a point.**
- Currency `USD`. **Taxes are OFF** (`woocommerce_calc_taxes = no`) — never assert a tax line.
- Orders use **HPOS** (`wp_wc_orders`), not `wp_posts`.
- `woocommerce_enable_guest_checkout = yes`, but ArraySubs force-requires registration for
  **subscription** carts via `woocommerce_checkout_registration_required`
  (`SubscriptionCheckout/Services/Hooks.php:103`, `CheckoutHelpersTrait.php:93-100`).
- WooCommerce **grouped** products have zero handling in either plugin — grouped tasks are
  exploratory: document behaviour, do not assert a spec.
- WP-Cron runs every minute from `/etc/cron.d/mirror-help-arrayhash-wordpress`. Scheduled actions
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence before forcing.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run `wp action-scheduler run` without `--hooks=`; prefer a single action by ID.
- Evidence goes in `qa/subscription-lifecycle-test/evidence/<TASK-KEY>/`.
