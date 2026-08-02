---
id: 104
title: Admin-side repeat of the Basic to Pro upgrade and record-for-record diff against SLT-SW-01
status: todo
priority: medium
created: 2026-08-02T03:43:11.662273007+02:00
updated: 2026-08-02T03:43:23.07453456+02:00
tags:
    - plan-switching
    - day-07
    - has-conflicts
due: "2026-08-09"
estimate: 1h 30m
depends_on:
    - 86
    - 60
    - 12
class: standard
---

> **SLT-SW-04** · group `switching` · scheduled **D07** (2026-08-09)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · same-account-collision / duplicate account creation** — with `SLT-SW-06`, `SLT-SW-08`

- *Problem:* SLT-SW-06 (d5) states 'this task also creates slt-switch2 / slt-switch2@example.test' and buys SLT Plan Basic on it, then upgrades to Pro. SLT-SW-04 (d7) states 'this task CREATES slt-switch2 / slt-switch2@example.test' and buys SLT Plan Basic on it again. SLT-SW-08 (d7) then operates on 'slt-switch2, on SLT Plan Pro from SLT-SW-06'. SW-04 either aborts on a duplicate user, or buys SLT Plan Basic a second time on an account that already holds a Pro subscription from the same ladder - and with auto_migrate_on_checkout the checkout-migration ladder in CheckoutMigrationTrait becomes reachable, silently converting SW-08's Pro subscription instead of creating SW-04's Basic one.
- *Required fix:* SLT-SW-04 creates and uses a distinct account, slt-switch4 / slt-switch4@example.test (Customer, SltQa!2026#Pass, SETUP-03 step 4 billing), registered for 99B deletion. SLT-SW-06 remains the sole creator of slt-switch2 and the sole owner of its subscription; SLT-SW-08 continues to inherit it. Add to SW-04's preconditions: 'slt-switch2 belongs to SLT-SW-06/SLT-SW-08 and must not be reused'.

**`high` · shared-global-setting / same-day bracket collision** — with `SLT-SW-08`, `SLT-SW-02`, `SLT-ADM-01`, `SLT-MYA-04`, `SLT-DUN-05`

- *Problem:* SLT-SW-08 (d7) sets proration.switch_fees.upgrade from 0 to 7.50 globally and restores it in the same task, declaring 'no other SLT switch may run between set and restore'. SLT-SW-04 (d7) performs a Basic->Pro upgrade the same day and asserts its proration order matches SLT-SW-01's record-for-record with 'no switch-fee row'. If SW-04 runs inside SW-08's bracket its order gains a $7.50 'Plan Upgrade switch fee' line and the comparison fails for the wrong reason. The bracket file exists but nothing sequences the two tasks.
- *Required fix:* Fix the D7 order explicitly in the calendar and in both task bodies: SLT-SW-04 completes and its proration order is PAID before SLT-SW-08 opens its bracket. SW-08's step 2 gains a pre-flight assertion: 'SLT-SW-04 is done on the board and no plan_switch order created today is still unpaid'. SW-08's bracket file must record open/close UTC and be posted to the registry so any switch order created inside it can be attributed and re-run.

---
## Objective
Repeat the SLT-SW-01 upgrade (Basic $5.00 → Pro $15.00, day/1) on a fresh subscription driven from **wp-admin**, and compare the records field by field with SLT-SW-01. First the negative: the admin screens expose **no plan-switch control** (edit = invoice email, addresses, status only, Product read-only; detail offers only *Cancel Pending Switch*). The admin's lever is the proration order, which `attemptAutoPayment()` leaves "awaiting manual payment from customer or admin".

## Scope
- Gateway: Stripe test (purchase only)
- Checkout: block (page 8) for the purchase, wp-admin for the switch
- Account: admin-created (`slt-switch2`, created by this task)
- Plugins: free-only

## Preconditions
- SLT-SW-01 complete; its record set is the comparison baseline.
- This task CREATES **`slt-switch2` / slt-switch2@example.test**, customer, password `SltQa!2026#Pass`, billing address as SLT-SETUP-03 step 4; it matches `slt-*`, so SLT-SETUP-99 removes it. A separate account is needed because `auto_migrate_on_checkout=true` forbids buying a product twice.

## Test data
| Item | Value |
|---|---|
| Account | slt-switch2 (new) |
| Switch | S-BASIC2: Basic $5.00 → Pro $15.00, day/1 |
| Card | `4242 4242 4242 4242` |
| Admin | `page=arraysubs-mainadmin#/subscriptions/edit/<id>` and `/detail/<id>` |
| Sessions | `admin`, `cust-SW-04` |

## Steps
1. As admin create `slt-switch2` (Send User Notification UNTICKED) and fill its billing address.
2. `mailpit-agent latest-id` → M0. As `cust-SW-04` buy **SLT Plan Basic** on the block checkout. Record S-BASIC2, its order id and `_last_payment_date`.
3. Admin: open `#/subscriptions/edit/<S-BASIC2>`; screenshot the form and confirm there is **no** product/plan field — only Contact, Billing, Shipping, status. Same on `#/subscriptions/detail/<S-BASIC2>`.
4. In the customer session: portal → **Change Plan** → **Select** SLT Plan Pro → Confirm → **Change Plan**; record T1 and the preview values; land on the order-pay page and **do not pay**. Record PRO-ORDER2; `mailpit-agent latest-id` → M1.
5. Admin: `page=wc-orders&action=edit&id=<PRO-ORDER2>`; screenshot the fee line and `_arraysubs_*` custom fields; set **Order status** = `Completed` → **Update**.
6. Re-dump S-BASIC2 and PRO-ORDER2 meta; read the notes; `mailpit-agent list 30` from M1.
7. Build the comparison table vs SLT-SW-01: `_product_id`, `_recurring_amount`, billing period/interval, title, `_next_payment_date` before/after, `_plan_switch_history`, `_store_credit`, order `_arraysubs_*` metas.

## Expected results
1. Neither admin screen has a plan/product control. The only admin route is completing the proration order — or the UI-less REST `subscriptions/<id>/update`, which via `trackProductChange()` fires `arraysubs_plan_switch_completed` **without** proration, order, reschedule or title rewrite. Record that asymmetry, do not exercise it.
2. Marking PRO-ORDER2 **Completed** fires `Hooks::onProrationOrderPaid()` (hooked to `woocommerce_order_status_completed`), so the switch applies with no gateway charge.
3. Records are structurally **identical** to SLT-SW-01: `_product_id`=Pro, `_recurring_amount`=`15.00`, day/1 kept, title `SLT Plan Pro - Subscription #<S-BASIC2>`, `_arraysubs_switch_processed=yes`, note "Subscription updated after proration payment.", one `type=upgrade` history entry, `_next_payment_date`/`_store_credit` unchanged.
4. Amounts differ legitimately: r comes from this subscription's own `_last_payment_date`, so credit/charge/net differ while satisfying `net = round(15r,2) − round(5r,2)`.
5. Acceptable differences vs SLT-SW-01: no Stripe transaction id, status `completed` not `processing`, and the "completed" order mail instead of "processing". Anything else is a finding.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE at user creation | step 1 | — | — | notification unticked; `latest-id` unchanged |
| 2 | Purchase mail for the first order | step 2 | slt-switch2@example.test | `Order #` | record ids |
| 3 | NONE from the switch | steps 4-6 | — | — | no switch listener; no lifecycle mail |
| 4 | WooCommerce "completed" mail for PRO-ORDER2 | step 5 | slt-switch2@example.test | `Order #<PRO-ORDER2>` | `mailpit-agent list 30` |

## Evidence to capture
- `SLT-SW-04-01-no-plan-field.png`, `-02-detail.png`, `-03-order-metas.png`, `-04-completed.png`; the comparison table; all ids and Mailpit ids.

## Pass criteria
- [ ] Admin screens proven to have no plan-switch control (screenshots)
- [ ] Completing the proration order applies the switch with no gateway charge
- [ ] Records match SLT-SW-01 field for field (amount differences explained by r)
- [ ] `_next_payment_date` unchanged; one `upgrade` history entry; only the listed order mails

## Isolation / teardown
- Adds `slt-switch2` and one subscription, both removed by SLT-SETUP-99; register the account in the registry so no other task reuses it. Nothing global changed.

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
