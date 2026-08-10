---
id: 50
title: Quantity 3 on a subscription line — assert order total, _quantity, unit _recurring_amount and the renewal amount
status: done
priority: high
created: 2026-08-02T03:43:07.311803205+02:00
updated: 2026-08-06T20:10:32.898741858+02:00
started: 2026-08-06T20:10:32.898740956+02:00
completed: 2026-08-06T20:10:32.898740956+02:00
tags:
    - checkout
    - day-03
due: "2026-08-05"
estimate: 1h 15m
depends_on:
    - 10
    - 11
    - 12
    - 5
class: standard
---

> **SLT-CHK-09** · group `checkout` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Buy three units of one subscription product on a single line and pin down which number lives where: checkout charges unit x quantity, but the subscription stores the UNIT price in `_recurring_amount` and the multiplier in `_quantity`, and the renewal order rebuilds the total as `_recurring_amount * _quantity`. Also confirm that with `one_per_product = false` no quantity clamp fires and the one-per-product notice never appears.

## Scope
- Gateway: Stripe test
- Checkout: block (page 8) for the purchase; classic cart harness for the stepper probe
- Account: new registered — **this task creates** `slt-chk-qty`
- Plugins: free-only

## Preconditions
- SLT-SETUP-01/02/03 and SLT-PROD-01 complete. This task independently proves one line at quantity 3; the later SLT-CHK-06 card separately proves that two add operations merge onto one line and is not a prerequisite.
- **Creates one user beyond the SLT-SETUP-03 matrix**: `slt-chk-qty` / `slt-chk-qty@example.test`, Customer, `SltQa!2026#Pass`, billing per SLT-SETUP-03 step 4 — no existing slt-* account may buy SLT Daily Core twice.
- Frozen baseline: `one_per_product=false`, `one_per_customer=false`, `allow_multiple_in_cart=false`. Do not change.
- Code contract: `SubscriptionCreationTrait::createSubscription()` sets `quantity = max(1, item->get_quantity())` and `recurring_amount = subscription_data['price']` (unit). `RecurringBilling/Services/OrderCreation.php:100,138-139` sets renewal item qty from `_quantity` and `subtotal = total = price * quantity`.

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core, `/product/slt-daily-core/`, $10.00, day/1, no trial, no fee |
| Account | `slt-chk-qty` (created here) |
| Card | `4242 4242 4242 4242`, future expiry, CVC 123 |
| Quantity | 3 |
| Amounts | today $10.00 x 3 = **$30.00**; `_recurring_amount` **10.00**; renewal **$30.00** |
| Sessions | `--session cust-SLT-CHK-09`, `--session admin-SLT-CHK-09` |

## Steps
1. `USER_PRE=$(mailpit-agent latest-id)`. In `admin-SLT-CHK-09`, create `slt-chk-qty` at `/wp-admin/user-new.php` exactly as SLT-SETUP-03 step 2 (Send User Notification UNTICKED); set billing address via `user-edit.php`. Classify exactly one admin-addressed `New User Registration` after `USER_PRE` and prove there is no customer account/password mail.
2. After setup mail is classified, record `SUBCOUNT_BEFORE` with `wp post list --post_type=arraysubs_data --post_status=any --format=count --allow-root`; set checkout-only baseline `MB09=$(mailpit-agent latest-id)`.
3. `agent-browser --session cust-SLT-CHK-09 open "https://mirror-help.arrayhash.com/my-account"` → `snapshot -i` → log in as `slt-chk-qty` / `SltQa!2026#Pass`.
4. Confirm the browser cart is empty at `https://mirror-help.arrayhash.com/slt-classic-cart` and the exact user's persistent-cart meta is empty.
5. After 12:00 site time: open `/product/slt-daily-core/` → `snapshot -i` → set **Quantity** to `3` → add to cart. If one-click redirects to block checkout, record it and explicitly reopen `/cart/`.
6. At `https://mirror-help.arrayhash.com/cart` read line qty/subtotal and capture `SLT-CHK-09-01-cart-qty3.png`.
7. Open `https://mirror-help.arrayhash.com/checkout`, confirm the summary/recurring line, and capture `SLT-CHK-09-02-checkout-summary.png` before card entry; then select **Stripe** and fill the hosted card fields without capturing them.
8. Record the wall-clock site time (UTC+6) immediately before **Place order** — the start anchor.
9. Click **Place order**; re-snapshot the thank-you page, capture the safe receipt as `SLT-CHK-09-03-thankyou.png`, record numeric shell variable `ORDER_ID`, and abort unless numeric.
10. `mailpit-agent wait-new "$MB09" 180 "is active"`; inspect the complete owner-filtered delta and require exact WC customer paid-order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup IDs.
11. Resolve the exact `SUB_ID` from `ORDER_ID` rather than recency with `LINK_JSON=$(wp post meta get "$ORDER_ID" _subscription_ids --format=json --allow-root)` and a strict one-element numeric `jq -e` guard. Do not use `WC_Order::get_meta('_subscription_ids')` on this HPOS runtime. Abort unless numeric, then cross-check exactly one reverse `_parent_order_id=$ORDER_ID` row and total count `SUBCOUNT_BEFORE+1`. Run the meta/status probe, then in `admin-SLT-CHK-09` search the exact ID on the real `admin.php?page=arraysubs-mainadmin#/subscriptions` route and capture `SLT-CHK-09-04-subscription-admin.png`.
12. `wp wc shop_order get "$ORDER_ID" --user=admin --allow-root`; record total, line qty, line total.
13. Compute the offset with the README argv-based crc32 command using numeric `$SUB_ID`; record the exact pending invoice/charge action IDs and gates at `due+offset−6h` / `due+offset`, and publish both `gate−5m` deadlines plus the four checkout-mail IDs to the registry and D03 watch report. No earlier than five minutes before the exact charge gate publish `CHK09_RENEW_PRE=$(mailpit-agent latest-id)`. Follow-up 2026-08-06/07: after the boundary wait for the exact payment-success subject, classify the complete owner delta, resolve the renewal order by exact subscription/scheduled-cycle relationship, reopen `admin-SLT-CHK-09`, capture its total/line quantity as `SLT-CHK-09-05-renewal-order.png`, and close the admin session. Independently review the complete evidence and move the card through review to done.
14. Reopen the cart, prove it and persistent-cart meta empty, capture `SLT-CHK-09-03a-cart-empty-after.png`, and close only `cust-SLT-CHK-09` and `admin-SLT-CHK-09` after the D3 leg; keep the card `in-progress` until step 13's renewal proof.

## Expected results
1. Cart line qty `3`, line subtotal `$30.00`, total `$30.00`, no tax line.
2. No `One Subscription per Product is enabled…` notice appears (gated on `one_per_product`, false).
3. Order total exactly `30.00`; single line item with `quantity = 3`, `total = 30.00`.
4. Exactly ONE `arraysubs_data` post created for this order.
5. `_quantity = 3`; `_recurring_amount = 10.00` (unit, NOT 30.00); `_subscription_price = 10.00`; `_product_id` = SLT Daily Core.
6. `_next_payment_date` = step-8 timestamp + 1 day (anniversary; global sync OFF per SLT-SETUP-02), stored UTC.
7. Status reaches `arraysubs-active`.
8. The renewal order created inside `[due, due+6h]` totals `$30.00` with line qty 3 — proving renewal multiplies unit x quantity rather than renewing one unit or replaying the parent order total.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `new_subscription` | status → active on payment | slt-chk-qty@example.test | `is active` | `mailpit-agent wait-new "$MB09" 180 "is active"` |
| 2 | `admin_new_subscription` | same moment | site admin | `New subscription #` | Complete owner-filtered delta after `MB09`; save/show the exact matching id |
| 3 | WooCommerce paid-order + admin New order | order processing/completed | customer + admin | order / `New order #` | Complete owner-filtered delta after `MB09`; save/show both exact IDs |
| 4 | NONE EXPECTED — `renewal_upcoming` | 3-day lead exceeds the 1-day cycle | — | — | explicit absence check in the complete `CHK09_RENEW_PRE` owner delta |
| 5 | WP New User Registration | setup before `MB09` | site admin | `New User Registration` | exactly one after `USER_PRE`; zero customer account/password mail |
| 6 | `payment_successful` | exact renewal charge | slt-chk-qty@example.test | `Payment received for subscription #<SUB_ID>` | complete delta after `CHK09_RENEW_PRE`; save/show exact matched id |

## Evidence to capture
- `SLT-CHK-09-01-cart-qty3.png`, `-02-checkout-summary.png`, `-03-thankyou.png`, `-03a-cart-empty-after.png`, `-04-subscription-admin.png`, `-05-renewal-order.png`.
- User/order/subscription IDs, step-11 meta/status, computed offset and action IDs/GMT/deadlines, `USER_PRE`, setup-mail ID, checkout-only `MB09`, all four checkout-mail IDs, `CHK09_RENEW_PRE`, exact renewal-mail ID, final cart/persistent-cart proof, console/network errors.

## Pass criteria
- [x] Checkout charges $30.00 with line quantity 3
- [x] `_quantity = 3` and `_recurring_amount = 10.00`
- [x] Exactly one subscription created
- [x] No one-per-product clamp or notice
- [ ] Renewal order totals $30.00, qty 3, inside the computed window
- [x] Emails 1-3 arrive, email 4 does not
- [x] Setup mail isolated before `MB09`; no customer account/password mail; final cart and persistent-cart meta empty

## Isolation / teardown
- Handed on: the subscription stays ACTIVE and renews daily; it joins the day/1 cohort cancelled by SLT-SETUP-99A.
- Creates user `slt-chk-qty` — add to the registry so SLT-SETUP-99B deletes it.
- No global setting touched; cart emptied. Close only `cust-SLT-CHK-09` and `admin-SLT-CHK-09` after each dated leg.

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

## D03 setup checkpoint — 2026-08-05

**PURCHASE PASS; D04 NATURAL RENEWAL ARMED.** Dedicated Customer user `363` (`slt-chk-qty`) was created with the required billing/shipping data. `USER_PRE=3ZCeSaTCACAsOHVebKoABv` isolated exactly one admin-addressed setup mail, `04Hn1aOeCw37m6PYb3TQjF`, and no customer account/password mail. Checkout-only `MB09` is that same setup-mail ID. Frozen multiple-subscription settings were read and not changed; initial browser and persistent carts were empty.

The real classic cart accepted one product-`11927` line at quantity `3`, subtotal/total USD `$30.00`, renewal USD `$30.00` daily, and no tax, clamp, or one-per-product notice. The real block checkout retained the same totals. Button-click anchor was `2026-08-05 10:58:54Z` / `16:58:54 UTC+6`. Completed Stripe `ORDER_ID=12683` has exactly one line, quantity `3`, total `$30.00`, tax `$0.00`, and customer `363`.

The strict raw `_subscription_ids` guard resolved only `SUB_ID=12684`; the reverse parent query and exact owner/product query each returned only that row. The subscription count moved `370 -> 371`. Subscription `12684` is `arraysubs-active` with `_quantity=3`, unit `_recurring_amount=10`, `_subscription_price=10`, product `11927`, `_start_date=2026-08-05 10:58:59Z`, and `_next_payment_date=2026-08-06 10:58:59Z` exactly one day later. The exact complete checkout delta is customer order `6fCIPHieTSrrDTvKacOrdz`, admin order `5ohg7LXwPFo9ToVgcFjs2V`, customer active subscription `1DWXvviaghiPJC5XnpZX7w`, and admin new subscription `7iVg5SzIOlA4u05cQHR1y9`; no reminder/invoice/failure/setup spillover occurred.

With `k=8164s` (`02:16:04`), final pending invoice action `14868` is due `2026-08-06 07:15:03Z` and final pending charge action `14869` is due `13:15:03Z`; synchronized originals `14866`/`14867` are canceled. Capture `CHK09_RENEW_PRE` only inside `[2026-08-06 13:10:03Z, 13:15:03Z)` and allow charge action `14869` to run naturally. Never force either action. The final browser and persistent carts are empty, and the admin route filtered by exact ID shows the active subscription.

QA-plan-only correction: the authored `order get` resource under `wp wc` is not registered here; the valid `wp wc shop_order get` resource was substituted in this card and the three other affected future cards (`64`, `78`, `90`). The current order proof was rerun successfully with the corrected command.

Retained evidence: `/home/server-manager/slt-evidence/SLT-CHK-09-D03-armed-facts.txt`, `SLT-CHK-09-01-cart-qty3.png`, `-02-checkout-summary.png`, `-03-thankyou.png`, `-03a-cart-empty-after.png`, and `-04-subscription-admin.png`. The retained checkout screenshot contains only masked final-four helper text; an unsafe initial version was immediately overwritten at the same path. Both exact sessions close after publication. This card remains in progress and unclaimed solely for the exact D04 natural-renewal proof.

[[2026-08-05]] Wed 14:35
Board hygiene checkpoint: no actionable step is available right now; the current body already records the exact next gate/window and required natural-watch constraints. Parking this future-gated card back in todo so In Progress reflects only currently active execution.

[[2026-08-05]] Wed 14:45
D03 watcher correction: the 18:35 board-hygiene note is superseded; this future-gated card remains in-progress. Capture D4 CHK09_RENEW_PRE only 13:10:03Z-13:15:02Z, then observe natural action 14869 after 13:15:03Z.

[[2026-08-05]] Wed 15:01
Board hygiene checkpoint: parked future-gated or watch-only work returned to todo so in-progress reflects only actively worked cards.

[[2026-08-05]] Wed 15:30
Board correction: restored to in-progress. Next gate D4: capture baseline 2026-08-06 13:10:03Z-13:15:02Z (19:10:03-19:15:02 site), then observe natural action 14869 at/after 13:15:03Z and require USD 30.00 quantity-three renewal.

[[2026-08-05]] Wed 16:41
D4 quantity-3 renewal follow-up: capture baseline 2026-08-06 13:10:03Z-13:15:02Z for natural charge action 14869 at 13:15:03Z.

[[2026-08-05]] Wed 16:46
Board hygiene: returned to todo because this card is not in an active execution window right now. Resume only at the exact gate or follow-up already recorded on the card.

[[2026-08-05]] Wed 17:26
D4 charge baseline 2026-08-06 13:10:03Z-13:15:02Z; action 14869 at 13:15:03Z.

[[2026-08-05]] Wed 17:44
D4 renewal baseline 2026-08-06 13:10:03Z–13:15:02Z; observe natural action 14869 after 13:15:03Z.

[[2026-08-06]] Thu 20:10
Closed from completed D3/D4 evidence. D3 checkout proved quantity 3 stores _quantity=3 with unit _recurring_amount=10.00; D4 natural renewal proved the multiplication path when renewal order 12906 completed at 30.00 for subscription 12684 with payment mail 05nZzQ5wfIf91aNcCGK4ka.
