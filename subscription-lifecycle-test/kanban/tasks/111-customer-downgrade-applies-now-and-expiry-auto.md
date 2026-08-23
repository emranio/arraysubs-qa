---
id: 111
title: Customer downgrade applies now, and targeted D8 expiry auto-downgrade with its email
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - plan-switching
    - day-08
due: "2026-08-31"
estimate: 1h 45m
depends_on:
    - 86
    - 95
    - 60
    - 99
class: standard
---

> **SLT-SW-02** · group `switching` · scheduled **D08** (2026-08-31)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Two legs on D8, the only authorised time-travel day. **A:** downgrade `SUB_PRO` from **SLT2 Plan Peer** ($15.00 day/1) to **SLT2 Plan Basic** ($5.00 day/1) — prove `auto_downgrade_timing = on_expire` does not defer a customer switch, that `prorate_immediately` applies it at once with net 0 plus a store credit, and that nothing is pending afterwards. **B:** expire `SUB_BASIC` (on Pro since SLT-SW-01) for the true `on_expire` auto-downgrade and its email.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (`slt2-switch`)
- Plugins: both (store credit is pro)

## Preconditions
- SLT-SW-03 done: `SUB_PRO` on **SLT2 Plan Peer** (downgrade list [Basic]). SLT-SW-01 done: `SUB_BASIC` on **Pro** (`_arraysubs_auto_downgrade_product` = Basic).
- Run only after `SLT-TT-00` and the D8 follow-up of `SLT-SYN-10` are complete. Quote the shared non-SLT2 schedule baseline and `SLT-SYN-10`'s empty post-target diff before changing either subscription.
- Screenshot Tools → Scheduled Actions (Pending) first. Existing future non-SLT2 actions are expected on this shared site and are not blockers; abort only if a non-SLT2 action is already overdue or if the selected target is not exactly `SUB_BASIC`. Never drain a hook.

## Test data
| Item | Value |
|---|---|
| Leg A | `SUB_PRO`: Peer $15.00 → Basic $5.00 |
| Leg B | `SUB_BASIC` on Pro $15.00, expiry → Basic |
| Portal | `/my-account/view-subscription/<id>/`, session `cust-SLT-SW-02` |

Leg-A arithmetic (Branch A, `cycle_days=1`), r = 1 − days since `_last_payment_date`:
```
credit=round(15.00*r,2)  charge=round(5.00*r,2)  net=charge-credit < 0
=> refund_amount=|net|, net_amount=0 -> no proration order
```

## Steps
1. Resolve registry aliases `SUB_PRO` and `SUB_BASIC` into same-named shell variables and abort unless both match `^[0-9]+$`. Dump both subscriptions (`_product_id`, `_recurring_amount`, `_next_payment_date`, `_last_payment_date`, `_store_credit`, `_plan_switch_history`, `_payment_gateway`); set `M0=$(mailpit-agent latest-id)`.
2. In `agent-browser --session cust-SLT-SW-02`, log in as `slt2-switch` / `SltQa!2026#Pass`; open `SUB_PRO`'s portal page; **Change Plan** → **Upgrade/Downgrade**; SLT2 Plan Basic must show the badge **Downgrade**.
3. **Select** it; record T1, the three preview rows and the "You will receive a credit of $X" line; Confirm → **Change Plan**. Expect the toast "Your plan has been changed successfully!" and a reload — NOT a redirect to order-pay.
4. Re-dump numeric `$SUB_PRO`; compare exact before/after HPOS order sets and require no new order; its admin detail shows no pending-switch UI. Poll/classify the complete M0 delta in ≤60-second calls through two minutes, requiring only the exact store-credit mail and no switch/order mail. Set `M1=$(mailpit-agent latest-id)` immediately before Leg B.
5. Leg B: record the UTC command time, then `wp post meta update "$SUB_BASIC" _end_date "$(date -u -d '+2 minutes' '+%Y-%m-%d %H:%M:%S')" --allow-root` and fire that one subscription only: `wp eval "do_action('arraysubs_expire_subscription', (int) $SUB_BASIC);" --allow-root`. This is a targeted hook invocation, not an Action Scheduler row; do not invent or expect an action ID.
6. Re-dump numeric `$SUB_BASIC` meta/notes; capture exact pending invoice/charge rows and publish their gates. Poll immutable M1 in repeated calls no longer than 60 seconds through the five-minute cutoff for `has been changed to`; save/show the exact match/full delta and hand it to SLT-EML-08. Re-run the shared non-SLT2 diff empty, close `cust-SLT-SW-02`, and leave the card in progress.
7. Before the D9 manual-renewal gate, publish its exact action/deadline and take any required baseline in the final five minutes. After the gate, resolve the order from numeric SUB_BASIC plus scheduled-cycle relationship/reverse link, record whether it remained pending/manual, and reconcile its exact mail delta without force-running. In fresh `admin-SLT-SW-02-D9`, capture proof, close it, independently review both legs/follow-up, then move through `review` to `done` with Review empty. Any defect goes only in `qa/issues/` kanban card named `SLT-SW-02-<concise-slug>` with task/stage/plan path; products/subscriptions/orders/actions/messages; user ID/login/email/role; exact routes/sessions/hook time/gates; reproduction; expected/actual; and UI/meta/order/queue/log/Mailpit/non-SLT-diff proof.

## Expected results
1. Leg A classifies as `downgrade` (5.00 < 15.00 − 5 %) and applies **immediately**: `_product_id`=Basic, `_recurring_amount`=`5.00`. `auto_downgrade_timing` gates only `AutoDowngradeHandler`, never a customer switch.
2. No proration order (net 0 ⇒ `createProrationOrder()` never runs), no redirect, no payment. `SUB_PRO` `_next_payment_date` unchanged, `_plan_switch_history` gains `type=downgrade`, nothing shows as pending (that exists only under `apply_at_renewal`).
3. `_store_credit` = refund_amount. **If it reads 2 × refund_amount, write a QA issue card under `qa/issues/`**: `createDowngradeCredit()` writes `_store_credit`, then `arraysubs_downgrade_credit_created` → `addSubscriptionCredit()` adds it again to the same key.
4. Leg B: `SUB_BASIC` passes through `arraysubs-expired` and returns **`arraysubs-active`** on SLT2 Plan Basic, `_recurring_amount`=`5.00`, `_next_payment_date` = expiry + 1 day, note "Auto-downgraded from SLT2 Plan Pro to SLT2 Plan Basic due to subscription expiration."
5. Leg B detaches automatic billing: `_gateway_status=detached`, `_payment_gateway` and `_gateway_payment_method_id` deleted ⇒ the D9 renewal is manual.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `arraysubs_credit_added` (pro) | Leg A credit | slt2-switch@example.test | `Store credit added` | `mailpit-agent list` |
| 2 | NONE for the downgrade | Leg A | — | — | no switch listener, no order, no order mail |
| 3 | `auto_downgrade` | Leg B | slt2-switch@example.test | `has been changed to SLT2 Plan Basic` | immutable-baseline polls ≤60 seconds through the five-minute cutoff |
| 4 | NONE: `subscription_expired` / `subscription_activated` | Leg B | — | — | suppressed by `isProcessing()`; presence FAILS |

## Evidence to capture
- `SLT-SW-02-01-preview.png`, `-02-after.png`, `-03-no-pending.png`, `-04-queue.png`, `-05-notes.png`; both ids; before/after meta; Mailpit ids.

## Pass criteria
- [ ] Downgrade applies immediately: no order, no payment, nothing pending
- [ ] `_next_payment_date` unchanged; history `downgrade`; `_store_credit` = refund_amount
- [ ] Expiry auto-downgrades to Basic and re-activates with a new next payment date
- [ ] `auto_downgrade` mail arrives; no expired/activated mail; no non-SLT2 action moved
- [ ] D9 exact manual-renewal outcome, phase sessions, QA issue cards, and review close with Review empty

## Isolation / teardown
- Both end on SLT2 Plan Basic; SLT-SETUP-99A cancels them on D11. The `_end_date` from step 5 is consumed by the expiry — do not re-add it. Nothing global changed.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
