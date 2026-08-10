---
id: 32
title: SLTREC3 vs SLTREC3NOINIT on block checkout - N-cycle counting and the exact renewal where the discount stops
status: done
priority: critical
created: 2026-08-02T03:43:05.721219012+02:00
updated: 2026-08-05T21:37:49.373624002+02:00
started: 2026-08-05T21:04:15.923652467+02:00
completed: 2026-08-05T21:04:15.923652467+02:00
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
- Two fresh accounts: both buy the same product, one coupon per account. At `one_per_customer=false`, `auto_migrate_on_checkout` is inert; separate buyers keep the two coupon fixtures unambiguous.
- Both checkouts **18:00-19:00 site (UTC+6) on 2026-08-04**, same hour so the ladders align. Satisfies C02; keeps every invoice leg (12:00-19:00 site) clear of SLT-SYN-04's D3 09:00-11:00 bracket.
- Coupon expiry must be `2026-08-15` (date-only expiry is site-local midnight, so this is the first value that covers all of D12).
- Sessions `admin-SLT-CPN-03`, `customer-a-SLT-CPN-03`, and `customer-b-SLT-CPN-03` are exclusive; A and B never share one - sessions share a cart (C09).

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
1. `USER_PRE=$(mailpit-agent latest-id)` and `SUBCOUNT_BEFORE=<exact current SLT subscription count>`. In `agent-browser --session admin-SLT-CPN-03`, create `slt-cpncyc` and `slt-cpncyc2` (Customer, **Send User Notification** unticked, `SltQa!2026#Pass`, billing per SLT-SETUP-03); record both numeric user IDs.
2. Open both coupons; screenshot each ArraySubs group; confirm **Count initial checkout** TICKED on SLTREC3 and UNTICKED on SLTREC3NOINIT, both cycles = 3, expiry 2026-08-15. Record coupon IDs.
3. Before either checkout, classify the setup-only Mailpit delta after `USER_PRE`: exactly two admin-addressed `New User Registration` messages are allowed (one per user), with zero customer-addressed account/password messages. Record both IDs, then set buyer A's checkout-only baseline `M_A=$(mailpit-agent latest-id)`.
4. In `agent-browser --session customer-a-SLT-CPN-03`, log in as `slt-cpncyc`; assert the browser and persistent carts EMPTY and capture `SLT-CPN-03-01-a-cart-empty-before.png`; add SLT Daily Core. If one-click redirects to block checkout, record it and continue there; otherwise open `/checkout/`. Expand **Add a coupon**, apply `SLTREC3`, confirm Discount **-$2.50** and Total **$7.50**, and capture `SLT-CPN-03-01b-a-checkout.png` before card entry; then pay with Stripe. Record numeric `ORDER_CPN_INIT` and capture the safe receipt as `SLT-CPN-03-01a-a-order-received.png`. Read that order's linkage through `wp post meta get "$ORDER_CPN_INIT" _subscription_ids --format=json --allow-root`, resolve `SUB_CPN_INIT` only through a strict one-element numeric `jq -e` guard, and cross-check reverse parent/customer/product plus `SUBCOUNT_AFTER_A == SUBCOUNT_BEFORE + 1`; never use the WooCommerce order meta accessor or recency. Run `mailpit-agent wait-new "$M_A" 180 "is active"`, classify every message newer than `M_A`, and require the exact WC Completed order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup IDs. Then reopen `/cart/`, prove it and persistent-cart user meta are empty, and capture `SLT-CPN-03-02-a-cart-empty-after.png`.
5. Set `M_B=$(mailpit-agent latest-id)` only after buyer A's delta is fully classified. In `agent-browser --session customer-b-SLT-CPN-03`, log in as `slt-cpncyc2`; assert both carts EMPTY and capture `SLT-CPN-03-03-b-cart-empty-before.png`; repeat with `SLTREC3NOINIT`, handling the one-click redirect the same way. Confirm Total **$7.50** and capture `SLT-CPN-03-03b-b-checkout.png` before card entry; pay, record numeric `ORDER_CPN_NOINIT`, and capture `SLT-CPN-03-03a-b-order-received.png`. Use the same post-meta JSON path and strict guard to resolve `SUB_CPN_NOINIT`, cross-check reverse parent/customer/product plus `SUBCOUNT_AFTER_B == SUBCOUNT_BEFORE + 2`, and run `mailpit-agent wait-new "$M_B" 180 "is active"`. Classify every message newer than `M_B` and require the second exact four-message checkout set. Reopen `/cart/`, prove it and persistent-cart user meta are empty, and capture `SLT-CPN-03-04-b-cart-empty-after.png`.
6. For both: `wp post meta list <ID> --allow-root | rg _coupon`; copy the capture notes verbatim; compute `k = crc32('arraysubs-spread-'.ID) % 21600`; record invoice `due+k−6h` and charge `due+k`. Publish both users/orders/subscriptions, the eight checkout-mail IDs, exact pending action IDs/times, and both first `charge−5m` deadlines to the registry and D02 watch report. Close all three task sessions and keep this card `in-progress`.
7. For each subscription and cycle, store a distinct registry baseline `REN_PRE_<ALIAS>_<N>=$(mailpit-agent latest-id)` no earlier than five minutes before that subscription's exact recorded charge gate. **Mornings 08-06, 08-07, 08-08 and 08-09 after 09:00 site**: reopen `admin-SLT-CPN-03`; for each exact new renewal order resolve its subscription relationship, screenshot the item table with a unique alias/cycle filename, run `wp eval '$o=wc_get_order(<REN>);foreach($o->get_fees() as $f){echo $f->get_name()."|".$f->get_total();}echo "|".$o->get_total();' --allow-root`, re-read `_coupon_remaining_cycles`, copy the newest note, run `mailpit-agent wait-new "<that exact recorded baseline ID>" 900 "Payment received for subscription #<exact numeric subscription ID>"`, reconcile every message newer than that exact baseline, and append every renewal/action/baseline/mail ID plus the next cycle's `charge−5m` deadline to `slt-catalog-registry` and that day's watch report. Close the admin session after each morning. After the D7 morning proves `SUB_CPN_NOINIT` renewal #4 at full price, independently review the complete D2-D7 record and move this card through review to done.

## Expected results
1. Both checkouts total exactly **$7.50** (subtotal $10.00, discount -$2.50).
2. `SUB_CPN_INIT`: `_coupon_count_initial=yes`, `_coupon_original_cycles=3`, `_coupon_remaining_cycles=2` (via `maybeCountInitialCheckoutCycle()` on `arraysubs_order_paid`), `_coupon_initial_cycle_pending` DELETED, note `Coupon "sltrec3" counted against the initial checkout. 2 discount cycle(s) remaining.` `SUB_CPN_NOINIT`: same but `count_initial=no`, remaining **3**, `_coupon_initial_cycle_pending` ABSENT, no such note.
3. `SUB_CPN_INIT` renewals #1 and #2: total **$7.50**, fee `Recurring Discount: sltrec3` = -2.50; remaining 1 then 0.
4. At remaining 0: `Coupon "sltrec3" has been fully used. All discount cycles have been consumed. Future renewals will be charged at full price.`
5. **`SUB_CPN_INIT` renewal #3, charged the evening of 2026-08-07, totals $10.00 with ZERO fee items** (`applyRecurringCoupons()` returns on `original>0 && remaining<=0`). **Drop-off proved by the watch run on the morning of 2026-08-08 = watch day D6.**
6. `SUB_CPN_NOINIT` renewals #1/#2/#3 each total **$7.50** with fee `Recurring Discount: sltrec3noinit` = -2.50; remaining 2, 1, 0.
7. **`SUB_CPN_NOINIT` renewal #4, charged the evening of 2026-08-08, totals $10.00 with ZERO fee items. Drop-off proved by the watch run on the morning of 2026-08-09 = watch day D7.**
8. Discounted charges: `SUB_CPN_INIT` = 3, `SUB_CPN_NOINIT` = 4 - that delta IS the `count_initial` contract. Both stay `arraysubs-active`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription x2 | parents paid | both customers | `is active` | `mailpit-agent wait-new "$M_A" 180 "is active"`, then separately `mailpit-agent wait-new "$M_B" 180 "is active"` |
| 2 | admin_new_subscription x2 | parents paid | admin_email | `New subscription #` | Complete owner-filtered deltas after `$M_A` and `$M_B`; save/show both exact matching ids |
| 2a | WC New order x2 | parents paid | admin_email | `New order #<exact parent order>` | Each separate complete checkout delta; save/show exact ids |
| 2b | WC Completed order x2 | parents paid | each customer | `is on its way` | Each separate complete checkout delta; save/show exact ids |
| 3 | payment_successful | each renewal | each customer | `Payment received for subscription #<ID>` | exact per-sub/per-cycle baseline, `mailpit-agent wait-new <baseline-id> 900 "Payment received for subscription #<numeric ID>"` |
| 4 | **NONE EXPECTED**: none at exhaustion, no renewal_invoice ever | remaining 0 / invoice legs | - | - | no listener on `arraysubs_coupon_expired_on_subscription`; renewal_invoice suppressed for auto-renew subs |

## Evidence to capture
- Screenshots `SLT-CPN-03-NN-<slug>.png`: both coupon groups, both safe receipts/$7.50 checkouts, both customers' before/after empty carts, and uniquely named per-sub/per-cycle renewal item tables including the two drop-offs.
- Coupon/user/order/subscription IDs, both count deltas, `k` values, `USER_PRE`, both classified setup-mail IDs, checkout-only `M_A`/`M_B`, all eight checkout-message IDs, a per-day table of renewal order/action IDs and totals, every per-sub/per-cycle renewal baseline, every `_coupon_remaining_cycles` reading, every note verbatim.

## Pass criteria
- [ ] Both checkouts $7.50; `SUB_CPN_INIT` remaining 2, `SUB_CPN_NOINIT` remaining 3 after parent payment
- [ ] `SUB_CPN_INIT`: exactly 2 discounted renewals, then $10.00 on 2026-08-07
- [ ] `SUB_CPN_NOINIT`: exactly 3 discounted renewals, then $10.00 on 2026-08-08
- [ ] Exhaustion note verbatim; correct fee name on every discounted renewal; zero mail at exhaustion
- [ ] Exactly two admin-only setup mails and no customer account/password mail; both session carts and persistent-cart metas empty after checkout
- [ ] Both receipt orders link bidirectionally to exactly one distinct subscription; both complete four-message checkout sets and every future gate handoff are recorded

## Isolation / teardown
- Watch: D4 both still discounted; **D6 (08-08) `SUB_CPN_INIT` at $10.00**; **D7 (08-09) `SUB_CPN_NOINIT` at $10.00**; D8-D9 both $10.00.
- Leaves two accounts and two active daily subscriptions - add both subscriptions to SLT-SETUP-99A's cancel list and both accounts to SLT-SETUP-99B's deletion list. Neither coupon may be edited mid-window. Close all three exact sessions after D2, then reopen/close only `admin-SLT-CPN-03` for each dated renewal read; never use a wildcard or `close --all`.


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
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence and do not force a natural-watch action.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run a bare or `--hooks=` Action Scheduler drain. Run one known action ID at a time only when the task explicitly authorizes it and after the required queue pre-flight; natural-watch actions are never forced.
- Evidence goes under `/home/server-manager/slt-evidence/` using task-key-prefixed filenames.

[[2026-08-05]] Wed 21:04
UNVERIFIED (missed D02 execution window) on 2026-08-05.

This task required two aligned purchases during the fixed 18:00-19:00 site window on 2026-08-04 so that the recurring-coupon ladders would line up across D4-D7. Live verification on 2026-08-05 found no `slt-cpncyc` or `slt-cpncyc2` users and no ArraySubs subscriptions owned by those logins. The D03 suite report and evening automation log explicitly classify missing D2 coupon fixtures as execution gaps that remain `UNVERIFIED` unless a later authored recovery path permits creation. No such recovery path exists here, so this card closes without late substitute purchases.
