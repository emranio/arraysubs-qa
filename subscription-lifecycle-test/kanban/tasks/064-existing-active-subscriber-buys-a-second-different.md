---
id: 64
title: Existing active subscriber buys a second, different subscription — auto_migrate_on_checkout is gated off; document what migration would do
status: todo
priority: high
created: 2026-08-02T03:43:08.695080419+02:00
updated: 2026-08-02T03:43:19.117927786+02:00
tags:
    - checkout
    - day-04
    - has-conflicts
due: "2026-08-06"
estimate: 1h 30m
depends_on:
    - 10
    - 11
    - 12
    - 5
    - 58
class: standard
---

> **SLT-CHK-08** · group `checkout` · scheduled **D04** (2026-08-06)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · shared-global-setting / undeclared exclusive bracket** — with `SLT-EML-13`, `SLT-CHK-13`, `SLT-SYN-07`, `SLT-SYN-11`, `SLT-SW-09`, `SLT-IMP-03`

- *Problem:* SLT-EML-13 (d4) disables all four ArraySubs admin emails site-wide for a bracket it bounds only as '08:00-09:00 site, under 20 min'. D4 (2026-08-06) carries the heaviest checkout load of the middle of the window: SLT-CHK-08 places two checkouts, SLT-SYN-11 three, SLT-IMP-03 three, SLT-SW-09 two, plus SLT-CHK-13 and SLT-SYN-07. Every admin_new_subscription for a checkout inside the bracket is silently lost, and those tasks' email tables assert it as present. SLT-ADM-03/ADM-05 also drive status transitions on D4 whose admin notifications would vanish. Conversely, if any of those checkouts drifts into the bracket, EML-13's own 'exactly one message' silence proof is contaminated by their customer mail.
- *Required fix:* Fix the bracket at 08:00-08:20 site on D4 and make it the FIRST thing that happens that day - before any product save, cart, checkout or status change. Add a pre-flight step (already half-present as step 1): screenshot Tools -> Scheduled Actions Pending for the next 2h and abort if any renewal/retry/overdue/cancel action is due, AND assert no SLT checkout task is in-progress on the board. Publish the open/close UTC to the registry. Add 'no checkout before 08:30 site on D4' to the D4 row of the calendar.

---
## Objective
Put one customer through two checkouts for two different SLT subscription products and record what `auto_migrate_on_checkout = true` actually does. `arraysubs_can_auto_migrate_subscription_on_checkout()` (`settings-helpers.php:777-784`) returns **false** unless `arraysubs_is_one_subscription_per_customer()` is true, and this site has `one_per_customer = false`, so auto-migrate is INERT: the second checkout creates a second independent subscription and never touches the first.

## Scope
- Gateway: Stripe test
- Checkout: block (page 8), both purchases
- Account: new registered — **this task creates** `slt-chk-second`
- Plugins: free-only

## Preconditions
- SLT-SETUP-01/02/03, SLT-PROD-01 (`SLT Daily Core`), SLT-PROD-04 (`SLT Signup Fee Daily`) complete.
- **Creates one user beyond the SLT-SETUP-03 matrix**: `slt-chk-second` / `slt-chk-second@example.test`, Customer, `SltQa!2026#Pass`, billing per SLT-SETUP-03 step 4 — migration short-circuits above one live subscription, and every existing slt-* account holds several or is reserved.
- **Change NO global setting.** Writes to `arraysubs_settings` are reserved to SLT-SETUP-02/SYN-04/99A; flipping `one_per_customer` would corrupt every concurrent cart on this shared site.

## Test data
| Item | Value |
|---|---|
| Purchase 1 | SLT Daily Core, $10.00 day/1 → charge **$10.00** |
| Purchase 2 | SLT Signup Fee Daily, $9.00 day/1 + $15.00 fee → charge **$24.00** |
| Account | `slt-chk-second` (created here) |
| Card | `4242 4242 4242 4242` |
| Session | `--session cust-SLT-CHK-08` |

## Steps
1. Create `slt-chk-second` per SLT-SETUP-03 step 2 (Send User Notification UNTICKED); set billing address.
2. `mailpit-agent latest-id` → `MB08A`. `agent-browser --session cust-SLT-CHK-08 open "https://mirror-help.arrayhash.com/my-account"` → log in; confirm Subscriptions and cart empty.
3. After 12:00 site time: `/slt-daily-core` → add to cart → `/checkout` → pay. Record order 1 id and wall-clock time.
4. `mailpit-agent wait-new MB08A 180 "is active"`. Record `SUB1`; `wp post meta list SUB1 --keys=_product_id,_recurring_amount,_quantity,_next_payment_date,_status --allow-root`.
5. `mailpit-agent latest-id` → `MB08B`. Confirm the cart is empty.
6. `/slt-signup-fee-daily` → add to cart → `/cart` → `snapshot -i`. **Read every cart-item meta row.** Screenshot.
7. `/checkout` → `snapshot -i`. **Read every order-summary row**, then pay. Record order 2 id.
8. `mailpit-agent wait-new MB08B 180 "is active"`. Record `SUB2` and the same meta dump.
9. Re-dump SUB1's meta; diff field by field against step 4.
10. `wp wc order get <ORDER2_ID> --user=admin --allow-root`; `wp db query "SELECT meta_key,meta_value FROM wp_wc_orders_meta WHERE order_id=<ORDER2_ID>" --allow-root`.
11. Record in Notes the ladder from `CheckoutMigrationTrait::getCheckoutMigrationContext()` — one live subscription; `plan_switching.enabled`; current ≠ target; switch type enabled; target listed as an allowed switch on the SOURCE product; proration succeeds — and its effect: cart rows `Checkout action: Replaces your current <product> subscription` + `Due today`, order meta `_arraysubs_order_type = plan_switch_checkout`, then `PlanManager::updateSubscriptionProduct()` replaces the subscription in place instead of adding one.
12. Empty the cart; close the session.

## Expected results
1. Order 1 total `$10.00`; order 2 total `$24.00` ($9.00 line + `Subscription Signup Fee` $15.00). No tax line.
2. Two `arraysubs_data` posts for `slt-chk-second`, both `arraysubs-active`, `_product_id` = Daily Core and Signup Fee Daily.
3. SUB1 at step 9 matches step 4 except `_next_payment_date` if a renewal ran; `_product_id` and `_recurring_amount` (10.00) unchanged.
4. Steps 6/7 show **no** `Checkout action` row and no `Due today` / `Credit applied` rows.
5. Order 2 has none of `_arraysubs_order_type=plan_switch_checkout`, `_arraysubs_subscription_id`, `_arraysubs_new_product_id`, `_arraysubs_proration_data`; its line item has no `_arraysubs_checkout_migration`.
6. `SUB2._recurring_amount = 9.00`, `_signup_fee = 15.00` — the fee is a first-payment cart fee, so the next-day renewal is $9.00 with no fee line.
7. Filed conclusion: at this baseline `auto_migrate_on_checkout=true` has no runtime effect — a settings-UX finding, not a functional bug.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `new_subscription` x2 | each activation | slt-chk-second@example.test | `is active` | `wait-new MB08A` / `MB08B` |
| 2 | `admin_new_subscription` x2 | same moments | admin | `New subscription #` | `mailpit-agent list 30` |
| 3 | Woo order emails x2 | processing/completed | customer | `order` | `mailpit-agent list 30` |
| 4 | NONE EXPECTED — `auto_downgrade`, `subscription_cancelled`, plan-switch mail | checkout 2 | — | — | sweep `list 50` |

## Evidence to capture
- `SLT-CHK-08-01-subs-empty.png`, `-02-cart-second.png`, `-03-no-migration.png`, `-04-two-active-subs.png`.
- Order/subscription ids, SUB1 before-after diff, order-2 meta dump, Mailpit ids.

## Pass criteria
- [ ] Both checkouts succeed at $10.00 and $24.00
- [ ] Two independent active subscriptions for one customer
- [ ] SUB1 product and recurring amount unchanged
- [ ] Zero migration UI rows and zero migration order/item meta
- [ ] `SUB2._recurring_amount = 9.00`, `_signup_fee = 15.00`
- [ ] No plan-switch mail; gate finding filed with its code citation

## Isolation / teardown
- No global setting written. Creates user `slt-chk-second` — register it for SLT-SETUP-99B deletion.
- Handed on: SUB1 and SUB2 renew daily from 2026-08-07 (watch confirms 2026-08-08); both join the day/1 cohort cancelled by SLT-SETUP-99A.

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
