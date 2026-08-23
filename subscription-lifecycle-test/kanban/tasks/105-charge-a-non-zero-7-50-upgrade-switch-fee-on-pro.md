---
id: 105
title: Charge a non-zero $7.50 upgrade switch fee on Pro→Enterprise and restore the fee to 0 in-task
status: todo
priority: medium
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - plan-switching
    - day-07
due: "2026-08-30"
estimate: 1h 30m
depends_on:
    - 87
    - 104
claimed_by: delta-gate
class: standard
---

> **SLT-SW-08** · group `switching` · scheduled **D07** (2026-08-30)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove a non-zero `proration.switch_fees.upgrade` is really charged, appears as its own order line on top of the proration amount, is charged once and never folded into the recurring amount — then restore it to 0 in this task. The baseline has all three fees at 0, so this is a bracketed, self-restoring deviation: no other SLT2 switch may run between set and restore.

## Scope
- Gateway: Stripe test
- Checkout: N/A (order-pay page only)
- Account: existing — `slt2-switch2`, on SLT2 Plan Pro from SLT-SW-06
- Plugins: free-only

## Preconditions
- **SLT-SW-06 complete and its D6 renewal evidence captured** — this task mutates that subscription.
- `SLT-SW-04` is `done` on the board and its admin-completed proration order is paid. Before opening this fee bracket, run the board check from the suite's `kanban/` directory and query today's `plan_switch` orders; abort if task 104 is not done or any earlier switch order is still unpaid.
- Run only after its D7 renewal has fired (`due + OFFSET`), so `_last_payment_date` is fresh and `dr` is near 1.
- Baseline `proration.switch_fees` all 0, `minimum_charge=0`, `rounding_method=round`, `proration_type=prorate_immediately`. Sessions `admin-SLT-SW-08`, `customer-SLT-SW-08`.

## Test data
| Item | Value |
|---|---|
| Products | SLT2 Plan Pro $15.00 day/1 → SLT2 Plan Enterprise $30.00 day/1 |
| Account | slt2-switch2; card 4242 4242 4242 4242 |
| Setting | `proration.switch_fees.upgrade` 0 → **7.50** → 0 |
| Amounts | proration = round(30×dr,2) − round(15×dr,2); fee 7.50; total = both, where dr = max(0, 1 − round((now − `_last_payment_date`)/86400, 2)) |

## Steps
1. `MP0=$(mailpit-agent latest-id)`; `wp option get arraysubs_settings --format=json --allow-root > /home/server-manager/slt-evidence/SLT-SW-08-priors.json`. From the suite's `kanban/` directory run `kanban-md show 104 --json` and require `status=done`. Query all plan-switch orders created on 2026-08-30 and require every row older than this task to be paid/completed; save both checks under `/home/server-manager/slt-evidence/`.
2. **Open the bracket:** write the UTC open time to `/home/server-manager/slt-evidence/SLT-SW-08-bracket.txt` and post to the registry that no other SLT2 switch may run until it closes.
3. In `admin-SLT-SW-08`, open `#/settings/plan-switching` → **Switch Fees (Optional)** → **Upgrade Fee** = `7.50`; leave the other two at 0; Save; screenshot.
4. Verify `wp option get arraysubs_settings --allow-root | rg -o 'switch_fees.{0,120}'`.
5. Record `wp post meta list <SUB_ID> --keys=_last_payment_date,_recurring_amount,_next_payment_date --allow-root` to fix `dr`.
6. In `customer-SLT-SW-08`, log in as `slt2-switch2`, require the cart and persistent-cart meta to be empty, then open `/my-account/view-subscription/<SUB_ID>/` → **Change Plan** → **SLT2 Plan Enterprise** → screenshot **Plan Change Summary** (it must show the fee). Immediately before **Confirm Plan Change**, save `SWITCH_PRE=$(mailpit-agent latest-id)`.
7. Record the exact pre-switch order set, click **Confirm Plan Change**, open the returned `checkout_url`, record numeric `ORDER_C` from that exact response, and require exactly one new pending plan-switch order with subscription/customer/target linkage and verified fee/proration math. Screenshot its unpopulated line items, pay 4242 without capturing populated hosted fields, capture only the safe paid receipt, then poll immutable `SWITCH_PRE` in repeated calls no longer than 60 seconds through the two-minute cutoff. Allow only WooCommerce order mail linked to exact `ORDER_C`, require zero ArraySubs switch mail, and save/show every matched message.
8. Re-open the subscription page, re-dump the metas, screenshot the Recurring Amount row.
9. **Restore-first on every exit path:** immediately after the switch attempt (successful or failed), set Upgrade Fee back to the exact prior value, Save, re-read with `rg`, and diff the full option against `/home/server-manager/slt-evidence/SLT-SW-08-priors.json`. Do not collect secondary evidence while the setting remains changed. Write the actual UTC close time to the bracket file/registry, prove the diff empty, publish numeric `SUB_ID`'s exact next invoice/charge IDs/GMTs and `charge−300s` deadline, empty/prove both customer carts, and close the D7 sessions.
10. At the final scheduled watch phase inside this subscription's exact D8 `[charge−300s, charge)` interval, save `RENEW_PRE=$(mailpit-agent latest-id)` to the registry and `/home/server-manager/slt-evidence/SLT-SW-08-renew-pre.txt`; never capture it earlier.
11. **Follow-up, D9 watch 2026-09-01:** poll immutable `RENEW_PRE` in repeated calls no longer than 60 seconds through the 10-minute cutoff for `Payment received for subscription #$SUB_ID`, save/show the exact match, and classify the complete baseline delta. Resolve the linked D8 renewal from numeric SUB_ID plus scheduled-cycle relationship and require its reverse link, never recency; confirm paid total exactly $30.00, no switch-fee line, and action logs `via WP Cron`. In fresh `admin-SLT-SW-08-R1` capture the exact order, close it, independently review bracket/switch/restore/renewal evidence, then move through `review` to `done` with Review empty. Any live defect goes only in `qa/issues/` kanban card named `SLT-SW-08-<concise-slug>` with task/stage/plan path; source/target products, user/subscription/switch/renewal/action/message IDs; user login/email/role; exact routes/sessions/bracket/gates; reproduction; expected/actual; and settings/UI/REST/meta/order/queue/log/Mailpit proof.

## Expected results
1. After step 3 `switch_fees.upgrade` is `7.5`, the other two still `0`, nothing else differs from the priors.
2. `ORDER_C` is manual/pending, `_arraysubs_order_type=plan_switch`, `_arraysubs_switch_type=upgrade`, with **two** fee lines: `Plan Upgrade to SLT2 Plan Enterprise - Proration` = round(30×dr,2) − round(15×dr,2), and `Plan Upgrade switch fee` = **$7.50**; total = their sum, no tax.
3. If `dr` is 0 the proration line is omitted and the order is the $7.50 fee alone — record which branch ran.
4. `_arraysubs_proration_data` holds `switch_fee: 7.5` and a `net_amount` that already includes it.
5. After payment `_product_id` = Enterprise and `_recurring_amount` = **30.00** — the fee is neither recurring nor re-charged; `_next_payment_date` is unchanged (day/1 → day/1).
6. After step 9 the settings diff against the priors is empty.
7. The D8 renewal (`due + OFFSET`) charges **$30.00** with no fee line — confirmed by the D9 watch, 2026-09-01.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE for the settings change | steps 3, 9 | — | — | Complete delta after `MP0` through step 4; zero settings-change-attributable mail, while unrelated/background mail is allowed and classified |
| 2 | NONE for the switch | steps 6-7 | — | — | distinct `SWITCH_PRE`; classify full delta and allow only Woo order mail linked to `ORDER_C` |
| 3 | payment_successful | D8 renewal | slt2-switch2 | `Payment received for subscription #<SUB_ID>` | final-five-minute baseline; repeated ≤60-second polls through the 10-minute cutoff; exact match plus full delta |

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
- Leaves slt2-switch2 active on SLT2 Plan Enterprise at $30.00/day; SLT-SETUP-99B deletes it with the plan-rung cohort.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
