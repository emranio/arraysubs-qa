---
id: 90
title: 'Mixed cart: subscription + plain product — one order, only the subscription line creates a subscription, only it renews'
status: todo
priority: high
created: 2026-08-02T03:43:10.686233217+02:00
updated: 2026-08-02T03:43:21.759018567+02:00
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
| Subscription line | SLT Daily Core, `/slt-daily-core`, $10.00, day/1 |
| Plain line | SLT Grouped Extra, `/slt-grouped-extra`, $3.00, one-off |
| Account | `slt-chk-mixed` (created here) |
| Card | `4242 4242 4242 4242` |
| Amounts | today **$13.00**; renewal **$10.00** |
| Session | `--session cust-SLT-CHK-07` |

## Steps
1. Create `slt-chk-mixed` per SLT-SETUP-03 step 2 (Send User Notification UNTICKED); set billing address.
2. `mailpit-agent latest-id` → `MB07`.
3. `agent-browser --session cust-SLT-CHK-07 open "https://mirror-help.arrayhash.com/my-account"` → log in. Confirm Subscriptions and cart are empty.
4. After 12:00 site time: open `/slt-daily-core` → add to cart.
5. Open `/slt-grouped-extra` → add to cart → re-snapshot. **Neither the SLT-CHK-06 multiple-plans string nor the mixed-cart string may appear.**
6. Open `https://mirror-help.arrayhash.com/cart` → `snapshot -i`: two lines, subtotal `$13.00`. Screenshot.
7. Open `https://mirror-help.arrayhash.com/checkout` → `snapshot -i`: both lines, recurring text on the subscription line only. Screenshot.
8. Record wall-clock site time (UTC+6), pay, **Place order**. Record the order id.
9. `mailpit-agent wait-new MB07 180 "is active"`.
10. `wp wc order get <ORDER_ID> --user=admin --allow-root` — record total and both line items.
11. `wp post meta list <SUB_ID> --keys=_product_id,_recurring_amount,_quantity,_next_payment_date,_status,_parent_order_id --allow-root`.
12. `wp db query "SELECT post_id FROM wp_postmeta WHERE meta_key='_parent_order_id' AND meta_value='<ORDER_ID>'" --allow-root` must return one row.
13. Compute `offset = crc32('arraysubs-spread-'.<SUB_ID>) % 21600` (SLT-REF-01); record invoice window `due+offset-6h` and charge window `due+offset`.
14. Follow-up 2026-08-09/10 (daily watch): open the renewal order for `<SUB_ID>`, screenshot its line items, record its total.
15. Empty the cart; close the session.

## Expected results
1. The plain product is accepted into a cart already holding a subscription; no ArraySubs notice at step 5.
2. Cart at step 6: two lines, `$10.00` + `$3.00`, total `$13.00`, no tax line.
3. Exactly ONE order (WooCommerce does not split mixed carts); total `13.00`, two line items.
4. Exactly ONE `arraysubs_data` post has `_parent_order_id = <ORDER_ID>`; `_product_id` = SLT Daily Core, `_recurring_amount = 10.00`, `_quantity = 1`.
5. No subscription for SLT Grouped Extra; its line item carries no `_arraysubs_*` meta.
6. `_next_payment_date` = step-8 timestamp + 1 day (anniversary; global sync OFF per SLT-SETUP-02).
7. The renewal order created inside `[due, due+6h]` on 2026-08-09 totals **$10.00** with ONE line item, qty 1. Any $3.00 line on the renewal is a defect — file it.
8. Subscription reaches `arraysubs-active`; parent order reaches `processing`/`completed`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `new_subscription` | activation on payment | slt-chk-mixed@example.test | `is active` | `mailpit-agent wait-new MB07 180 "is active"` |
| 2 | `admin_new_subscription` | same moment | site admin | `New subscription #` | `mailpit-agent list 20` |
| 3 | WooCommerce order email | processing/completed | customer | `order` | `mailpit-agent list 20` |
| 4 | `payment_successful` | renewal charge leg next day | customer | `Payment received for subscription #` | daily watch 2026-08-10 |
| 5 | NONE EXPECTED — second `new_subscription` for the plain product | payment | — | — | exactly ONE `is active` mail for this order |

## Evidence to capture
- `SLT-CHK-07-01-mixed-cart.png`, `-02-checkout-summary.png`, `-03-thankyou.png`, `-04-order-lines.png`, `-05-renewal-single-line.png`.
- Order/subscription ids, step-12 SQL result, computed offset and windows, Mailpit ids, console/network errors.

## Pass criteria
- [ ] Mixed cart accepted with no ArraySubs notice
- [ ] Single order totalling $13.00 with two line items
- [ ] Exactly one subscription, from the subscription line only
- [ ] `_recurring_amount = 10.00`
- [ ] Renewal order totals $10.00, one line item, inside the computed window
- [ ] Emails 1-4 arrive; email 5 does not

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
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence before forcing.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run `wp action-scheduler run` without `--hooks=`; prefer a single action by ID.
- Evidence goes in `qa/subscription-lifecycle-test/evidence/<TASK-KEY>/`.
