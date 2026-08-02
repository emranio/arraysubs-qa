---
id: 62
title: 'Quantity 3 on a segment-2 prorated first charge: prove the proration multiplies per unit'
status: todo
priority: high
created: 2026-08-02T03:43:08.523856038+02:00
updated: 2026-08-02T03:43:18.815291178+02:00
tags:
    - renewal-sync
    - day-03
    - has-conflicts
due: "2026-08-05"
estimate: 1h15m
depends_on:
    - 10
    - 11
    - 12
    - 61
class: standard
---

> **SLT-SYN-14** · group `sync` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`critical` · shared-global-setting / undeclared exclusive bracket** — with `SLT-EML-12`, `SLT-CHK-09`, `SLT-CPN-04`, `SLT-CHK-05`, `SLT-ADM-05`, `SLT-EML-06`

- *Problem:* SLT-EML-12 (d3) writes the WooCommerce per-email Subject/Heading/Additional content on arraysubs_new_subscription globally, for a bracket it only vaguely bounds ('run after 12:00'). Every new_subscription email site-wide inside that bracket carries the subject 'SLT-EML-12 {customer_first_name} :: sub ...'. Four other D3 tasks place checkouts and gate on the default subject: SLT-CHK-09 ('mailpit-agent wait-new MB09 180 "is active"'), SLT-CPN-04 ('wait-new $M0 120 "is active"', 18:00-19:00), SLT-SYN-14 ('wait-new M0 180', after 12:00), plus SLT-ADM-05's status-change activation on D3. Any of these landing inside EML-12's bracket exits 124 and files a false 'missing email' bug. EML-12's own admin_new_subscription count (expects exactly 3) is also corrupted by any foreign checkout in the bracket.
- *Required fix:* Make EML-12 a declared exclusive bracket, same pattern as SLT-SYN-04's: fixed window 21:00-21:40 site on D3 (2026-08-05), after CPN-04's 18:00-19:00 slot has closed; open/close UTC timestamps written to slt-evidence/SLT-EML-12-bracket.txt and posted to the registry; no other SLT task may place an order, activate a subscription, or run a checkout inside it. Add a pre-flight step: assert no SLT checkout task is in-progress on the board. Apply the identical treatment to SLT-EML-13's admin-email OFF bracket (see separate entry).

---
## Objective
Prove a segment-2 prorated first charge multiplies correctly at quantity > 1: the ratio is applied to the UNIT price, rounded to 2 decimals, then multiplied by quantity. The price is chosen so unit-first and line-first rounding give different totals, so the observed figure says which one happens.

## Scope
- Gateway: Stripe test
- Checkout: classic
- Account: new registered `slt-qty` (CREATED HERE); card `4242…4242`
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01 (classic harness), -02 (sync OFF), -03 done and the **SLT-SYN-04 bracket closed** (see `SLT-SYN-04-bracket.txt`). Buy after 12:00 on **2026-08-05**. Creates one product and one account; `SLT Flex Week Segments` must NOT be reused (cohorts bound to slt-flex/2/3).
- `start_of_week` is **6 (Saturday)**, so the week cycle is 08-01 -> 08-08 site and an 08-05 purchase is day-in-cycle 5. It MUST complete that day: on 08-07 the day hits 7 and the segment flips.

## Test data
| Item | Value |
|---|---|
| Product | `SLT Flex Qty Week` / `slt-flex-qty-week`, Simple, Virtual, week/1, **$9.99**, qty **3** |
| Flex plan | 3 segments active, seg1_end **1**, seg2_end **6** -> `1 / 2-6 / 7` |
| Cycle | cycle_start `2026-07-31 18:00:00` UTC, due `2026-08-07 18:00:00` UTC, `cycle_days` 7; `remaining = max(1, days(start->due)-1) = 1`, ratio `1/7` |

## Steps
1. `mailpit-agent latest-id` -> `M0`. Create `slt-qty` (Customer) at `/wp-admin/user-new.php`.
2. Create the product: Simple, Virtual, tick **Subscription [ArraySubs]**, price `9.99`, **Billing Period** `Week`, **Interval** `1`, length 0, trial 0, no fee. Tick **Flexible Renewal Sync to Next Billing Cycle**, all toggles ON, handles until the legend reads `1` / `2 - 6` / `7`. Publish, reload.
3. `wp post meta list <ID> --keys=_subscription_period,_subscription_interval,_arraysubs_flex_sync_enabled,…seg1_end,…seg2_end,_regular_price`.
4. `--session guest-SLT-SYN-14`, log in as `slt-qty`, add at **quantity 1**, open `/slt-classic-cart`, screenshot the line total and sub meta rows.
5. Set the cart quantity to **3**, **Update cart**, re-screenshot. Record today and recurring figures.
6. Open `/slt-classic-checkout`, select Stripe, pay. Screenshot the order-received totals.
7. Dump the sub: `_renewal_sync_enabled`, `…_first_charge_mode`, `…_cycle_start_date`, `…_initial_recurring_amount`, `_next_payment_date`, plus order qty and total.
8. Follow-up on the 08-08 watch day: the renewal must charge the full recurring amount.

## Expected results
1. `_arraysubs_flex_sync_enabled=yes`, `seg1_end=1`, `seg2_end=6`, `_subscription_period=week`, `_subscription_interval=1`, `_regular_price=9.99`.
2. Day-in-cycle **5** -> segment **2** -> `_renewal_sync_first_charge_mode=prorate`.
3. Qty 1: today **$1.43** = `round(9.99*1/7,2)`; recurring **$9.99/week** from 08-08.
4. Qty 3: today **$4.29** (1.43 x 3); recurring **$29.97/week**; order total **$4.29**.
5. **If today reads $4.28 the ratio hit the line total** (`round(29.97/7,2)=4.28`) — rounding after multiplication. $4.28 is the finding; $4.29 is unit-first as designed.
6. `_next_payment_date` = **`2026-08-07 18:00:00` UTC** = 08-08 00:00 site — the week boundary, unchanged by quantity.
7. `_renewal_sync_initial_recurring_amount` is the UNIT figure ($1.43); the multiply happens on the order line. Stripe's minimum bump does not apply (143 minor units > the 50c floor), so `_renewal_sync_gateway_minimum_amount` must not raise it.
8. The 08-08 renewal order totals **$29.97** (3 x $9.99), proving proration applied to the first charge only.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `new_subscription`, `admin_new_subscription`, Woo order mail | step 6 | slt-qty / admin | `is active`, `New subscription #` | `wait-new M0 180` |
| 2 | `payment_successful` | 08-08 renewal | slt-qty | `Payment received for subscription #` | watch `list 50` |
| 3 | NONE, steps 2-5 | creation, previews | — | — | `latest-id`==`M0` to step 6 |

## Evidence to capture
- `SLT-SYN-14-01-legend.png`, `-02-cart-qty1.png`, `-03-cart-qty3.png`, `-04-order-received.png`, `-05-schedule.png`.
- Product, sub, order ids; today/recurring figures at both quantities; meta dump; Mailpit ids

## Pass criteria
- [ ] Product saved week/1 $9.99, segments `1 / 2-6 / 7`; mode `prorate` on the 08-05 purchase (day-in-cycle 5)
- [ ] Qty 1 charges $1.43; qty 3 charges $4.29 and the order total is $4.29
- [ ] Recurring $29.97/week; `_next_payment_date` = `2026-08-07 18:00:00` UTC
- [ ] `_renewal_sync_initial_recurring_amount` is the unit $1.43, no minimum bump
- [ ] The 08-08 renewal charges $29.97; only the listed mails arrive

## Isolation / teardown
- New artifacts: 1 `SLT ` product, 1 `slt-` user, 1 sub, 1 order — ids to the registry for 99B. No global setting changed, no other flex product touched.
- Handoff: renews every Saturday 00:00 site + spread offset (08-08, 08-15); only 08-08 is in the watch window, the D6 watch owns it.

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
