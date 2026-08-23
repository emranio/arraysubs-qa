---
id: 87
title: Mid-cycle Basic→Pro upgrade, then prove the D6 renewal charges $15.00 on the unchanged due date
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - plan-switching
    - day-05
due: "2026-08-28"
estimate: 2h
depends_on:
    - 11
    - 12
    - 60
claimed_by: plume-coal
class: standard
---

> **SLT-SW-06** · group `switching` · scheduled **D05** (2026-08-28)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
The group's highest-value assertion: a same-cycle upgrade taken mid-cycle leaves `_next_payment_date` untouched and makes the next unattended renewal charge the NEW price. Basic ($5.00/day) → Pro ($15.00/day) on D5 (2026-08-28); the D6 (2026-08-29) renewal must be $15.00, at the original due moment + the crc32 spread offset. Per L7 the proration order is never auto-charged — unpaid means no switch and D6 still bills $5.00.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: new registered — **this task also creates `slt2-switch2` / `slt2-switch2@example.test`**
- Plugins: free-only

## Preconditions
- SLT-SETUP-02, SLT-SETUP-03, SLT-PROD-11 done (Basic's `_arraysubs_upgrade_products` holds Pro).
- `slt2-switch` is reserved by SLT-SW-01..05, hence the dedicated account. Sessions `admin-SLT-SW-06`, `customer-SLT-SW-06`; cart and persistent-cart meta empty first and last.

## Test data
| Item | Value |
|---|---|
| Products | SLT2 Plan Basic $5.00 day/1 → SLT2 Plan Pro $15.00 day/1 |
| Card | 4242 4242 4242 4242 |
| Account | slt2-switch2 / `SltQa!2026#Pass` / Customer |
| Amounts | signup $5.00; proration net = round(15×dr,2) − round(5×dr,2); renewal $15.00 |
| dr | max(0, 1 − round((now − `_last_payment_date`)/86400, 2)) |


## Steps
1. Resolve strict numeric, distinct `BASIC_ID`/`PRO_ID` from the registry and verify titles/prices/schedules/upgrade relationship. Record `SUBCOUNT_BEFORE`, `ORDERCOUNT_BEFORE`, and `USER_PRE=$(mailpit-agent latest-id)`.
2. In `admin-SLT-SW-06`, create `slt2-switch2` as Customer with notification unticked; record numeric `USER_ID`, billing, login/email/role. Classify exactly one admin-only registration message and no customer account/password mail, then set checkout baseline `MP0`.
3. In `customer-SLT-SW-06`, log in, require both carts empty, and capture `SLT-SW-06-00-cart-empty-before.png`. If unexpected task-owned cart state exists, capture it, clear only this new user's carts, create the contextual dedicated issue in step 10, and continue from a proven empty state rather than stopping.
4. Open `/checkout/?add-to-cart=$BASIC_ID`, handle the frozen one-click redirect, capture the unpopulated $5 summary as `SLT-SW-06-00a-basic-checkout.png`, fill the hosted card without capturing it, pay, record numeric `ORDER_SW6_PARENT`, and capture safe receipt `-00b-basic-receipt.png`. Resolve `SUB_ID` only from exact order `_subscription_ids` JSON with a strict one-element guard; require reverse parent/customer/product linkage, `SUBCOUNT_AFTER == SUBCOUNT_BEFORE+1`, and the complete four-message `MP0` delta.
5. Save stable numeric `$SUB_ID` metas as `T0`; compute `OFFSET` with the README argv command, and record exact invoice/charge action IDs/gates/deadlines.
6. Between 30 and 90 minutes after the exact parent completion time, set `SWITCH_PRE=$(mailpit-agent latest-id)`, open `/my-account/view-subscription/$SUB_ID/`, choose exact `$PRO_ID`, and capture `SLT-SW-06-01-switch-modal.png` plus `-01a-proration-summary.png`; record formula inputs/displayed net and confirm.
7. Require `requires_payment` and record strict numeric `ORDER_SW6_CHANGE` from its pay URL. Capture its unpopulated summary as `SLT-SW-06-02-proration-order.png`, prove the sub still owns Basic, fill the hosted card without capturing it, pay, and capture safe receipt `-02a-change-receipt.png`.
8. Require `ORDERCOUNT_AFTER == ORDERCOUNT_BEFORE+2`, exact switch-order/subscription relationship and proration meta/line; reconcile only the two Woo switch-order messages after `SWITCH_PRE` and no lifecycle/switch mail. Reopen the subscription, capture `SLT-SW-06-03-sub-after.png`, diff exact metas vs T0, and in `admin-SLT-SW-06` capture numeric pending rows as `SLT-SW-06-04-actions.png`. Publish the rescheduled action ID/gate and `gate−5m` deadline, prove carts empty, close D5 sessions, and leave the card `in-progress`.
9. Take `RENEW_PRE` only inside `[exact D6 charge gate−300s, gate)`, never force the action, and poll in ≤60-second calls through the 15-minute cutoff. Resolve the renewal order by exact `$SUB_ID`/cycle plus reverse meta, require $15 and one Pro line, reconcile the complete delta, and capture `SLT-SW-06-05-d6-renewal.png` in `admin-SLT-SW-06-R1`; close that session.
10. Any live failure goes only in `qa/issues/` kanban card named `SLT-SW-06-<concise-slug>`, never a kanban card, with task/stage/plan; user/product/parent/switch/renewal/subscription/action IDs; login/email/role; exact URLs/sessions/gates; reproduction; expected/actual; formula/UI/meta/order/Mailpit/screenshot proof; and the pre-switch state as counterexample. Continue safe unaffected checks. After D6, independently review all evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. `SUB_ID` `arraysubs-active`, `_recurring_amount=5.00`, day/1, `_next_payment_date` = placed UTC + 24h; switch classified **upgrade** (5.00 → 15.00 daily rate).
2. `ORDER_SW6_CHANGE` manual/pending, `_arraysubs_order_type=plan_switch`, `_arraysubs_switch_type=upgrade`, one fee line `Plan Upgrade to SLT2 Plan Pro - Proration` = round(15×dr,2)−round(5×dr,2); no switch-fee, no tax line.
3. Plan stays Basic until `ORDER_SW6_CHANGE` is paid; then `_product_id`=Pro, `_recurring_amount=15.00`, day/1.
4. **`_next_payment_date` identical to `T0`**; legs at `+OFFSET`, `+OFFSET−6h`.
5. D6 renewal order totals exactly **$15.00**, `_is_renewal_order=yes`, in `[due+OFFSET, +10min]`; then `_next_payment_date` advances exactly 24h.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC customer paid-order + WC New order + `new_subscription` + `admin_new_subscription` | step 4 | customer/admin | exact order/subscription | complete owner-filtered `MP0` delta; save/show all four IDs |
| 2 | WC customer/admin order pair only — no ArraySubs switch mail | step 7 | customer/admin | exact `ORDER_SW6_CHANGE` | complete owner-filtered `SWITCH_PRE` delta; save/show both IDs |
| 3 | payment_successful | D6 renewal | slt2-switch2 | exact numeric `$SUB_ID` | final-five-minute `RENEW_PRE`, ≤60-second polling, complete delta |
| 4 | renewal_invoice | 6h pre-due | — | — | **NONE EXPECTED** (L23) |
| 5 | WP New User Registration | setup before `MP0` | admin | `New User Registration` | exactly one after `USER_PRE`; zero customer account/password mail |

## Evidence to capture
- `SLT-SW-06-01-switch-modal.png`, `-02-proration-order.png`, `-03-sub-after.png`, `-04-actions.png`, `-05-d6-renewal.png`; `SUB_ID`, order ids, `OFFSET`, `T0` diff, `dr`, `USER_PRE`, setup-mail id, checkout-only `MP0`, later Mailpit ids, console errors

## Pass criteria
- [ ] Starts on Basic at $5.00/day under slt2-switch2, classified `upgrade`
- [ ] Proration order manual with the correct fee line and no switch fee
- [ ] Plan unchanged until `ORDER_SW6_CHANGE` paid, then `_recurring_amount=15.00` on Pro
- [ ] `_next_payment_date` unchanged from `T0`; legs at due+OFFSET and −6h
- [ ] D6 renewal total exactly $15.00 inside the offset window
- [ ] Emails 1 and 3 present; no switch mail; no renewal_invoice mail
- [ ] Setup mail isolated before `MP0`; no customer account/password mail
- [ ] Both orders and sole subscription linked/count-exact, safe evidence complete, phase sessions closed, and final evidence reviewed to done

## Isolation / teardown
- Hands SLT-SW-08 an active day/1 Pro subscription on slt2-switch2; SW-08 must not start before this task's D6 evidence exists. `slt2-switch2` is deleted by SLT-SETUP-99B.
- Empty both carts and close only the exact D5/R1 sessions. No global change. If D6 misses its deadline, capture exact pending action/date/note sets in the same dedicated issue contract and never drain Action Scheduler.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
