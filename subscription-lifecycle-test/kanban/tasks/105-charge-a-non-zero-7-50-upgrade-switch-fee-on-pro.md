---
id: 105
title: Charge a non-zero $7.50 upgrade switch fee on Pro→Enterprise and restore the fee to 0 in-task
status: done
priority: medium
created: 2026-08-02T03:43:11.73831357+02:00
updated: 2026-08-09T08:02:25.347693955+02:00
started: 2026-08-09T08:02:25.347692713+02:00
completed: 2026-08-09T08:02:25.347692713+02:00
tags:
    - plan-switching
    - day-07
due: "2026-08-09"
estimate: 1h 30m
depends_on:
    - 87
    - 104
claimed_by: delta-gate
claimed_at: 2026-08-09T08:02:25.347693865+02:00
class: standard
---

> **SLT-SW-08** · group `switching` · scheduled **D07** (2026-08-09)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove a non-zero `proration.switch_fees.upgrade` is really charged, appears as its own order line on top of the proration amount, is charged once and never folded into the recurring amount — then restore it to 0 in this task. The baseline has all three fees at 0, so this is a bracketed, self-restoring deviation: no other SLT switch may run between set and restore.

## Scope
- Gateway: Stripe test
- Checkout: N/A (order-pay page only)
- Account: existing — `slt-switch2`, on SLT Plan Pro from SLT-SW-06
- Plugins: free-only

## Preconditions
- **SLT-SW-06 complete and its D6 renewal evidence captured** — this task mutates that subscription.
- `SLT-SW-04` is `done` on the board and its admin-completed proration order is paid. Before opening this fee bracket, run the board check from the suite's `kanban/` directory and query today's `plan_switch` orders; abort if task 104 is not done or any earlier switch order is still unpaid.
- Run only after its D7 renewal has fired (`due + OFFSET`), so `_last_payment_date` is fresh and `dr` is near 1.
- Baseline `proration.switch_fees` all 0, `minimum_charge=0`, `rounding_method=round`, `proration_type=prorate_immediately`. Sessions `admin-SLT-SW-08`, `customer-SLT-SW-08`.

## Test data
| Item | Value |
|---|---|
| Products | SLT Plan Pro $15.00 day/1 → SLT Plan Enterprise $30.00 day/1 |
| Account | slt-switch2; card 4242 4242 4242 4242 |
| Setting | `proration.switch_fees.upgrade` 0 → **7.50** → 0 |
| Amounts | proration = round(30×dr,2) − round(15×dr,2); fee 7.50; total = both, where dr = max(0, 1 − round((now − `_last_payment_date`)/86400, 2)) |

## Steps
1. `MP0=$(mailpit-agent latest-id)`; `wp option get arraysubs_settings --format=json --allow-root > /home/server-manager/slt-evidence/SLT-SW-08-priors.json`. From the suite's `kanban/` directory run `kanban-md show 104 --json` and require `status=done`. Query all plan-switch orders created on 2026-08-09 and require every row older than this task to be paid/completed; save both checks under `/home/server-manager/slt-evidence/`.
2. **Open the bracket:** write the UTC open time to `/home/server-manager/slt-evidence/SLT-SW-08-bracket.txt` and post to the registry that no other SLT switch may run until it closes.
3. In `admin-SLT-SW-08`, open `#/settings/plan-switching` → **Switch Fees (Optional)** → **Upgrade Fee** = `7.50`; leave the other two at 0; Save; screenshot.
4. Verify `wp option get arraysubs_settings --allow-root | rg -o 'switch_fees.{0,120}'`.
5. Record `wp post meta list <SUB_ID> --keys=_last_payment_date,_recurring_amount,_next_payment_date --allow-root` to fix `dr`.
6. In `customer-SLT-SW-08`, log in as `slt-switch2`, require the cart and persistent-cart meta to be empty, then open `/my-account/view-subscription/<SUB_ID>/` → **Change Plan** → **SLT Plan Enterprise** → screenshot **Plan Change Summary** (it must show the fee). Immediately before **Confirm Plan Change**, save `SWITCH_PRE=$(mailpit-agent latest-id)`.
7. Record the exact pre-switch order set, click **Confirm Plan Change**, open the returned `checkout_url`, record numeric `ORDER_C` from that exact response, and require exactly one new pending plan-switch order with subscription/customer/target linkage and verified fee/proration math. Screenshot its unpopulated line items, pay 4242 without capturing populated hosted fields, capture only the safe paid receipt, then poll immutable `SWITCH_PRE` in repeated calls no longer than 60 seconds through the two-minute cutoff. Allow only WooCommerce order mail linked to exact `ORDER_C`, require zero ArraySubs switch mail, and save/show every matched message.
8. Re-open the subscription page, re-dump the metas, screenshot the Recurring Amount row.
9. **Restore-first on every exit path:** immediately after the switch attempt (successful or failed), set Upgrade Fee back to the exact prior value, Save, re-read with `rg`, and diff the full option against `/home/server-manager/slt-evidence/SLT-SW-08-priors.json`. Do not collect secondary evidence while the setting remains changed. Write the actual UTC close time to the bracket file/registry, prove the diff empty, publish numeric `SUB_ID`'s exact next invoice/charge IDs/GMTs and `charge−300s` deadline, empty/prove both customer carts, and close the D7 sessions.
10. At the final scheduled watch phase inside this subscription's exact D8 `[charge−300s, charge)` interval, save `RENEW_PRE=$(mailpit-agent latest-id)` to the registry and `/home/server-manager/slt-evidence/SLT-SW-08-renew-pre.txt`; never capture it earlier.
11. **Follow-up, D9 watch 2026-08-11:** poll immutable `RENEW_PRE` in repeated calls no longer than 60 seconds through the 10-minute cutoff for `Payment received for subscription #$SUB_ID`, save/show the exact match, and classify the complete baseline delta. Resolve the linked D8 renewal from numeric SUB_ID plus scheduled-cycle relationship and require its reverse link, never recency; confirm paid total exactly $30.00, no switch-fee line, and action logs `via WP Cron`. In fresh `admin-SLT-SW-08-R1` capture the exact order, close it, independently review bracket/switch/restore/renewal evidence, then move through `review` to `done` with Review empty. Any live defect goes only in `issues/SLT-SW-08-<concise-slug>.md` with task/stage/plan path; source/target products, user/subscription/switch/renewal/action/message IDs; user login/email/role; exact routes/sessions/bracket/gates; reproduction; expected/actual; and settings/UI/REST/meta/order/queue/log/Mailpit proof.

## Expected results
1. After step 3 `switch_fees.upgrade` is `7.5`, the other two still `0`, nothing else differs from the priors.
2. `ORDER_C` is manual/pending, `_arraysubs_order_type=plan_switch`, `_arraysubs_switch_type=upgrade`, with **two** fee lines: `Plan Upgrade to SLT Plan Enterprise - Proration` = round(30×dr,2) − round(15×dr,2), and `Plan Upgrade switch fee` = **$7.50**; total = their sum, no tax.
3. If `dr` is 0 the proration line is omitted and the order is the $7.50 fee alone — record which branch ran.
4. `_arraysubs_proration_data` holds `switch_fee: 7.5` and a `net_amount` that already includes it.
5. After payment `_product_id` = Enterprise and `_recurring_amount` = **30.00** — the fee is neither recurring nor re-charged; `_next_payment_date` is unchanged (day/1 → day/1).
6. After step 9 the settings diff against the priors is empty.
7. The D8 renewal (`due + OFFSET`) charges **$30.00** with no fee line — confirmed by the D9 watch, 2026-08-11.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE for the settings change | steps 3, 9 | — | — | Complete delta after `MP0` through step 4; zero settings-change-attributable mail, while unrelated/background mail is allowed and classified |
| 2 | NONE for the switch | steps 6-7 | — | — | distinct `SWITCH_PRE`; classify full delta and allow only Woo order mail linked to `ORDER_C` |
| 3 | payment_successful | D8 renewal | slt-switch2 | `Payment received for subscription #<SUB_ID>` | final-five-minute baseline; repeated ≤60-second polls through the 10-minute cutoff; exact match plus full delta |

## Evidence to capture
- `SLT-SW-08-01-fee-setting.png`, `-02-summary-with-fee.png`, `-03-two-fee-lines.png`, `-04-sub-after.png`, `-05-restored.png`; `SLT-SW-08-priors.json`, the restore diff, the bracket file, `ORDER_C`, the `dr` used, `SWITCH_PRE`, `RENEW_PRE`, exact-match and full-delta Mailpit ids

## Pass criteria
- [ ] Upgrade fee set to 7.50 with no other setting changed
- [ ] SLT-SW-04 was done and no earlier plan-switch order was unpaid before the bracket opened
- [ ] A separate `Plan Upgrade switch fee` line of exactly $7.50 on `ORDER_C`
- [ ] Proration line = round(30×dr,2) − round(15×dr,2), or absent when dr = 0
- [ ] After payment: Enterprise, `_recurring_amount=30.00`, fee not recurring, `_next_payment_date` unchanged
- [ ] Fee restored to 0; settings diff against the priors is empty
- [ ] No ArraySubs mail for the setting change or the switch
- [ ] D8 renewal charged $30.00 with no switch-fee line and its exact D9 mail delta is reconciled
- [ ] Restore-first executed on every path; exact phase sessions close and independent review reaches `done` with Review empty

## Isolation / teardown
- Restores `switch_fees.upgrade` to 0 in step 9; the bracket file and registry record the window in which the fee was live, so any other task's switch inside it can be attributed. No other switch is allowed inside the bracket.
- Leaves slt-switch2 active on SLT Plan Enterprise at $30.00/day; SLT-SETUP-99B deletes it with the plan-rung cohort.

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

[[2026-08-06]] Thu 20:38
Source-block note on 2026-08-06: this fee-bracket task depends on card 104 / SLT-SW-04 being done, and that comparison task is currently source-blocked behind card 86 / SLT-SW-01 and the missing card-72 ladder fixtures. Do not open this bracket until that full chain exists on a later valid execution.

[[2026-08-09]] Sun 08:02
D07 post-noon final source recheck at 2026-08-09 12:01:44 UTC+6 — UNVERIFIED. /home/server-manager/slt-evidence/SLT-SW-08-D07-source-recheck.txt proves no exact slt-switch2 user or relationship-owned SLT Plan Pro subscription. Card 104 is done/UNVERIFIED only as execution closure and provides no valid comparison baseline. Thus the SLT-SW-06 renewal evidence, fresh payment anchor, and paid SLT-SW-04 dependency are absent. The fee bracket was never opened; proration.switch_fees remains 0/0/0, and no setting, session, switch order, payment, mail baseline, or schedule was changed. Future impact: no USD 7.50 switch-fee assertion or later USD 30 fee-free renewal is expected during this watch; a future run must rebuild the ladder and valid dependencies first.

[[2026-08-09]] Sun 12:17
Independent-review correction: discard the original subscription-count SQL because it filtered `arraysubs_subscription`; exact `slt-switch2` user lookup is `[]`, so no numeric owner exists for a relationship query. The correct proration-type path is `plan_switching.proration_type=prorate_immediately`; the separately queried `proration.switch_fees=0/0/0` and `proration.minimum_charge=0` remain valid. The UNVERIFIED closure is unchanged. Full correction: `/home/server-manager/slt-evidence/SLT-SW-08-D07-source-recheck.txt`.
