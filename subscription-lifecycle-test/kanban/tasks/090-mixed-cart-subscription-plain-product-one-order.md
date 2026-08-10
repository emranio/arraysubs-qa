---
id: 90
title: 'Mixed cart: subscription + plain product — one order, only the subscription line creates a subscription, only it renews'
status: done
priority: high
created: 2026-08-02T03:43:10.686233217+02:00
updated: 2026-08-09T18:51:43.045416041+02:00
started: 2026-08-09T18:51:42.967020871+02:00
completed: 2026-08-09T18:51:42.967020871+02:00
tags:
    - checkout
    - day-06
due: "2026-08-08"
estimate: 1h 15m
depends_on:
    - 10
    - 11
    - 12
    - 5
    - 39
    - 77
class: standard
---

> **SLT-CHK-07** · group `checkout` · scheduled **D06** (2026-08-08)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Place a real mixed-cart ORDER — one recurring line, one plain line. Prove `allow_mixed_cart=true` lets both coexist, checkout charges the combined total once, exactly one subscription is created from the subscription line only, and the renewal carries that line alone. Closes the audit gap at SLT-CHK-09 ("nothing places a mixed-cart ORDER").

## Scope
- Gateway: Stripe test
- Checkout: block (page 8)
- Account: new registered — **this task creates** `slt-chk-mixed`
- Plugins: free-only

## Preconditions
- SLT-SETUP-01/02/03, SLT-PROD-01 (`SLT Daily Core`), SLT-PROD-09 (`SLT Grouped Extra`, plain simple $3.00) complete. SLT-CHK-06 established the rejection string that must NOT appear here.
- **Creates one user beyond the SLT-SETUP-03 matrix**: `slt-chk-mixed` / `slt-chk-mixed@example.test`, Customer, `SltQa!2026#Pass`, billing address per SLT-SETUP-03 step 4 — no existing slt-* account may buy SLT Daily Core twice.
- Frozen baseline, do not change: `allow_mixed_cart=true`, `allow_multiple_in_cart=false`, `one_per_customer=false`, `one_per_product=false`.
- Code contract: `CartValidation::getCartValidationErrors()` emits the mixed-cart message only when `!$allow_mixed_cart && subscription_count>0 && regular_count>0`; subscription creation skips non-subscription line items.

## Test data
| Item | Value |
|---|---|
| Subscription line | SLT Daily Core, `/product/slt-daily-core/`, $10.00, day/1 |
| Plain line | SLT Grouped Extra, `/product/slt-grouped-extra/`, $3.00, one-off |
| Account | `slt-chk-mixed` (created here) |
| Card | `4242 4242 4242 4242` |
| Amounts | today **$13.00**; renewal **$10.00** |
| Session | `--session cust-SLT-CHK-07` |

## Steps
1. Resolve strict numeric, distinct `SUB_PRODUCT_ID`/`PLAIN_PRODUCT_ID`, verify their subscription/plain flags and prices, and record `SUBCOUNT_BEFORE`/`ORDERCOUNT_BEFORE`. Set `USER_PRE`; in `admin-SLT-CHK-07` create `slt-chk-mixed`, record numeric `USER_ID`/Customer role and billing, classify exactly one admin-only registration message, and prove no customer account/password mail.
2. After setup mail is classified, set checkout-only baseline `MB07=$(mailpit-agent latest-id)`.
3. In `cust-SLT-CHK-07`, log in, require zero owned subscriptions and both carts empty, and capture `SLT-CHK-07-00-cart-empty-before.png`.
4. Add only `$SUB_PRODUCT_ID`; if one-click redirects to checkout, do not proceed and explicitly open the plain product next while preserving the task cart.
5. Add only `$PLAIN_PRODUCT_ID`, handle any redirect, reopen `/cart`, and require both exact lines with neither rejection string.
6. Capture the two-line $13 cart as `SLT-CHK-07-01-mixed-cart.png`.
7. Open `/checkout`, capture the unpopulated two-line summary as `SLT-CHK-07-02-checkout-summary.png`; never capture populated card fields.
8. Record site time, fill the hosted card without capturing it, pay, record strict numeric `ORDER_ID`, and capture safe receipt `SLT-CHK-07-03-thankyou.png`.
9. Poll immutable `MB07` in ≤60-second calls through the three-minute cutoff, then require the complete four-message delta: WC customer paid-order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup.
10. Run `wp wc shop_order get "$ORDER_ID" --user=admin --allow-root`, record exact total/two items, and capture them in `admin-SLT-CHK-07` as `SLT-CHK-07-04-order-lines.png`; require `ORDERCOUNT_AFTER == ORDERCOUNT_BEFORE+1`.
11. Read `LINK_JSON=$(wp post meta get "$ORDER_ID" _subscription_ids --format=json --allow-root)`, resolve exactly one subscription through a strict numeric `jq -e` guard, cross-check `_parent_order_id`, customer and product, assign it to shell variable `SUB_ID`, and abort unless `[[ "$SUB_ID" =~ ^[0-9]+$ ]]`. Do not use `WC_Order::get_meta('_subscription_ids')` or recency. Run `wp post meta list "$SUB_ID" --keys=_product_id,_recurring_amount,_quantity,_next_payment_date,_status,_parent_order_id --allow-root`.
12. `wp db query "SELECT post_id FROM wp_postmeta WHERE meta_key='_parent_order_id' AND meta_value='$ORDER_ID'" --allow-root` must return exactly the same `$SUB_ID` and no other row.
13. Require `SUBCOUNT_AFTER == SUBCOUNT_BEFORE+1`, compute offset/action IDs/gates with numeric `$SUB_ID`, and publish the exact `gate−5m` deadline.
14. Prove both carts empty, capture `SLT-CHK-07-04a-cart-empty-after.png`, close the D6 customer/admin sessions, and leave the card `in-progress`. Take `REN_PRE` only inside `[exact charge gate−300s, gate)`, never force, and poll in ≤60-second calls through the cutoff. Resolve the renewal order by exact subscription/cycle plus reverse meta, capture its sole line as `SLT-CHK-07-05-renewal-single-line.png` in `admin-SLT-CHK-07-R1`, reconcile the complete delta, and close the R1 session.
15. If any live assertion fails, create a standalone `issues/SLT-CHK-07-<concise-slug>.md` (never a kanban bug card) with task/stage/plan, user/product/parent/renewal/subscription/action IDs, login/email/role, exact URLs/sessions/gates, reproduction, expected/actual, UI/HPOS/meta/Mailpit/screenshot proof, and the other line as counterexample. After renewal, independently review all evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. The plain product is accepted into a cart already holding a subscription; no ArraySubs notice at step 5.
2. Cart at step 6: two lines, `$10.00` + `$3.00`, total `$13.00`, no tax line.
3. Exactly ONE order (WooCommerce does not split mixed carts); total `13.00`, two line items.
4. Exactly one `arraysubs_data` post has `_parent_order_id = $ORDER_ID`; `_product_id` = numeric SLT Daily Core, `_recurring_amount = 10.00`, `_quantity = 1`.
5. No subscription for SLT Grouped Extra; its line item carries no `_arraysubs_*` meta.
6. `_next_payment_date` = step-8 timestamp + 1 day (anniversary; global sync OFF per SLT-SETUP-02).
7. The renewal order created inside `[due, due+6h]` on 2026-08-09 totals **$10.00** with ONE line item, qty 1. Any $3.00 line on the renewal is a defect — write a standalone markdown file under `issues/`, never a lifecycle-board card.
8. Subscription reaches `arraysubs-active`; parent order reaches `processing`/`completed`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC customer paid-order + WC New order + `new_subscription` + `admin_new_subscription` | parent paid | customer/admin | exact order/subscription | complete owner-filtered `MB07` delta; save/show all four IDs |
| 2 | `payment_successful` | renewal charge leg next day | customer | exact numeric `$SUB_ID` | final-five-minute `REN_PRE`, ≤60-second polling, complete delta |
| 3 | NONE EXPECTED — second `new_subscription` for the plain product | payment | — | — | exactly one `is active` mail for this order |
| 4 | WP New User Registration | setup before `MB07` | admin | `New User Registration` | exactly one after `USER_PRE`; zero customer account/password mail |

## Evidence to capture
- Safe named `SLT-CHK-07-00` through `-05` captures; numeric user/product/parent/renewal/subscription/action IDs and count/bidirectional linkage; step-12 SQL result, offset/gates/deadline, setup/checkout/renewal baselines and exact Mailpit IDs, carts, console/network, sessions/review proof.

## Pass criteria
- [ ] Mixed cart accepted with no ArraySubs notice
- [ ] Single order totalling $13.00 with two line items
- [ ] Exactly one subscription, from the subscription line only
- [ ] `_recurring_amount = 10.00`
- [ ] Renewal order totals $10.00, one line item, inside the computed window
- [ ] Complete four-message checkout and exact renewal mail sets arrive; no second subscription/signup mail
- [ ] Setup mail isolated before `MB07`; no customer account/password mail; final cart and persistent-cart meta empty
- [ ] +1 order/+1 subscription relationships exact, sessions closed, standalone findings only, final evidence reviewed to done

## Isolation / teardown
- Handed on: the subscription stays ACTIVE and renews daily; joins the day/1 cohort cancelled by SLT-SETUP-99A.
- Creates user `slt-chk-mixed`; register it for SLT-SETUP-99B deletion. No global setting changed; cart emptied, session closed. `SLT Grouped Extra` is purchased, never edited.

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

[[2026-08-08]] Sat 15:52
D6 purchase leg PASS. Created Customer 368 (slt-chk-mixed), parent order 13321 and relationship-resolved sole subscription 13331. Order is Stripe-test processing for USD 13.00 with exact lines Daily Core 11927 x1 USD 10 and plain Grouped Extra 12583 x1 USD 3; reverse parent relationship exact; counts +1 order/+1 subscription; plain line created none. MB07=3krXNks0xc1TKkQEVuDk1b; exact four checkout mails 1w2hoRmhksmtDZiZUIPhbM, 3NF06HNkpnhV3wbdCEL3T7, 3uuUduoZGw42wTvBmxheig, 5b8qiXy0QkUsv3k1hfOqzc. Cart and persistent cart empty, task sessions closed. Evidence: /home/server-manager/slt-evidence/SLT-CHK-07-D06-purchase.txt and screenshots 00 through 04a. Card stays in-progress. D7 final pending invoice action 16074 at 2026-08-09 10:40:02Z / 16:40:02 site; charge action 16075 at 16:40:02Z / 22:40:02 site. Capture REN_PRE only 16:35:02Z-16:40:01Z / 22:35:02-22:40:01 site, then prove one-line USD 10 renewal; never force.

[[2026-08-09]] Sun 18:51
D07 follow-up PASS at the authored 22:40:02 gate. REN_PRE=2gFM3pYjPz1sgKF50Triwz at 22:35:02 site; action 16075 completed once via WP Cron; unique order 13488 completed USD 10.00 with sole product-11927 qty1 line and zero product-12583/fees/coupons/tax; sub 13331 advanced payments 1->2 and queued 16521/16522; exact mails 5q0QCefjWuW1l1VfosWgEM + 7ab3FZRTZTYaiAIOBTBgkZ, no duplicate through 22:50:02. Browser screenshot /home/server-manager/slt-evidence/SLT-CHK-07-05-renewal-single-line.png; complete follow-up /home/server-manager/slt-evidence/SLT-CHK-07-D07-renewal.txt. D6 setup leg was not repeated.
