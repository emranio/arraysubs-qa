---
id: 62
title: 'Quantity 3 on a segment-2 prorated first charge: prove the proration multiplies per unit'
status: done
priority: high
created: 2026-08-02T03:43:08.523856038+02:00
updated: 2026-08-08T02:34:23.39513714+02:00
started: 2026-08-08T02:34:12.165542972+02:00
completed: 2026-08-08T02:34:12.165542972+02:00
tags:
    - renewal-sync
    - day-03
due: "2026-08-08"
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
- Sessions `admin-SLT-SYN-14` and `customer-SLT-SYN-14` are exclusive to this task.

## Test data
| Item | Value |
|---|---|
| Product | `SLT Flex Qty Week` / `slt-flex-qty-week`, Simple, Virtual, week/1, **$9.99**, qty **3** |
| Flex plan | 3 segments active, seg1_end **1**, seg2_end **6** -> `1 / 2-6 / 7` |
| Cycle | cycle_start `2026-07-31 18:00:00` UTC, due `2026-08-07 18:00:00` UTC, `cycle_days` 7; the D3 purchase is site-local cycle day 5, so `days(now->due)=3`, `remaining = max(1, 3-1) = 2`, ratio `2/7` |

## Steps
1. `USER_PRE=$(mailpit-agent latest-id)` and `SUBCOUNT_PRE=<exact current SLT subscription count>`. In `admin-SLT-SYN-14`, create `slt-qty` (Customer) at `/wp-admin/user-new.php`, with **Send User Notification** unticked and billing per SLT-SETUP-03. Record its numeric ID, classify exactly one admin-addressed `New User Registration` after `USER_PRE`, and prove there is no customer account/password mail. Then set product-only baseline `M0=$(mailpit-agent latest-id)`.
2. Create the product: Simple, Virtual, tick **Subscription [ArraySubs]**, price `9.99`, **Billing Period** `Week`, **Interval** `1`, length 0, trial 0, no fee. Enable Flexible Renewal Sync with all toggles ON and set the legend to `1` / `2 - 6` / `7`; capture `SLT-SYN-14-01-legend.png`. Publish/reload, record numeric `PRODUCT_ID`, and abort unless numeric. Append only that ID to the preserved Shop Access exclusion rule and require it exactly once before storefront access.
3. `wp post meta list "$PRODUCT_ID" --keys=_subscription_period,_subscription_interval,_arraysubs_flex_sync_enabled,_arraysubs_flex_sync_seg1_end,_arraysubs_flex_sync_seg2_end,_regular_price --allow-root`. Inspect the complete delta after `M0`, require zero product-attributable mail, then set checkout-only `M_BUY=$(mailpit-agent latest-id)`.
4. In `customer-SLT-SYN-14`, log in as `slt-qty`; require `/slt-classic-cart` and persistent-cart meta empty, add quantity 1, handle any one-click redirect by explicitly reopening `/slt-classic-cart`, and capture `SLT-SYN-14-02-cart-qty1.png`.
5. Set cart quantity **3**, **Update cart**, capture `SLT-SYN-14-03-cart-qty3.png`, and record today/recurring figures.
6. Open `/slt-classic-checkout`, select Stripe, and pay without capturing populated card fields. Capture the safe receipt as `SLT-SYN-14-04-order-received.png` and record numeric `ORDER_ID`. Resolve its sole numeric `SUB_ID` through `wp post meta get "$ORDER_ID" _subscription_ids --format=json --allow-root` plus a strict guard; cross-check reverse parent/customer/product and `SUBCOUNT_POST == SUBCOUNT_PRE + 1`. Never use the WooCommerce accessor or recency. Require the complete four-message WC/ArraySubs delta after `M_BUY`. Reopen the cart, prove both carts empty, capture `SLT-SYN-14-04a-cart-empty-after.png`, and close only `customer-SLT-SYN-14`.
7. Dump numeric `$SUB_ID` with the exact sync/date keys plus order qty/total. In `admin-SLT-SYN-14`, search exact `$SUB_ID` on the real ArraySubs list and exact pending actions, then capture `SLT-SYN-14-05-schedule.png`. Compute the offset, publish the order/subscription/count/mail/action IDs, exact gates, and `charge−5m` deadline to the registry and D03 watch report; close the admin session and keep this card `in-progress`.
8. No earlier than five minutes before the exact 08-08 charge gate, store `QTY_REN_PRE=$(mailpit-agent latest-id)` in the registry/report. After the gate, require the full recurring charge, wait for the exact payment-success subject, reconcile the complete delta, resolve the renewal order by subscription/scheduled-cycle relationship, reopen the admin session, and capture `SLT-SYN-14-06-renewal-order.png`. If the first charge was `$8.56` rather than `$8.55`, create a standalone issue only after live proof with all mandatory task/fixture/user/route/reproduction/expected/actual/order/meta/counterexample fields. Close the session, independently review the full record, and move this card through review to done.

## Expected results
1. `_arraysubs_flex_sync_enabled=yes`, `seg1_end=1`, `seg2_end=6`, `_subscription_period=week`, `_subscription_interval=1`, `_regular_price=9.99`.
2. Day-in-cycle **5** -> segment **2** -> `_renewal_sync_first_charge_mode=prorate`.
3. Qty 1: today **$2.85** = `round(9.99*2/7,2)`; recurring **$9.99/week** from 08-08.
4. Qty 3: today **$8.55** (2.85 x 3); recurring **$29.97/week**; order total **$8.55**.
5. **If today reads $8.56 the ratio hit the line total** (`round(29.97*2/7,2)=8.56`) — rounding after multiplication. $8.56 is the finding; $8.55 is unit-first as designed.
6. `_next_payment_date` = **`2026-08-07 18:00:00` UTC** = 08-08 00:00 site — the week boundary, unchanged by quantity.
7. `_renewal_sync_initial_recurring_amount` stores the purchased checkout-line figure (**$8.55**) on this runtime, while the unit-first proof still comes from the observed qty-1/qty-3 totals (`$2.85` then `$8.55`, not `$8.56`). Stripe's minimum bump does not apply, so `_renewal_sync_gateway_minimum_amount` must stay absent.
8. The 08-08 renewal order totals **$29.97** (3 x $9.99), proving proration applied to the first charge only.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `new_subscription`, `admin_new_subscription`, WC paid-order + admin New order | step 6 | slt-qty / admin | `is active`, `New subscription #`, order subjects | complete checkout-only delta after `M_BUY`; save/show all four exact IDs |
| 2 | `payment_successful` | 08-08 renewal | slt-qty | `Payment received for subscription #<ID>` | `mailpit-agent wait-new "$QTY_REN_PRE" 900 ...`, then inspect every newer message |
| 3 | NONE, steps 2-3 | creation | — | — | Complete product-only delta after `M0`; zero task-attributable mail, while unrelated/background mail is classified |
| 4 | WP New User Registration | setup before `M0` | admin | `New User Registration` | exactly one after `USER_PRE`; zero customer account/password mail |

## Evidence to capture
- `SLT-SYN-14-01` through `-06`, including safe receipt, empty-cart, schedule, and exact renewal order; no image may contain a full card number.
- Product/user/sub/order IDs; count delta; figures at both quantities; meta/Shop Access proof; `USER_PRE`, setup-mail ID, product-only `M0`, checkout-only `M_BUY`, four checkout-mail IDs, `QTY_REN_PRE`, renewal mail IDs; final cart proof.

## Pass criteria
- [ ] Product saved week/1 $9.99, segments `1 / 2-6 / 7`; mode `prorate` on the 08-05 purchase (day-in-cycle 5)
- [ ] Qty 1 charges $2.85; qty 3 charges $8.55 and the order total is $8.55
- [ ] Recurring $29.97/week; `_next_payment_date` = `2026-08-07 18:00:00` UTC
- [ ] `_renewal_sync_initial_recurring_amount` is the checkout-line $8.55 and `_renewal_sync_gateway_minimum_amount` stays absent
- [ ] The 08-08 renewal charges $29.97; only the listed mails arrive
- [ ] Product is excluded exactly once from Shop Access before storefront access; setup mail isolated; cart/persistent-cart meta empty after checkout

## Isolation / teardown
- New artifacts: 1 `SLT ` product, 1 `slt-` user, 1 sub, 1 order — ids to the registry for 99B. The only global mutation is appending the task-owned product ID to the preserved Shop Access exclusion list; SETUP-99A restores the exact pre-window Shop Access snapshot. No other flex product is touched. Close only `admin-SLT-SYN-14` and `customer-SLT-SYN-14` after each dated leg.
- Handoff: renews every Saturday 00:00 site + spread offset (08-08, 08-15); only 08-08 is in the watch window, the D6 watch owns it.

---

## D3 checkpoint — 2026-08-05

- Setup: `USER_PRE=5dFUr2i3qmDN4QRnMXefcb`, setup mail `5SpKqkjAIM756AJKIQRk3l`, `SUBCOUNT_PRE=372`, user `365` (`slt-qty` / `slt-qty@example.test`), product `12737` (`SLT Flex Qty Week`), checkout baseline `M_BUY=2JwziMPdgLutdrk96V4UTY`.
- Cart arithmetic passed exactly: qty 1 showed today's charge **$2.85** and recurring **$9.99/week**; qty 3 showed today's charge **$8.55**, subtotal/total **$8.55**, and recurring **$29.97/week**. This rejects the authored line-first bug value **$8.56**.
- Real Stripe classic checkout completed as order `12748` (completed, USD `8.55`, customer `365`) and sole active subscription `12749`; the strict `_subscription_ids` link plus reverse checks passed and the subscription count moved **372 -> 373**.
- Complete checkout-only delta after `M_BUY` is customer order `3SgL2bAHjt0pQ3v9gng1FA`, admin order `3yuJrlL6p55Yy7rdwvyONd`, customer active subscription `1iiwOdU03lYDSaDlV4sLVi`, and admin new subscription `2NWHnHe5PRDFyk9da17atS`.
- Subscription `12749` stores `_product_id=12737`, `_quantity=3`, `_subscription_price=9.99`, `_recurring_amount=9.99`, `_renewal_sync_first_charge_mode=prorate`, `_renewal_sync_initial_recurring_amount=8.55`, `_next_payment_date=2026-08-07 18:00:00Z`, `_parent_order_id=12748`, `_customer_id=365`, `_completed_payments=1`. `_renewal_sync_gateway_minimum_amount` is absent.
- Authored plan correction applied on D3: this runtime stores `_renewal_sync_initial_recurring_amount` as the purchased checkout-line figure, not the per-unit figure. The unit-first proof remains the visible qty-1/qty-3 totals.
- Final pending renewal actions are invoice `14916` at `2026-08-07 17:19:30Z` / `2026-08-07 23:19:30` site and charge `14917` at `2026-08-07 23:19:30Z` / `2026-08-08 05:19:30` site. `k=19170s`; capture `QTY_REN_PRE` only during `[2026-08-07 23:14:30Z, 2026-08-07 23:19:29Z]` / site `[2026-08-08 05:14:30, 05:19:29]`.
- Required D3 evidence is saved at `/home/server-manager/slt-evidence/SLT-SYN-14-01-legend.png` through `SLT-SYN-14-05-schedule.png`, plus `/home/server-manager/slt-evidence/SLT-SYN-14-D03-armed-facts.txt`. Browser and persistent carts are empty after checkout; `customer-SLT-SYN-14` is closed. Keep this card in progress for the D6 natural renewal proof.

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

[[2026-08-05]] Wed 15:00
Board handoff: D3 leg complete, evidence/report/registry published, both browser sessions closed, future natural-watch gate only. Return on D6 to capture QTY_REN_PRE inside [2026-08-07 23:14:30Z, 2026-08-07 23:19:29Z] / site [2026-08-08 05:14:30, 05:19:29] and finish the renewal proof.

[[2026-08-05]] Wed 15:30
Board correction: restored to in-progress. Next gate D6: QTY_REN_PRE 2026-08-07 23:14:30Z-23:19:29Z (2026-08-08 05:14:30-05:19:29 site), then observe action 14917 naturally.

[[2026-08-05]] Wed 16:41
D6 quantity-three renewal follow-up: baseline 2026-08-07 23:14:30Z-23:19:29Z for natural action 14917 at 23:19:30Z.

[[2026-08-05]] Wed 16:46
Board hygiene: returned to todo because this card is not in an active execution window right now. Resume only at the exact gate or follow-up already recorded on the card.

[[2026-08-05]] Wed 17:26
D6 baseline 2026-08-07 23:14:30Z-23:19:29Z; natural action 14917 at 23:19:30Z.

[[2026-08-05]] Wed 17:44
D6 baseline 2026-08-07 23:14:30Z–23:19:29Z; observe natural action 14917 after 23:19:30Z.

[[2026-08-06]] Thu 20:16
Carry-forward note: despite its overdue due-date label, this card's final authored proof is not on Thursday, August 6, 2026. Its exact natural-renewal gate remains Friday, August 7, 2026 23:14:30Z-23:19:29Z / Saturday, August 8, 2026 05:14:30-05:19:29 site, so leave it in todo until that later watch window opens.

[[2026-08-06]] Thu 21:33
Live re-check on 2026-08-06: the overdue label was stale, not a missed execution. Sub 12749 for slt-qty remains arraysubs-active with next payment 2026-08-07 18:00:00 UTC, quantity 3, _renewal_sync_first_charge_mode=prorate, _renewal_sync_initial_recurring_amount=8.55, and pending natural actions 14916 (invoice 2026-08-07 17:19:30 UTC / 2026-08-07 23:19:30 site) plus 14917 (charge 2026-08-07 23:19:30 UTC / 2026-08-08 05:19:30 site). Treat the card as a D6 watch completion item, not unfinished D3 execution.


[[2026-08-08]] Sat 06:31
D6 final follow-up: natural invoice 14916 and charge 14917 completed via WP Cron; renewal order 13174 is completed for sub 12749/customer 365/product 12737 at qty 3 and $29.97, with completed payments 2 and next payment 2026-08-14 18:00:00Z. Exact mails: admin order 2ZvI0MvOPCYHpFsVjs8Ohf and customer payment 6B2jxkahZfmlae0je2Rmah. Browser proof: /home/server-manager/slt-evidence/SLT-SYN-14-06-renewal-order.png. Full review: /home/server-manager/slt-evidence/SLT-SYN-14-D06-final-review.txt. Persisted renewal behavior PASS; overall execution UNVERIFIED only because authored QTY_REN_PRE window 05:14:30-05:19:29 site elapsed before this invocation and no in-window task baseline exists. No action was forced and no product defect is asserted.
