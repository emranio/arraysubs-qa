---
id: 89
title: 'Admin status ladder active to on-hold to active to cancelled: emails and scheduler side effects'
status: done
priority: critical
created: 2026-08-02T03:43:10.616609424+02:00
updated: 2026-08-08T02:39:29.068872723+02:00
started: 2026-08-08T02:35:30.003064325+02:00
completed: 2026-08-08T02:35:30.003064325+02:00
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
1. Resolve numeric `SUB_B`, exact user/product/unpaid-renewal order, and current invoice/charge action IDs from the registry; require bidirectional relationships and save status/date/action/note sets. Set `M0` and in `admin-SLT-ADM-04` capture the exact before detail as `SLT-ADM-04-01-before.png`. If already on hold, consume the exact prior natural-transition evidence as leg 1 or mark only leg 1 `UNVERIFIED`; do not replay the transition, and continue legs 2–3.
2. After every leg, save exact status/meta/note/action ID sets and capture the numeric scheduler search at `admin.php?page=wc-status&tab=action-scheduler&status=pending&s=$SUB_B`; never run an action.
3. When leg 1 is available, set `M1`, change to **On Hold** through the shared confirmation/loading UI, poll in ≤60-second calls through the two-minute cutoff, and capture exact no-action state as `SLT-ADM-04-02-onhold-queue.png`. Require one customer message and no admin message in the complete M1 delta.
4. Set `M2`, change to **Active**, poll ≤60 seconds, capture fresh exact action rows as `SLT-ADM-04-03-queue-reactivated.png`, and recompute D2/k from stored values. Require only the reactivated customer mail and no new-subscription mail.
5. Set `M3`, change to **Cancelled**, poll ≤60 seconds, and require the exact customer/admin cancellation pair plus empty date/action sets and no waiting-cancellation meta.
6. Reopen exact detail, capture `SLT-ADM-04-04-cancelled-detail.png`, and reconcile every task message after M0. If any assertion fails, create a standalone `issues/SLT-ADM-04-<concise-slug>.md` (never a kanban bug card) with task/stage/plan, subscription/order/action/note/product IDs, user ID/login/email/role, exact routes/session, reproduction, expected/actual, UI/meta/scheduler/Mailpit/screenshot proof, and another status leg as counterexample. Close only the admin session, independently review all PASS/UNVERIFIED evidence, move the card through `review` to `done`, and ensure Review returns to zero.

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
| 1 | subscription_on_hold | Leg 1 | slt-admincreated | `is on hold` | immutable M1, repeated ≤60-second waits, complete delta |
| 2 | NONE EXPECTED to admin | Leg 1 | — | — | no admin_email mail between M1 and M2 |
| 3 | subscription_reactivated | Leg 2 | slt-admincreated | `has been reactivated` | immutable M2, repeated ≤60-second waits, complete delta |
| 4 | NONE EXPECTED — no `new_subscription` | Leg 2 | — | `is active` | no `is active` subject between M2 and M3 |
| 5 | subscription_cancelled + admin copy | Leg 3 | customer + admin | `has been cancelled` / `cancelled by` | immutable M3, repeated ≤60-second waits, save/show both exact matches |

## Evidence to capture
- Screenshots `SLT-ADM-04-01-before.png`, `-02-onhold-queue.png`, `-03-queue-reactivated.png`, `-04-cancelled-detail.png`; probe outputs, `D2`, leg timestamps, Mailpit ids with subjects, the reactivation note.

## Pass criteria
- [ ] Leg 1 unschedules both legs, sets `_on_hold_date`, leaves the date alone, mails only the customer
- [ ] Leg 2 recalculates to +1 day, re-queues both legs at `D2+k−6h` / `D2+k`, sends only `subscription_reactivated`
- [ ] Leg 3 clears the date, unschedules everything, no double badge, both cancellation emails sent; every Mailpit message since `M0` maps to a table row
- [ ] Exact relationships/sets and any upstream UNVERIFIED branch recorded; session closed and evidence reviewed to done

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
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence and do not force a natural-watch action.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run a bare or `--hooks=` Action Scheduler drain. Run one known action ID at a time only when the task explicitly authorizes it and after the required queue pre-flight; natural-watch actions are never forced.
- Evidence goes under `/home/server-manager/slt-evidence/` using task-key-prefixed filenames.

[[2026-08-06]] Thu 20:37
Source-block note on 2026-08-06: prerequisite card 63 / SLT-ADM-03 was closed UNVERIFIED after its D4 setup leg missed the site-local day boundary. SUB-B and its authored unpaid-renewal handoff do not exist yet, so this card cannot start until a later valid execution actually creates that canvas and publishes real D5/D6 queue evidence.


[[2026-08-08]] Sat 06:34
D6 before-noon closeout: prerequisite SLT-ADM-03/card 63 never created SUB-B. Live exact checks found user 353 (slt-admincreated) and product 11927 (SLT Daily Core) but zero subscriptions and zero orders for customer 353, so there is no numeric canvas, unpaid renewal, queue, or mail handoff to drive. Recreating D4 setup on D6 is not permitted and would not validate this authored lifecycle. No browser route was opened and no state changed. Verdict UNVERIFIED (source absent); evidence /home/server-manager/slt-evidence/SLT-ADM-04-D06-source-block.txt. This is an inherited execution gap, not a product failure.


[[2026-08-08]] Sat 06:45
Correction to the 06:34 live-count sentence: the exact ArraySubs post type is arraysubs_data, and customer 353 owns one subscription, 12760. That row is the separately owned SLT-ADM-05 / SUB_A fixture (gateway-less/manual, no parent order, next payment 2026-09-05 13:03:41Z) and cannot be substituted for missing SLT-ADM-03 / SUB_B. The conclusion is unchanged: there is no SUB_B or D6 unpaid-renewal/action/mail handoff, so this card remains UNVERIFIED without mutation. The corrected evidence file names the unrelated counterexample.
