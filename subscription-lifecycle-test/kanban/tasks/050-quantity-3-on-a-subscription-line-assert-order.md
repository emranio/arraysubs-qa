---
id: 50
title: Quantity 3 on a subscription line — assert order total, _quantity, unit _recurring_amount and the renewal amount
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - checkout
    - day-03
due: "2026-08-26"
estimate: 1h 15m
depends_on:
    - 10
    - 11
    - 12
    - 5
class: standard
---

> **SLT-CHK-09** · group `checkout` · scheduled **D03** (2026-08-26)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Buy three units of one subscription product on a single line and pin down which number lives where: checkout charges unit x quantity, but the subscription stores the UNIT price in `_recurring_amount` and the multiplier in `_quantity`, and the renewal order rebuilds the total as `_recurring_amount * _quantity`. Also confirm that with `one_per_product = false` no quantity clamp fires and the one-per-product notice never appears.

## Scope
- Gateway: Stripe test
- Checkout: block (page 8) for the purchase; classic cart harness for the stepper probe
- Account: new registered — **this task creates** `slt2-chk-qty`
- Plugins: free-only

## Preconditions
- SLT-SETUP-01/02/03 and SLT-PROD-01 complete. This task independently proves one line at quantity 3; the later SLT-CHK-06 card separately proves that two add operations merge onto one line and is not a prerequisite.
- **Creates one user beyond the SLT-SETUP-03 matrix**: `slt2-chk-qty` / `slt2-chk-qty@example.test`, Customer, `SltQa!2026#Pass`, billing per SLT-SETUP-03 step 4 — no existing slt2-* account may buy SLT2 Daily Core twice.
- Frozen baseline: `one_per_product=false`, `one_per_customer=false`, `allow_multiple_in_cart=false`. Do not change.
- Code contract: `SubscriptionCreationTrait::createSubscription()` sets `quantity = max(1, item->get_quantity())` and `recurring_amount = subscription_data['price']` (unit). `RecurringBilling/Services/OrderCreation.php:100,138-139` sets renewal item qty from `_quantity` and `subtotal = total = price * quantity`.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Daily Core, `/product/slt2-daily-core/`, $10.00, day/1, no trial, no fee |
| Account | `slt2-chk-qty` (created here) |
| Card | `4242 4242 4242 4242`, future expiry, CVC 123 |
| Quantity | 3 |
| Amounts | today $10.00 x 3 = **$30.00**; `_recurring_amount` **10.00**; renewal **$30.00** |
| Sessions | `--session cust-SLT-CHK-09`, `--session admin-SLT-CHK-09` |

## Steps
1. `USER_PRE=$(mailpit-agent latest-id)`. In `admin-SLT-CHK-09`, create `slt2-chk-qty` at `/wp-admin/user-new.php` exactly as SLT-SETUP-03 step 2 (Send User Notification UNTICKED); set billing address via `user-edit.php`. Classify exactly one admin-addressed `New User Registration` after `USER_PRE` and prove there is no customer account/password mail.
2. After setup mail is classified, record `SUBCOUNT_BEFORE` with `wp post list --post_type=arraysubs_data --post_status=any --format=count --allow-root`; set checkout-only baseline `MB09=$(mailpit-agent latest-id)`.
3. `agent-browser --session cust-SLT-CHK-09 open "https://mirror-help.arrayhash.com/my-account"` → `snapshot -i` → log in as `slt2-chk-qty` / `SltQa!2026#Pass`.
4. Confirm the browser cart is empty at `https://mirror-help.arrayhash.com/slt2-classic-cart` and the exact user's persistent-cart meta is empty.
5. After 12:00 site time: open `/product/slt2-daily-core/` → `snapshot -i` → set **Quantity** to `3` → add to cart. If one-click redirects to block checkout, record it and explicitly reopen `/cart/`.
6. At `https://mirror-help.arrayhash.com/cart` read line qty/subtotal and capture `SLT-CHK-09-01-cart-qty3.png`.
7. Open `https://mirror-help.arrayhash.com/checkout`, confirm the summary/recurring line, and capture `SLT-CHK-09-02-checkout-summary.png` before card entry; then select **Stripe** and fill the hosted card fields without capturing them.
8. Record the wall-clock site time (UTC+6) immediately before **Place order** — the start anchor.
9. Click **Place order**; re-snapshot the thank-you page, capture the safe receipt as `SLT-CHK-09-03-thankyou.png`, record numeric shell variable `ORDER_ID`, and abort unless numeric.
10. `mailpit-agent wait-new "$MB09" 180 "is active"`; inspect the complete owner-filtered delta and require exact WC customer paid-order, WC admin New order, ArraySubs customer signup, and ArraySubs admin signup IDs.
11. Resolve the exact `SUB_ID` from `ORDER_ID` rather than recency with `LINK_JSON=$(wp post meta get "$ORDER_ID" _subscription_ids --format=json --allow-root)` and a strict one-element numeric `jq -e` guard. Do not use `WC_Order::get_meta('_subscription_ids')` on this HPOS runtime. Abort unless numeric, then cross-check exactly one reverse `_parent_order_id=$ORDER_ID` row and total count `SUBCOUNT_BEFORE+1`. Run the meta/status probe, then in `admin-SLT-CHK-09` search the exact ID on the real `admin.php?page=arraysubs-mainadmin#/subscriptions` route and capture `SLT-CHK-09-04-subscription-admin.png`.
12. `wp wc shop_order get "$ORDER_ID" --user=admin --allow-root`; record total, line qty, line total.
13. Compute the offset with the README argv-based crc32 command using numeric `$SUB_ID`; record the exact pending invoice/charge action IDs and gates at `due+offset−6h` / `due+offset`, and publish both `gate−5m` deadlines plus the four checkout-mail IDs to the registry and D03 watch report. No earlier than five minutes before the exact charge gate publish `CHK09_RENEW_PRE=$(mailpit-agent latest-id)`. Follow-up 2026-08-27/07: after the boundary wait for the exact payment-success subject, classify the complete owner delta, resolve the renewal order by exact subscription/scheduled-cycle relationship, reopen `admin-SLT-CHK-09`, capture its total/line quantity as `SLT-CHK-09-05-renewal-order.png`, and close the admin session. Independently review the complete evidence and move the card through review to done.
14. Reopen the cart, prove it and persistent-cart meta empty, capture `SLT-CHK-09-03a-cart-empty-after.png`, and close only `cust-SLT-CHK-09` and `admin-SLT-CHK-09` after the D3 leg; keep the card `in-progress` until step 13's renewal proof.

## Expected results
1. Cart line qty `3`, line subtotal `$30.00`, total `$30.00`, no tax line.
2. No `One Subscription per Product is enabled…` notice appears (gated on `one_per_product`, false).
3. Order total exactly `30.00`; single line item with `quantity = 3`, `total = 30.00`.
4. Exactly ONE `arraysubs_data` post created for this order.
5. `_quantity = 3`; `_recurring_amount = 10.00` (unit, NOT 30.00); `_subscription_price = 10.00`; `_product_id` = SLT2 Daily Core.
6. `_next_payment_date` = step-8 timestamp + 1 day (anniversary; global sync OFF per SLT-SETUP-02), stored UTC.
7. Status reaches `arraysubs-active`.
8. The renewal order created inside `[due, due+6h]` totals `$30.00` with line qty 3 — proving renewal multiplies unit x quantity rather than renewing one unit or replaying the parent order total.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `new_subscription` | status → active on payment | slt2-chk-qty@example.test | `is active` | `mailpit-agent wait-new "$MB09" 180 "is active"` |
| 2 | `admin_new_subscription` | same moment | site admin | `New subscription #` | Complete owner-filtered delta after `MB09`; save/show the exact matching id |
| 3 | WooCommerce paid-order + admin New order | order processing/completed | customer + admin | order / `New order #` | Complete owner-filtered delta after `MB09`; save/show both exact IDs |
| 4 | NONE EXPECTED — `renewal_upcoming` | 3-day lead exceeds the 1-day cycle | — | — | explicit absence check in the complete `CHK09_RENEW_PRE` owner delta |
| 5 | WP New User Registration | setup before `MB09` | site admin | `New User Registration` | exactly one after `USER_PRE`; zero customer account/password mail |
| 6 | `payment_successful` | exact renewal charge | slt2-chk-qty@example.test | `Payment received for subscription #<SUB_ID>` | complete delta after `CHK09_RENEW_PRE`; save/show exact matched id |

## Evidence to capture
- `SLT-CHK-09-01-cart-qty3.png`, `-02-checkout-summary.png`, `-03-thankyou.png`, `-03a-cart-empty-after.png`, `-04-subscription-admin.png`, `-05-renewal-order.png`.
- User/order/subscription IDs, step-11 meta/status, computed offset and action IDs/GMT/deadlines, `USER_PRE`, setup-mail ID, checkout-only `MB09`, all four checkout-mail IDs, `CHK09_RENEW_PRE`, exact renewal-mail ID, final cart/persistent-cart proof, console/network errors.

## Pass criteria
- [ ] Checkout charges $30.00 with line quantity 3
- [ ] `_quantity = 3` and `_recurring_amount = 10.00`
- [ ] Exactly one subscription created
- [ ] No one-per-product clamp or notice
- [ ] Renewal order totals $30.00, qty 3, inside the computed window
- [ ] Emails 1-3 arrive, email 4 does not
- [ ] Setup mail isolated before `MB09`; no customer account/password mail; final cart and persistent-cart meta empty

## Isolation / teardown
- Handed on: the subscription stays ACTIVE and renews daily; it joins the day/1 cohort cancelled by SLT-SETUP-99A.
- Creates user `slt2-chk-qty` — add to the registry so SLT-SETUP-99B deletes it.
- No global setting touched; cart emptied. Close only `cust-SLT-CHK-09` and `admin-SLT-CHK-09` after each dated leg.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
