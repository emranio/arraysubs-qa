---
id: 89
title: 'Admin status ladder active to on-hold to active to cancelled: emails and scheduler side effects'
status: todo
priority: critical
created: 2026-08-02T03:43:10.616609424+02:00
updated: 2026-08-02T03:43:21.615641944+02:00
tags:
    - admin
    - portal
    - day-06
due: "2026-08-08"
estimate: 1h15m
depends_on:
    - 63
class: standard
---

> **SLT-ADM-04** · group `admin` · scheduled **D06** (2026-08-08)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Drive SUB-B through the admin status ladder active → on-hold → active → cancelled and prove, leg by leg, which emails fire (and which deliberately do not) and what each leg does to the date and the queue.

## Scope
- Gateway: N/A (gateway-less canvas)
- Checkout: N/A
- Account: admin-created (slt-admincreated)
- Plugins: both

## Preconditions
- SLT-ADM-03 done; SUB-B renewed unattended on 08-07, its renewal order is unpaid, `k` recorded.
- **Run before 12:00 site time**: the unpaid renewal drives the sweep to on-hold later on 08-08. If step 1 shows `On Hold` already, record it and start at leg 2.
- Code facts: `changeStatus()` is a raw `wp_update_post` except for cancelled, which routes through `arraysubs_cancel_subscription_immediately()` (`SubscriptionController.php:1376-1460`); the reactivated mail comes from `arraysubs_data_reactivated` (`:635-637`).

## Test data
| Item | Value |
|---|---|
| Canvas | **SUB-B** (SLT-ADM-03), slt-admincreated + SLT Daily Core, $10.00 Day/1 |
| Session | `--session admin-SLT-ADM-04` |

## Steps
1. `mailpit-agent latest-id` → `M0`. Open `#/subscriptions/detail/SUB-B`; screenshot the chip, **Next Payment** and the unpaid renewal order row.
2. Probe (after every leg): `wp post get SUB-B --field=post_status`, `wp post meta list SUB-B --keys=_next_payment_date,_on_hold_date,_renewal_action_id,_renewal_invoice_action_id` (`--allow-root`), screenshot `admin.php?page=wc-status&tab=action-scheduler&status=pending&s=SUB-B`.
3. `latest-id` → `M1`. **Leg 1**: `#/subscriptions/edit/SUB-B` → `Change to...` **On Hold** → **Change Status**, confirm. `wait-new M1 120 "on hold"`. Re-probe.
4. `latest-id` → `M2`. **Leg 2**: → **Active** → **Change Status**, confirm. `wait-new M2 120 "reactivated"`. Re-probe; recompute the leg timestamps from the new date.
5. `latest-id` → `M3`. **Leg 3**: → **Cancelled** → **Change Status**, confirm. `wait-new M3 120 "cancelled"`. Re-probe.
6. Reopen the detail screen; screenshot the final chip, Next Payment and Order History. `mailpit-agent list 30` — map every message since `M0` to a row below; unmapped mail is a finding. No AS command; close the session.

## Expected results
1. **Leg 1**: `post_status = arraysubs-on-hold`; `_on_hold_date` = the transition time in UTC (`Subscriptions/Services/Hooks.php:127-139`); **all** pending invoice and charge rows gone (`OrderIntegration.php:647-649`); date unchanged. One customer email only — **no admin on-hold email exists**.
2. **Leg 2**: the stored date is now past, so `recalculateNextPaymentOnReactivation()` rewrites it to transition time + 1 day = **D2** (`:670-717`); both legs re-queue at `D2+k−6h` and `D2+k` (±60 s) with fresh action ids.
3. One customer email on leg 2 (`subscription_reactivated`); **`new_subscription` must NOT be re-sent** — old status is on-hold, not pending/trial/auto-draft.
4. Reactivation adds a gateway-validation note saying billing is manual — record its text.
5. **Leg 3**: `post_status = arraysubs-cancelled`; `_next_payment_date` emptied and every action unscheduled (`OrderIntegration.php:653-656`); no `_waiting_cancellation` remains; the detail screen shows one `Cancelled` chip, **no** `Cancels at end of period` badge, **Next Payment** = `No recurring payment`.
6. Record the renewal order's final status verbatim rather than asserting one. No 4xx/5xx on `/subscriptions/SUB-B/status`, no console errors, no non-SLT row touched.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | subscription_on_hold | Leg 1 | slt-admincreated | `is on hold` | `wait-new M1 120 "on hold"` |
| 2 | NONE EXPECTED to admin | Leg 1 | — | — | no admin_email mail between M1 and M2 |
| 3 | subscription_reactivated | Leg 2 | slt-admincreated | `has been reactivated` | `wait-new M2 120 "reactivated"` |
| 4 | NONE EXPECTED — no `new_subscription` | Leg 2 | — | `is active` | no `is active` subject between M2 and M3 |
| 5 | subscription_cancelled + admin copy | Leg 3 | customer + admin | `has been cancelled` / `cancelled by` | `wait-new M3 120 "cancelled"`, `list 30` |

## Evidence to capture
- Screenshots `SLT-ADM-04-01-before.png`, `-02-onhold-queue.png`, `-03-queue-reactivated.png`, `-04-cancelled-detail.png`; probe outputs, `D2`, leg timestamps, Mailpit ids with subjects, the reactivation note.

## Pass criteria
- [ ] Leg 1 unschedules both legs, sets `_on_hold_date`, leaves the date alone, mails only the customer
- [ ] Leg 2 recalculates to +1 day, re-queues both legs at `D2+k−6h` / `D2+k`, sends only `subscription_reactivated`
- [ ] Leg 3 clears the date, unschedules everything, no double badge, both cancellation emails sent; every Mailpit message since `M0` maps to a table row

## Isolation / teardown
- Leaves SUB-B `arraysubs-cancelled` with an empty schedule: the cancelled SLT row SLT-ADM-01 needs, and proof that a cancelled subscription stops renewing on watch days D7-D12.
- No setting changed; SUB-B deleted by 99B.


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
