---
id: 62
title: 'Quantity 3 on a segment-2 prorated first charge: prove the proration multiplies per unit'
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - renewal-sync
    - day-03
due: "2026-08-29"
estimate: 1h15m
depends_on:
    - 10
    - 11
    - 12
    - 61
class: standard
---

> **SLT-SYN-14** · group `sync` · scheduled **D03** (2026-08-26)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove a segment-2 prorated first charge multiplies correctly at quantity > 1: the ratio is applied to the UNIT price, rounded to 2 decimals, then multiplied by quantity. The price is chosen so unit-first and line-first rounding give different totals, so the observed figure says which one happens.

## Scope
- Gateway: Stripe test
- Checkout: classic
- Account: new registered `slt2-qty` (CREATED HERE); card `4242…4242`
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01 (classic harness), -02 (sync OFF), -03 done and the **SLT-SYN-04 bracket closed** (see `SLT-SYN-04-bracket.txt`). Buy after 12:00 on **2026-08-26**. Creates one product and one account; `SLT2 Flex Week Segments` must NOT be reused (cohorts bound to slt2-flex/2/3).
- `start_of_week` is **6 (Saturday)**, so the week cycle is 08-01 -> 08-29 site and an 08-26 purchase is day-in-cycle 5. It MUST complete that day: on 08-28 the day hits 7 and the segment flips.
- Sessions `admin-SLT-SYN-14` and `customer-SLT-SYN-14` are exclusive to this task.

## Test data
| Item | Value |
|---|---|
| Product | `SLT2 Flex Qty Week` / `slt2-flex-qty-week`, Simple, Virtual, week/1, **$9.99**, qty **3** |
| Flex plan | 3 segments active, seg1_end **1**, seg2_end **6** -> `1 / 2-6 / 7` |
| Cycle | cycle_start `2026-08-21 18:00:00` UTC, due `2026-08-28 18:00:00` UTC, `cycle_days` 7; the D3 purchase is site-local cycle day 5, so `days(now->due)=3`, `remaining = max(1, 3-1) = 2`, ratio `2/7` |

## Steps
1. `USER_PRE=$(mailpit-agent latest-id)` and `SUBCOUNT_PRE=<exact current SLT2 subscription count>`. In `admin-SLT-SYN-14`, create `slt2-qty` (Customer) at `/wp-admin/user-new.php`, with **Send User Notification** unticked and billing per SLT-SETUP-03. Record its numeric ID, classify exactly one admin-addressed `New User Registration` after `USER_PRE`, and prove there is no customer account/password mail. Then set product-only baseline `M0=$(mailpit-agent latest-id)`.
2. Create the product: Simple, Virtual, tick **Subscription [ArraySubs]**, price `9.99`, **Billing Period** `Week`, **Interval** `1`, length 0, trial 0, no fee. Enable Flexible Renewal Sync with all toggles ON and set the legend to `1` / `2 - 6` / `7`; capture `SLT-SYN-14-01-legend.png`. Publish/reload, record numeric `PRODUCT_ID`, and abort unless numeric. Append only that ID to the preserved Shop Access exclusion rule and require it exactly once before storefront access.
3. `wp post meta list "$PRODUCT_ID" --keys=_subscription_period,_subscription_interval,_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_regular_price --allow-root`. Inspect the complete delta after `M0`, require zero product-attributable mail, then set checkout-only `M_BUY=$(mailpit-agent latest-id)`.
4. In `customer-SLT-SYN-14`, log in as `slt2-qty`; require `/slt2-classic-cart` and persistent-cart meta empty, add quantity 1, handle any one-click redirect by explicitly reopening `/slt2-classic-cart`, and capture `SLT-SYN-14-02-cart-qty1.png`.
5. Set cart quantity **3**, **Update cart**, capture `SLT-SYN-14-03-cart-qty3.png`, and record today/recurring figures.
6. Open `/slt2-classic-checkout`, select Stripe, and pay without capturing populated card fields. Capture the safe receipt as `SLT-SYN-14-04-order-received.png` and record numeric `ORDER_ID`. Resolve its sole numeric `SUB_ID` through `wp post meta get "$ORDER_ID" _subscription_ids --format=json --allow-root` plus a strict guard; cross-check reverse parent/customer/product and `SUBCOUNT_POST == SUBCOUNT_PRE + 1`. Never use the WooCommerce accessor or recency. Require the complete four-message WC/ArraySubs delta after `M_BUY`. Reopen the cart, prove both carts empty, capture `SLT-SYN-14-04a-cart-empty-after.png`, and close only `customer-SLT-SYN-14`.
7. Dump numeric `$SUB_ID` with the exact sync/date keys plus order qty/total. In `admin-SLT-SYN-14`, search exact `$SUB_ID` on the real ArraySubs list and exact pending actions, then capture `SLT-SYN-14-05-schedule.png`. Compute the offset, publish the order/subscription/count/mail/action IDs, exact gates, and `charge−5m` deadline to the registry and D03 watch report; close the admin session and keep this card `in-progress`.
8. No earlier than five minutes before the exact 08-29 charge gate, store `QTY_REN_PRE=$(mailpit-agent latest-id)` in the registry/report. After the gate, require the full recurring charge, wait for the exact payment-success subject, reconcile the complete delta, resolve the renewal order by subscription/scheduled-cycle relationship, reopen the admin session, and capture `SLT-SYN-14-06-renewal-order.png`. If the first charge was `$8.56` rather than `$8.55`, create a dedicated issue only after live proof with all mandatory task/fixture/user/route/reproduction/expected/actual/order/meta/counterexample fields. Close the session, independently review the full record, and move this card through review to done.

## Expected results
1. `_arraysubs_flex_sync_enabled=yes`, `seg1_end=1`, `seg2_end=6`, `_subscription_period=week`, `_subscription_interval=1`, `_regular_price=9.99`.
2. Day-in-cycle **5** -> segment **2** -> `_renewal_sync_first_charge_mode=prorate`.
3. Qty 1: today **$2.85** = `round(9.99*2/7,2)`; recurring **$9.99/week** from 08-29.
4. Qty 3: today **$8.55** (2.85 x 3); recurring **$29.97/week**; order total **$8.55**.
5. **If today reads $8.56 the ratio hit the line total** (`round(29.97*2/7,2)=8.56`) — rounding after multiplication. $8.56 is the finding; $8.55 is unit-first as designed.
6. `_next_payment_date` = **`2026-08-28 18:00:00` UTC** = 08-29 00:00 site — the week boundary, unchanged by quantity.
7. `_renewal_sync_initial_recurring_amount` stores the purchased checkout-line figure (**$8.55**) on this runtime, while the unit-first proof still comes from the observed qty-1/qty-3 totals (`$2.85` then `$8.55`, not `$8.56`). Stripe's minimum bump does not apply, so `_renewal_sync_gateway_minimum_amount` must stay absent.
8. The 08-29 renewal order totals **$29.97** (3 x $9.99), proving proration applied to the first charge only.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `new_subscription`, `admin_new_subscription`, WC paid-order + admin New order | step 6 | slt2-qty / admin | `is active`, `New subscription #`, order subjects | complete checkout-only delta after `M_BUY`; save/show all four exact IDs |
| 2 | `payment_successful` | 08-29 renewal | slt2-qty | `Payment received for subscription #<ID>` | `mailpit-agent wait-new "$QTY_REN_PRE" 900 ...`, then inspect every newer message |
| 3 | NONE, steps 2-3 | creation | — | — | Complete product-only delta after `M0`; zero task-attributable mail, while unrelated/background mail is classified |
| 4 | WP New User Registration | setup before `M0` | admin | `New User Registration` | exactly one after `USER_PRE`; zero customer account/password mail |

## Evidence to capture
- `SLT-SYN-14-01` through `-06`, including safe receipt, empty-cart, schedule, and exact renewal order; no image may contain a full card number.
- Product/user/sub/order IDs; count delta; figures at both quantities; meta/Shop Access proof; `USER_PRE`, setup-mail ID, product-only `M0`, checkout-only `M_BUY`, four checkout-mail IDs, `QTY_REN_PRE`, renewal mail IDs; final cart proof.

## Pass criteria
- [ ] Product saved week/1 $9.99, segments `1 / 2-6 / 7`; mode `prorate` on the 08-26 purchase (day-in-cycle 5)
- [ ] Qty 1 charges $2.85; qty 3 charges $8.55 and the order total is $8.55
- [ ] Recurring $29.97/week; `_next_payment_date` = `2026-08-28 18:00:00` UTC
- [ ] `_renewal_sync_initial_recurring_amount` is the checkout-line $8.55 and `_renewal_sync_gateway_minimum_amount` stays absent
- [ ] The 08-29 renewal charges $29.97; only the listed mails arrive
- [ ] Product is excluded exactly once from Shop Access before storefront access; setup mail isolated; cart/persistent-cart meta empty after checkout

## Isolation / teardown
- New artifacts: 1 `SLT ` product, 1 `slt2-` user, 1 sub, 1 order — ids to the registry for 99B. The only global mutation is appending the task-owned product ID to the preserved Shop Access exclusion list; SETUP-99A restores the exact pre-window Shop Access snapshot. No other flex product is touched. Close only `admin-SLT-SYN-14` and `customer-SLT-SYN-14` after each dated leg.
- Handoff: renews every Saturday 00:00 site + spread offset (08-29, 09-05); only 08-29 is in the watch window, the D6 watch owns it.

---

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
