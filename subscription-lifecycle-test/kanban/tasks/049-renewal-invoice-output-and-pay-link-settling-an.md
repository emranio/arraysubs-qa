---
id: 49
title: Renewal invoice output and pay-link settling an unpaid invoice on Stripe and Paddle
status: todo
priority: high
created: 2026-08-02T03:43:07.248285977+02:00
updated: 2026-08-02T03:43:17.614113776+02:00
tags:
    - admin
    - portal
    - day-03
    - has-conflicts
due: "2026-08-05"
estimate: 2h
depends_on:
    - 48
    - 58
    - 23
class: standard
---

> **SLT-ADM-07** · group `admin` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · dependency-gap / unowned purchases** — with `SLT-MYA-04`, `SLT-ADM-08`, `SLT-SW-01`, `SLT-SW-03`, `SLT-SW-02`, `SLT-EML-08`

- *Problem:* Five purchases that multiple tasks treat as preconditions are owned by no task key in the index - they existed only as free-text 'purchases owned by other groups' rows in the superseded calendar. (a) S_FEE: slt-core's SLT Signup Fee Daily subscription, required by SLT-ADM-07 ('bought D3 by slt-core'), SLT-MYA-04 and SLT-ADM-08 (which refunds and cancels it). (b) S-BASIC and S-PRO: slt-switch's SLT Plan Basic and SLT Plan Pro subscriptions 'bought D4', required by SLT-SW-01, SW-03, SW-02 and SLT-EML-08. (c) SLT Flex Month Segments segment-3 by slt-flex3 on 2026-08-08, required by SLT-SYN-10 (SUB_S3, _next_payment_date 2026-09-30 18:00:00). (d) The D8 time-travel renewals for month segment-1/segment-2, week segment-3 (SLT-SYN-07's tail, due 2026-08-15) and the flex-variable tail - audit C17 mandates one dedicated D8 owner and none exists. (e) SLT-SYN-10 also references SUB_S2 which SLT-SYN-06 does buy, so only seg-3 is missing.
- *Required fix:* Assign explicit owners. Add step 0 to SLT-ADM-07: 'slt-core buys SLT Signup Fee Daily on D3 after 12:00 (order + subscription ids to the registry)'. Create SLT-SW-00 on D4: 'slt-switch buys SLT Plan Basic and SLT Plan Pro on Stripe after 12:00' as the ladder canvas for SW-01/02/03 and EML-08. Add step 0 to SLT-SYN-10: 'slt-flex3 buys SLT Flex Month Segments on 2026-08-08 (D6) after 12:00 - day-in-cycle 8, past both boundaries, resolves to segment 3, next payment 2026-10-01 00:00 site = 2026-09-30 18:00 UTC'. Create SLT-TT-00 on D8 as the single time-travel owner: pre-flight pending-queue screenshot + the 13 non-SLT _next_payment_date snapshot, then the month seg1/seg2 and week seg3 renewals and the flex-variable tail, single-action-by-id only, then the post-drain non-SLT diff proof - and have SYN-10, SW-02, EML-08, EML-10 and LIFE-01 quote its snapshot instead of each taking their own.

---
## Objective
Verify the customer-facing renewal invoice: what an unpaid renewal order shows in my-account and on order-pay, that totals are right with taxes off and no signup-fee line, and that the Pay link settles it on Stripe and Paddle. The unpaid window is real on both — Stripe leaves the order `pending` from `due+k-6h` to `due+k`, Paddle's `processRenewalPayment()` no-ops (SLT-REF-09 A).

## Scope
- Gateway: both
- Checkout: block (order-pay page)
- Account: existing (slt-core, slt-paddle)
- Plugins: both

## Preconditions
- SLT-ADM-06 done. `SLT Signup Fee Daily` ($9.00/day + $15.00 fee) bought D3 by `slt-core`; `SLT Paddle Daily` ($11.00/day) bought D2 by `slt-paddle`; ids in the registry.
- Stripe's renewal-invoice email is suppressed for auto-renew automatic subs (`EmailManager.php:504-510`) — a negative check, not a bug.
- Sessions: `cust-adm07-stripe`, `cust-adm07-paddle`.

## Test data
| Item | Value |
|---|---|
| Stripe | S_FEE, $9.00 renewal, card `4242 4242 4242 4242` |
| Paddle | S_PAD, $11.00 renewal, sandbox overlay |
| Amounts | USD, no tax/fee row |

## Steps
1. `mailpit-agent latest-id` -> `M0`. Compute `k = crc32('arraysubs-spread-'.<id>) % 21600` per sub; derive each window `[due+k-6h, due+k]` site time.
2. Inside the S_FEE window, as `--session cust-adm07-stripe` log in at `https://mirror-help.arrayhash.com/my-account/` as `slt-core`, open `/my-account/view-subscription/<S_FEE>/` -> `snapshot -i`. Find R_FEE (`Pending payment`) and its **Pay** button.
3. Click **View** on R_FEE -> `snapshot -i`. Record `SLT Signup Fee Daily x 1` at `$9.00`, total `$9.00`; confirm no `Subscription Signup Fee` row and no tax row.
4. Click **Pay** -> `snapshot -i`: record the order-pay URL (`/checkout/order-pay/<id>/?pay_for_order=true&key=…`) and offered methods. Pay with `4242 4242 4242 4242`, `12/34`, CVC `123`; screenshot the receipt.
5. `mailpit-agent wait-new M0 180 "Payment received"`, then `list 20`; record every id/subject this payment produced.
6. `wp db query "SELECT id,status,total_amount FROM wp_wc_orders WHERE id=<R_FEE>"`; `wp post meta list <S_FEE> --keys=_next_payment_date,_completed_payments,_pending_renewal_order_id` (both `--allow-root`).
7. Open `/my-account/view-subscription/<S_PAD>/` as `--session cust-adm07-paddle` (login `slt-paddle`). Screenshot pending order R_PAD and the note "awaiting automatic charge from Paddle".
8. Repeat steps 3-4 for R_PAD via Paddle sandbox; total must read `$11.00`, no fee, no tax. Record any Paddle overlay console error.
9. Deferred watch check (24 h): re-open R_PAD/S_PAD — a second paid or retroactive order for the SAME cycle means Paddle charged too; file a double-charge issue.

## Expected results
1. R_FEE and R_PAD exist unpaid inside the predicted window with a **Pay** button that disappears once paid.
2. R_FEE detail: one line, qty 1, `$9.00`; total `$9.00`; no signup-fee line (that $15.00 cart fee is charged only at signup) and no tax row.
3. After paying R_FEE: status Processing/Completed, `total_amount = 9.00`; on S_FEE `_pending_renewal_order_id` deleted, `_last_payment_date` set, `_completed_payments` +1, `_next_payment_date` +1 day from `_renewal_scheduled_date`, not from the pay time.
4. The later `arraysubs_process_renewal` no-ops (`isRenewalDue()` false); no second order for the same `_renewal_cycle_number`.
5. R_PAD reaches a paid status at `$11.00` with `_paddle_transaction_id` set.
6. One `payment_successful` per leg plus WooCommerce's order mail; no renewal-invoice mail on Stripe.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `payment_successful` | R_FEE paid (step 4) | slt-core | `Payment received for subscription #<S_FEE>` | `wait-new M0 180 "Payment received"` |
| 2 | WooCommerce order mail | R_FEE paid | slt-core | `Your order` | `mailpit-agent list 20` |
| 3 | `payment_successful` | R_PAD paid (step 8) | slt-paddle | `Payment received for subscription #<S_PAD>` | `wait-new <id before step 8> 180` |
| 4 | `renewal_invoice` NONE EXPECTED | Stripe invoice creation | — | `Invoice for subscription` | No such subject after M0 |

## Evidence to capture
- Screenshots `SLT-ADM-07-01-unpaid.png`, `-02-totals.png`, `-03-pay.png`, `-04-paid.png`, `-05-paddle.png`
- Order/subscription ids, `k` values, Mailpit ids, gateway transaction ids.

## Pass criteria
- [ ] Unpaid renewal order with a Pay button on both gateways
- [ ] Totals exact ($9.00, $11.00); no fee row, no tax row
- [ ] Pay link URL correct; settles the invoice on Stripe and on Paddle
- [ ] Schedule advanced from `_renewal_scheduled_date`; no duplicate order
- [ ] `payment_successful` per leg; no Stripe renewal-invoice mail

## Isolation / teardown
- Paying inside the invoice window moves each charge forward by at most 6 h; cadence unchanged. Post the paid timestamps to the registry so the watch does not misread them as early renewals.
- Touches only S_FEE, S_PAD and their orders; no setting changed. Close both sessions by name.

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
