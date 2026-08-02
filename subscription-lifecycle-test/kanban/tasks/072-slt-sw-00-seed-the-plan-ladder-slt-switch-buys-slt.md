---
id: 72
title: 'SLT-SW-00 Seed the plan ladder: slt-switch buys SLT Plan Basic and SLT Plan Pro'
status: todo
priority: critical
created: 2026-08-02T03:43:09.349918412+02:00
updated: 2026-08-02T03:43:19.938269804+02:00
tags:
    - plan-switching
    - day-04
due: "2026-08-06"
estimate: 45m
depends_on:
    - 60
    - 12
class: standard
---

> **SLT-SW-00** · group `switching` · scheduled **D04** (2026-08-06)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Every plan-switching task (`SLT-SW-01`..`SLT-SW-10`) assumes `slt-switch` already owns a live ladder subscription. Nothing in the plan created one — the audit caught this as a dependency inversion. This task seeds it, and only it.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing (`slt-switch`)
- Plugins: both

## Preconditions
- `SLT-PROD-11` complete: the four-product ladder (Basic / Pro / Enterprise / Peer) exists and is wired for switching.
- `SLT-SETUP-03` complete: `slt-switch` exists with a billing address.
- Runs on D4 **after 12:00 site time**, before `SLT-SW-09`.
- `slt-switch` owns no ladder subscription yet. **Verify this first** — `auto_migrate_on_checkout=true` means a rebuy migrates rather than creates.

## Test data
| Item | Value |
|---|---|
| Products | SLT Plan Basic (day/1, $10.00), SLT Plan Pro (day/1, $15.00) |
| Account | slt-switch / `SltQa!2026#Pass` |
| Card | 4242 4242 4242 4242, 12/34, CVC 123 |
| Session | `cust-SLT-SW-00` |

## Steps
1. `PRE=$(mailpit-agent latest-id)`. Record the `arraysubs_data` count.
2. Confirm `slt-switch` owns no SLT ladder subscription: `wp post list --post_type=arraysubs_data --allow-root` cross-checked against `_customer_id`.
3. `agent-browser --session cust-SLT-SW-00 open ".../my-account/"` → log in as `slt-switch`.
4. Assert `/cart/` is EMPTY. Add **SLT Plan Basic** only — `allow_multiple_in_cart=false` forbids buying both in one cart.
5. Checkout on the block page with Stripe 4242. Record `ORDER_BASIC`, `SUB_BASIC`.
6. Assert cart EMPTY again, then repeat steps 4-5 for **SLT Plan Pro** as a *second, separate* order. Record `ORDER_PRO`, `SUB_PRO`.
7. For each subscription record: status, `_next_payment_date`, `_recurring_amount`, spread offset `k`, and both queued Action Scheduler legs.
8. Publish `SUB_BASIC` and `SUB_PRO` to the `slt-catalog-registry` page. Every `SLT-SW-*` task reads them from there.
9. `mailpit-agent list 20` and reconcile.

## Expected results
1. Two new `arraysubs_data` posts, both `arraysubs-active`, owned by `slt-switch`.
2. `SUB_BASIC` recurring $10.00, `SUB_PRO` recurring $15.00, both day/1.
3. Two separate parent orders, each `processing`, correctly linked both ways.
4. Neither purchase migrated the other — the count increased by exactly 2.
5. Both subscriptions have invoice and charge legs queued at `due+k−6h` and `due+k`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC New order ×2 | each order → processing | admin | `New order #` | `list 20` |
| 2 | WC Processing order ×2 | each order → processing | slt-switch@example.test | `order has been received` | `list 20` |
| 3 | `new_subscription` ×2 | each → active | slt-switch@example.test | `is active` | `wait-new $PRE 180 "is active"` |
| 4 | `admin_new_subscription` ×2 | same | admin | `New subscription #` | `list 20` |
| 5 | NONE EXPECTED | — | — | — | no renewal, invoice, trial or reminder mail; day/1 cycle is shorter than the 3-day reminder lead |

## Evidence to capture
- Screenshots of both order-received pages and both subscription detail screens.
- `SUB_BASIC`, `SUB_PRO`, `ORDER_BASIC`, `ORDER_PRO`, both `k` values, all four AS timestamps.
- Registry page updated.

## Pass criteria
- [ ] Exactly two new active subscriptions for `slt-switch`, $10.00 and $15.00
- [ ] Neither purchase migrated the other
- [ ] Both orders linked two-way; both AS leg pairs queued
- [ ] Mail set matches rows 1-4; row 5 negatives hold
- [ ] Registry updated with both subscription IDs

## Isolation / teardown
- Hands the whole `SLT-SW-*` group its ladder. **Do not cancel** — `SLT-SW-01` upgrades `SUB_BASIC`, `SLT-SW-03` crossgrades `SUB_PRO`. Belongs to the D10 cancellation cohort. Nothing global changed; cart left empty.

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
