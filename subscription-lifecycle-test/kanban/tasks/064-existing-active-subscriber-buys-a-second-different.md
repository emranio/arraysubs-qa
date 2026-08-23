---
id: 64
title: Existing active subscriber buys a second, different subscription — auto_migrate_on_checkout is gated off; document what migration would do
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - checkout
    - day-04
due: "2026-08-27"
estimate: 1h 30m
depends_on:
    - 10
    - 11
    - 12
    - 5
    - 58
class: standard
---

> **SLT-CHK-08** · group `checkout` · scheduled **D04** (2026-08-27)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Put one customer through two checkouts for two different SLT2 subscription products and record what `auto_migrate_on_checkout = true` actually does. `arraysubs_can_auto_migrate_subscription_on_checkout()` (`settings-helpers.php:777-784`) returns **false** unless `arraysubs_is_one_subscription_per_customer()` is true, and this site has `one_per_customer = false`, so auto-migrate is INERT: the second checkout creates a second independent subscription and never touches the first.

## Scope
- Gateway: Stripe test
- Checkout: block (page 8), both purchases
- Account: new registered — **this task creates** `slt2-chk-second`
- Plugins: free-only

## Preconditions
- SLT-SETUP-01/02/03, SLT-PROD-01 (`SLT2 Daily Core`), SLT-PROD-04 (`SLT2 Signup Fee Daily`) complete.
- **Creates one user beyond the SLT-SETUP-03 matrix**: `slt2-chk-second` / `slt2-chk-second@example.test`, Customer, `SltQa!2026#Pass`, billing per SLT-SETUP-03 step 4 — migration short-circuits above one live subscription, and every existing slt2-* account holds several or is reserved.
- **Change NO global setting.** Writes to `arraysubs_settings` are reserved to SLT-SETUP-02/SYN-04/99A; flipping `one_per_customer` would corrupt every concurrent cart on this shared site.

## Test data
| Item | Value |
|---|---|
| Purchase 1 | SLT2 Daily Core, $10.00 day/1 → charge **$10.00** |
| Purchase 2 | SLT2 Signup Fee Daily, $9.00 day/1 + $15.00 fee → charge **$24.00** |
| Account | `slt2-chk-second` (created here) |
| Card | `4242 4242 4242 4242` |
| Sessions | `admin-SLT-CHK-08`, `cust-SLT-CHK-08` |

## Steps
1. Record `SUBCOUNT_BEFORE=<exact current SLT2 subscription count>` and `USER_PRE=$(mailpit-agent latest-id)`. In `admin-SLT-CHK-08`, create `slt2-chk-second` per SLT-SETUP-03 step 2 (Send User Notification UNTICKED), set billing, and record its numeric user ID. Classify exactly one admin-addressed `New User Registration` after `USER_PRE` and prove there is no customer account/password mail.
2. After setup mail is classified, set purchase-A-only baseline `MB08A=$(mailpit-agent latest-id)`. In `cust-SLT-CHK-08`, open `https://mirror-help.arrayhash.com/my-account`, log in, and confirm Subscriptions plus browser/persistent carts empty; capture `SLT-CHK-08-01-subs-empty.png`.
3. After 12:00 site time, open `/product/slt2-daily-core/` and add it. Record the expected one-click redirect to `/checkout/`, capture the $10.00 summary before card entry as `SLT-CHK-08-01a-checkout-a.png`, fill the hosted test card without capturing it, pay, record numeric `ORDER1` and wall-clock time, and capture the safe receipt as `SLT-CHK-08-01b-receipt-a.png`.
4. `mailpit-agent wait-new "$MB08A" 180 "is active"`; inspect the complete owner-filtered delta and require the exact WC customer paid-order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup IDs. Read linkage with `LINK1=$(wp post meta get "$ORDER1" _subscription_ids --format=json --allow-root)`, resolve exactly one subscription through a strict numeric `jq -e` guard, cross-check reverse parent/customer/product and `SUBCOUNT_AFTER_A == SUBCOUNT_BEFORE + 1`, assign it to numeric `SUB1`, and run `wp post meta list "$SUB1" --keys=_product_id,_recurring_amount,_quantity,_next_payment_date,_status --allow-root`. Do not use the WooCommerce order meta accessor or recency.
5. Set `MB08B=$(mailpit-agent latest-id)` only after purchase A's full delta is classified. Confirm both carts empty.
6. Open `/product/slt2-signup-fee-daily/` and add it. If one-click redirects to checkout, record it and explicitly reopen `/cart/`; require the single intended item and **read every cart-item meta row**. Capture `SLT-CHK-08-23-cart-second.png`.
7. Open `/checkout/`, read every order-summary row, and capture the $24.00 summary as `SLT-CHK-08-23a-checkout-b.png` before card entry. Fill the hosted card without capturing it, pay, record numeric `ORDER2`, and capture `SLT-CHK-08-23b-receipt-b.png`.
8. `mailpit-agent wait-new "$MB08B" 180 "is active"`; require the second exact four-message checkout set. Resolve `ORDER2` through the same post-meta JSON path and strict guard, cross-check reverse parent/customer/product and `SUBCOUNT_AFTER_B == SUBCOUNT_BEFORE + 2`, assign numeric `SUB2`, require it unequal to `$SUB1`, and run the same meta dump.
9. Re-dump numeric `$SUB1` meta; diff field by field against step 4.
10. `wp wc shop_order get "$ORDER2" --user=admin --allow-root`; query `wp_wc_orders_meta` with numeric `ORDER2` through a prepared/validated command and inspect its line-item meta.
11. Without opening product source, prove there are no replacement/proration rows, no migration order or line-item meta, and no mutation of `SUB1`. In `admin-SLT-CHK-08`, capture the exact second order/meta as `SLT-CHK-08-24-no-migration.png` and the two exact active subscriptions as `SLT-CHK-08-25-two-active-subs.png`. If runtime replaces `SUB1`, create a dedicated issue containing this task/plan, both orders/subscriptions/products and user ID/login/role, exact admin/customer URLs, reproduction, expected/actual, UI/meta/mail proof, and purchase A as the counterexample; create or update the mandatory `qa/issues/` kanban card.
12. Compute each subscription's offset with the README argv command, query exact pending invoice/charge rows, and publish both action sets plus each first `charge−5m` deadline to the registry and D04 report. Reopen the cart, prove browser/persistent carts empty, capture `SLT-CHK-08-26-cart-empty-after.png`, close only `cust-SLT-CHK-08` and `admin-SLT-CHK-08`, independently review the evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. Order 1 total `$10.00`; order 2 total `$24.00` ($9.00 line + `Subscription Signup Fee` $15.00). No tax line.
2. Two `arraysubs_data` posts for `slt2-chk-second`, both `arraysubs-active`, `_product_id` = Daily Core and Signup Fee Daily.
3. SUB1 at step 9 matches step 4 except `_next_payment_date` if a renewal ran; `_product_id` and `_recurring_amount` (10.00) unchanged.
4. Steps 6/7 show **no** `Checkout action` row and no `Due today` / `Credit applied` rows.
5. Order 2 has none of `_arraysubs_order_type=plan_switch_checkout`, `_arraysubs_subscription_id`, `_arraysubs_new_product_id`, `_arraysubs_proration_data`; its line item has no `_arraysubs_checkout_migration`.
6. `SUB2._recurring_amount = 9.00`, `_signup_fee = 15.00` — the fee is a first-payment cart fee, so the next-day renewal is $9.00 with no fee line.
7. At this baseline `auto_migrate_on_checkout=true` has no runtime effect; record that tested runtime conclusion in the task evidence. Create a QA issue card only if observed behavior contradicts the expected gate.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `new_subscription` x2 | each activation | slt2-chk-second@example.test | `is active` | `mailpit-agent wait-new "$MB08A" 180 "is active"` / `mailpit-agent wait-new "$MB08B" 180 "is active"` |
| 2 | `admin_new_subscription` x2 | same moments | admin | `New subscription #` | Complete owner-filtered deltas after `MB08A` and `MB08B`; save/show both exact matching ids |
| 3 | WC paid-order + WC New order x2 | processing/completed | customer + admin | exact order / `New order #` | Complete owner-filtered deltas after `MB08A` and `MB08B`; save/show four exact IDs |
| 4 | NONE EXPECTED — `auto_downgrade`, `subscription_cancelled`, plan-switch mail | checkout 2 | — | — | Complete owner-filtered delta after `MB08B`; zero matching messages |
| 5 | WP New User Registration | setup before `MB08A` | admin | `New User Registration` | exactly one after `USER_PRE`; zero customer account/password mail |

## Evidence to capture
- `SLT-CHK-08-01-subs-empty.png`, `-01a-checkout-a.png`, `-01b-receipt-a.png`, `-02-cart-second.png`, `-02a-checkout-b.png`, `-02b-receipt-b.png`, `-03-no-migration.png`, `-04-two-active-subs.png`, `-05-cart-empty-after.png`.
- User/order/subscription IDs, cumulative count/bidirectional linkage, SUB1 before-after diff, order-2 meta dump, both offsets/actions/deadlines, `USER_PRE`, setup-mail ID, `MB08A/MB08B`, eight checkout-mail IDs, final cart/session/review proof.

## Pass criteria
- [ ] Both checkouts succeed at $10.00 and $24.00
- [ ] Two independent active subscriptions for one customer
- [ ] SUB1 product and recurring amount unchanged
- [ ] Zero migration UI rows and zero migration order/item meta
- [ ] `SUB2._recurring_amount = 9.00`, `_signup_fee = 15.00`
- [ ] No plan-switch mail; runtime gate conclusion captured without product-source inspection
- [ ] Setup mail isolated before `MB08A`; no customer account/password mail; final cart and persistent-cart meta empty
- [ ] Both complete four-message checkout sets and exact future action handoffs recorded; card reviewed to done

## Isolation / teardown
- No global setting written. Creates user `slt2-chk-second` — register it for SLT-SETUP-99B deletion.
- Handed on: SUB1 and SUB2 renew daily from 2026-08-28 (watch confirms 2026-08-29); both join the day/1 cohort cancelled by SLT-SETUP-99A.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
