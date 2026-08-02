---
id: 86
title: Upgrade SLT Plan Basic to SLT Plan Pro on Stripe with prorate_immediately arithmetic
status: todo
priority: high
created: 2026-08-02T03:43:10.352632697+02:00
updated: 2026-08-02T03:43:21.305200986+02:00
tags:
    - plan-switching
    - day-05
    - has-conflicts
due: "2026-08-07"
estimate: 1h 15m
depends_on:
    - 60
    - 11
    - 12
class: standard
---

> **SLT-SW-01** · group `switching` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · same-subscription collision / duplicate coverage** — with `SLT-EML-08`, `SLT-SW-02`, `SLT-SW-03`, `SLT-PROD-11`

- *Problem:* Both tasks run on D8 and both drive the on_expire auto-downgrade of a slt-switch plan-ladder subscription. SLT-EML-08 step 5 sets _end_date on 'S_PRO - slt-switch's active Pro subscription' and fires arraysubs_expire_subscription to capture the auto_downgrade email and the expired-suppression negative. SLT-SW-02 Leg B does exactly the same on 'S-BASIC (on Pro since SLT-SW-01)'. There are only two slt-switch ladder subscriptions and SLT-SW-03 (d6) already crossgraded the other one (S-PRO) off Pro onto SLT Plan Peer - at which point Pro's _arraysubs_auto_downgrade_product no longer applies to it and EML-08's leg is unrunnable as written. Whichever task expires the remaining Pro subscription first consumes the other's canvas.
- *Required fix:* Single owner: SLT-SW-02 Leg B owns the hand-set _end_date and the expiry of S-BASIC (which SLT-SW-01 left on SLT Plan Pro). SLT-EML-08 becomes observation-only for that leg - it reads the auto_downgrade mail ('has been changed to SLT Plan Basic'), proves the subscription_expired suppression negative (EmailManager.php:317-322) and confirms S-BASIC re-activated on Basic at $5.00 - and runs strictly after SW-02 in the D8 order. Delete EML-08 steps 4-5 (queue screenshot + _end_date write) and replace with 'quote SLT-SW-02's pre-flight queue screenshot and _end_date timestamp'. Update EML-08's Test data to name S-BASIC, not S_PRO.

**`high` · dependency-gap / unowned purchases** — with `SLT-ADM-07`, `SLT-MYA-04`, `SLT-ADM-08`, `SLT-SW-03`, `SLT-SW-02`, `SLT-EML-08`

- *Problem:* Five purchases that multiple tasks treat as preconditions are owned by no task key in the index - they existed only as free-text 'purchases owned by other groups' rows in the superseded calendar. (a) S_FEE: slt-core's SLT Signup Fee Daily subscription, required by SLT-ADM-07 ('bought D3 by slt-core'), SLT-MYA-04 and SLT-ADM-08 (which refunds and cancels it). (b) S-BASIC and S-PRO: slt-switch's SLT Plan Basic and SLT Plan Pro subscriptions 'bought D4', required by SLT-SW-01, SW-03, SW-02 and SLT-EML-08. (c) SLT Flex Month Segments segment-3 by slt-flex3 on 2026-08-08, required by SLT-SYN-10 (SUB_S3, _next_payment_date 2026-09-30 18:00:00). (d) The D8 time-travel renewals for month segment-1/segment-2, week segment-3 (SLT-SYN-07's tail, due 2026-08-15) and the flex-variable tail - audit C17 mandates one dedicated D8 owner and none exists. (e) SLT-SYN-10 also references SUB_S2 which SLT-SYN-06 does buy, so only seg-3 is missing.
- *Required fix:* Assign explicit owners. Add step 0 to SLT-ADM-07: 'slt-core buys SLT Signup Fee Daily on D3 after 12:00 (order + subscription ids to the registry)'. Create SLT-SW-00 on D4: 'slt-switch buys SLT Plan Basic and SLT Plan Pro on Stripe after 12:00' as the ladder canvas for SW-01/02/03 and EML-08. Add step 0 to SLT-SYN-10: 'slt-flex3 buys SLT Flex Month Segments on 2026-08-08 (D6) after 12:00 - day-in-cycle 8, past both boundaries, resolves to segment 3, next payment 2026-10-01 00:00 site = 2026-09-30 18:00 UTC'. Create SLT-TT-00 on D8 as the single time-travel owner: pre-flight pending-queue screenshot + the 13 non-SLT _next_payment_date snapshot, then the month seg1/seg2 and week seg3 renewals and the flex-variable tail, single-action-by-id only, then the post-drain non-SLT diff proof - and have SYN-10, SW-02, EML-08, EML-10 and LIFE-01 quote its snapshot instead of each taking their own.

---
## Objective
Upgrade `slt-switch`'s **SLT Plan Basic** ($5.00 day/1) subscription to **SLT Plan Pro** ($15.00 day/1) from the portal on Stripe: prove the same-cycle proration arithmetic, that the switch does not apply until the always-manual proration order is paid (no off-session charge even with a saved card), that `_next_payment_date` holds, and that no switch email is sent.

## Scope
- Gateway: Stripe test
- Checkout: classic (order-pay form; page 8 untouched)
- Account: existing (`slt-switch`)
- Plugins: free-only

## Preconditions
- SLT-PROD-11 done; Basic's `_arraysubs_upgrade_products` contains the Pro ID.
- `slt-switch` owns an **active** SLT Plan Basic subscription bought D4 with `4242 4242 4242 4242` — **S-BASIC**. SLT-SETUP-02 frozen baseline in force; change no setting.

## Test data
| Item | Value |
|---|---|
| Switch | S-BASIC: Basic $5.00 day/1 → Pro $15.00 day/1 |
| Card | `4242 4242 4242 4242` |
| Portal | `/my-account/view-subscription/<S-BASIC>/`, session `cust-SLT-SW-01` |

Branch-A arithmetic (`cycle_days=1`, no cycle change); T0 = `_last_payment_date` UTC, T1 = confirm time UTC:
```
d = max(0, round((T1-T0)/86400,2))   r = max(0, 1-d)
credit=round(5.00*r,2)  charge=round(15.00*r,2)  net=charge-credit
example r=0.92 -> 4.60 / 13.80 / 9.20
```

## Steps
1. `wp post meta list <S-BASIC> --allow-root`; save `_product_id`, `_recurring_amount`, `_last_payment_date`, `_next_payment_date`, `_plan_switch_history`, `_store_credit` and the pending `arraysubs_process_renewal` action id + GMT.
2. Compute `offset = crc32('arraysubs-spread-'.<S-BASIC>)%21600`; do not act between the invoice leg (due+offset−6h) and charge leg (due+offset)+30m; if a renewal just ran, re-read `_last_payment_date`.
3. `mailpit-agent latest-id` → M0. `agent-browser --session cust-SLT-SW-01 open ".../my-account/"`; log in `slt-switch` / `SltQa!2026#Pass`; open the portal URL; `snapshot -i`.
4. Click **Change Plan**; on the **Upgrade/Downgrade** tab confirm SLT Plan Pro shows the badge **Upgrade**; screenshot.
5. Click **Select** on SLT Plan Pro; record T1 and the rows **Credit for unused time**, **Charge for new plan**, **Amount due**; check against the formula.
6. Confirm → dialog **Confirm Plan Change** → **Change Plan**; the browser lands on `/checkout/order-pay/<PRO-ORDER>/?pay_for_order=true&key=…`. Record PRO-ORDER.
7. Before paying, `wp post meta get <S-BASIC> _product_id --allow-root` must still return the Basic ID.
8. Pay with the card; reach order-received; screenshot.
9. Re-dump the meta; `wp eval "print_r(wc_get_order(<PRO-ORDER>)->get_meta('_arraysubs_proration_data'));" --allow-root`; re-read the renewal action.
10. `mailpit-agent list 30`; list every message after M0. Next morning, confirm the renewal charged **$15.00**.

## Expected results
1. Preview equals the formula to the cent; Amount due = net; no switch-fee row. PRO-ORDER carries `_arraysubs_order_type=plan_switch`, `_arraysubs_switch_type=upgrade`, old=Basic, new=Pro, one fee line `Plan Upgrade to SLT Plan Pro - Proration` = net, total = net, USD, **no tax line**.
2. Before payment `_product_id` is still Basic (response was `requires_payment: true` + `checkout_url`).
3. After payment: order processing/completed, `_arraysubs_switch_processed=yes`, note "Subscription updated after proration payment."; `_product_id`=Pro, `_recurring_amount`=`15.00`, day/1 kept, title `SLT Plan Pro - Subscription #<S-BASIC>`.
4. `_next_payment_date` byte-identical to step 1; the renewal action is unscheduled+rescheduled, so its **id changes** while its GMT stays due+offset.
5. `_plan_switch_history` gains one `type=upgrade` entry; `_store_credit` unchanged; status stays `arraysubs-active`; next renewal total = $15.00.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED from the switch | confirm/apply | — | — | no listener for `arraysubs_send_plan_switch_email` (SLT-REF-08 §5); any lifecycle mail FAILS |
| 2 | WooCommerce order mail for PRO-ORDER (+ admin New order) | order paid | slt-switch@example.test / admin | `Order #<PRO-ORDER>` | the only mail allowed |

## Evidence to capture
- `SLT-SW-01-01-modal.png`, `-02-preview.png`, `-03-order-pay.png`, `-04-received.png`; S-BASIC/PRO-ORDER ids; computed vs displayed amounts; before/after meta; `_arraysubs_proration_data`; Mailpit ids.

## Pass criteria
- [ ] Preview matches the Branch-A formula; switch not applied before payment
- [ ] Proration order metas + one fee line = net, no tax; after payment product Pro, `_recurring_amount` 15.00, `_arraysubs_switch_processed=yes`
- [ ] `_next_payment_date` unchanged byte-for-byte; one upgrade entry in `_plan_switch_history`; only WooCommerce order mail

## Isolation / teardown
- Hands S-BASIC to SLT-SW-02 on **SLT Plan Pro** (auto-downgrade target Basic); do not switch it again. `slt-switch`'s second subscription (SLT Plan Pro, D4) belongs to SLT-SW-03. Nothing global changed.

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
