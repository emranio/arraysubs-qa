---
id: 86
title: Upgrade SLT Plan Basic to SLT Plan Pro on Stripe with prorate_immediately arithmetic
status: done
priority: high
created: 2026-08-02T03:43:10.352632697+02:00
updated: 2026-08-07T19:06:50.339412035+02:00
started: 2026-08-07T19:06:50.339411134+02:00
completed: 2026-08-07T19:06:50.339411134+02:00
tags:
    - plan-switching
    - day-05
due: "2026-08-07"
estimate: 1h 15m
depends_on:
    - 60
    - 11
    - 12
    - 72
claimed_by: plume-coal
claimed_at: 2026-08-07T19:06:50.339411925+02:00
class: standard
---

> **SLT-SW-01** · group `switching` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Upgrade `slt-switch`'s **SLT Plan Basic** ($5.00 day/1) subscription to **SLT Plan Pro** ($15.00 day/1) from the portal on Stripe: prove the same-cycle proration arithmetic, that the switch does not apply until the always-manual proration order is paid (no off-session charge even with a saved card), that `_next_payment_date` holds, and that no switch email is sent.

## Scope
- Gateway: Stripe test
- Checkout: classic (order-pay form; page 8 untouched)
- Account: existing (`slt-switch`)
- Plugins: free-only

## Preconditions
- SLT-PROD-11 done; Basic's `_arraysubs_upgrade_products` contains the Pro ID.
- `slt-switch` owns the **active** `SUB_BASIC` created by `SLT-SW-00` with `4242 4242 4242 4242`. SLT-SETUP-02 frozen baseline is in force; change no setting.

## Test data
| Item | Value |
|---|---|
| Switch | `SUB_BASIC`: Basic $5.00 day/1 → Pro $15.00 day/1 |
| Card | `4242 4242 4242 4242` |
| Portal | `/my-account/view-subscription/$SUB_BASIC/`, session `cust-SLT-SW-01` after numeric resolution |

Branch-A arithmetic (`cycle_days=1`, no cycle change); T0 = `_last_payment_date` UTC, T1 = confirm time UTC:
```
d = max(0, round((T1-T0)/86400,2))   r = max(0, 1-d)
credit=round(5.00*r,2)  charge=round(15.00*r,2)  net=charge-credit
example r=0.92 -> 4.60 / 13.80 / 9.20
```

## Steps
1. Resolve strict numeric `SUB_BASIC`, `BASIC_ID`, `PRO_ID`, user ID, and exact current invoice/charge action IDs from the registry; require active owner/product relationships and the upgrade target. Save stable before metas, history/store-credit, exact note/order sets, and `ORDERCOUNT_BEFORE`.
2. Compute offset with the README argv command for numeric `$SUB_BASIC`; do not open the switch bracket inside the invoice-to-charge+30m interval. If a natural renewal just ran, re-read all before values and action IDs before continuing.
3. `M0=$(mailpit-agent latest-id)`. `agent-browser --session cust-SLT-SW-01 open ".../my-account/"`; log in `slt-switch` / `SltQa!2026#Pass`; open the portal URL; `snapshot -i`.
4. Click **Change Plan**; on the **Upgrade/Downgrade** tab confirm SLT Plan Pro shows the badge **Upgrade**; screenshot.
5. Click **Select** on SLT Plan Pro; record T1 and the rows **Credit for unused time**, **Charge for new plan**, **Amount due**; check against the formula.
6. Confirm through the shared dialog; record strict numeric `PRO_ORDER` from `/checkout/order-pay/$PRO_ORDER/`. Capture the unpopulated order-pay summary as `SLT-SW-01-03-order-pay.png`; never capture populated card fields.
7. Before paying, require numeric `$SUB_BASIC` still owns `$BASIC_ID`, all before dates/history remain byte-identical, and `$PRO_ORDER` is relationship-linked but unprocessed/unpaid.
8. Fill the hosted card without capturing it, pay, require safe order-received, and capture `SLT-SW-01-04-received.png`.
9. Require `ORDERCOUNT_AFTER == ORDERCOUNT_BEFORE+1`, exact bidirectional subscription/switch-order relationship, and dump metas/line items. Use `wp eval` argv with numeric `$PRO_ORDER` to read `_arraysubs_proration_data`; re-read exact renewal actions and require old ID gone/new ID present at the same computed GMT.
10. Inspect the complete Mailpit delta after M0: allow only the WooCommerce customer/admin messages linked to PRO-ORDER and require no lifecycle/plan-switch message for SUB_BASIC. Save/show every matched id and classify unrelated shared-site mail.
11. Publish the rescheduled exact charge ID/GMT and `gate−5m` deadline. Close only `cust-SLT-SW-01` after the paid-switch/cart teardown and keep the card `in-progress`. Take `SW01_RENEW_PRE` only inside `[gate−300s, gate)`, observe naturally without forcing, and poll in ≤60-second intervals through the cutoff. Resolve the renewal order by exact `$SUB_BASIC`/cycle plus reverse meta, reconcile its complete delta, and require $15.00. Capture it in `admin-SLT-SW-01-R1`, then close that phase session.
12. If any assertion fails, create a standalone `issues/SLT-SW-01-<concise-slug>.md` (never a kanban bug card) with task/stage/plan, user/product/subscription/switch-order/renewal-order/action IDs, login/email/role, exact URLs/sessions/gates, reproduction, expected/actual, arithmetic/UI/meta/action/Mailpit/screenshot proof, and the pre-payment state as counterexample. Continue safe unaffected checks. After R1, independently review all evidence, move the card through `review` to `done`, and ensure Review returns to zero.

## Expected results
1. Preview equals the formula to the cent; Amount due = net; no switch-fee row. PRO-ORDER carries `_arraysubs_order_type=plan_switch`, `_arraysubs_switch_type=upgrade`, old=Basic, new=Pro, one fee line `Plan Upgrade to SLT Plan Pro - Proration` = net, total = net, USD, **no tax line**.
2. Before payment `_product_id` is still Basic (response was `requires_payment: true` + `checkout_url`).
3. After payment: order processing/completed, `_arraysubs_switch_processed=yes`, note "Subscription updated after proration payment."; `_product_id`=Pro, `_recurring_amount`=`15.00`, day/1 kept, title `SLT Plan Pro - Subscription #$SUB_BASIC` using the resolved numeric value.
4. `_next_payment_date` byte-identical to step 1; the renewal action is unscheduled+rescheduled, so its **id changes** while its GMT stays due+offset.
5. `_plan_switch_history` gains one `type=upgrade` entry; `_store_credit` unchanged; status stays `arraysubs-active`; next renewal total = $15.00.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED from the switch | confirm/apply | — | — | no listener for `arraysubs_send_plan_switch_email` (SLT-REF-08 §5); any lifecycle mail FAILS |
| 2 | WooCommerce order mail for PRO-ORDER (+ admin New order) | order paid | slt-switch@example.test / admin | `Order #<PRO-ORDER>` | exact complete M0 delta; the only switch-time mail allowed |
| 3 | payment_successful | first post-switch unattended renewal | slt-switch@example.test | `Payment received for subscription #$SUB_BASIC` with the resolved numeric value | complete delta after registered `SW01_RENEW_PRE` |

## Evidence to capture
- `SLT-SW-01-01-modal.png`, `-02-preview.png`, `-03-order-pay.png`, `-04-received.png`; `SUB_BASIC`/PRO-ORDER/renewal-order ids; computed vs displayed amounts; before/after meta; `_arraysubs_proration_data`; `SW01_RENEW_PRE`, action id/GMT, and exact Mailpit ids.

## Pass criteria
- [ ] Preview matches the Branch-A formula; switch not applied before payment
- [ ] Proration order metas + one fee line = net, no tax; after payment product Pro, `_recurring_amount` 15.00, `_arraysubs_switch_processed=yes`
- [ ] `_next_payment_date` unchanged byte-for-byte; one upgrade entry in `_plan_switch_history`; only WooCommerce switch-order mail
- [ ] First exact post-switch renewal charges $15.00 and its payment-success mail is reconciled from `SW01_RENEW_PRE`
- [ ] Switch/renewal relationships exact, card-safe evidence complete, phase sessions closed, and final review moved the card to done

## Isolation / teardown
- Hands `SUB_BASIC` to SLT-SW-02 on **SLT Plan Pro** (auto-downgrade target Basic); do not switch it again. `slt-switch`'s second subscription (`SUB_PRO`) belongs to SLT-SW-03. Nothing global changed.

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

[[2026-08-06]] Thu 20:35
Source-block note on 2026-08-06: card 72 / SLT-SW-00 is now closed UNVERIFIED after the D4 same-day ladder-seeding checkout window was missed. This card cannot start until a later valid execution actually creates the required SUB_BASIC ladder fixture.

[[2026-08-07]] Fri 23:05
Final D05 relationship read found no ArraySubs subscription for `slt-switch` user 349, so no numeric `SUB_BASIC` exists. Basic 12608 and Pro 12611 remain published, but the required D4 ladder seed was never recreated by a valid replan. Execution closes UNVERIFIED without browser, order, subscription, action, setting, or mail mutation. Evidence: `/home/server-manager/slt-evidence/D05-night-source-block-and-window-close.txt`.
