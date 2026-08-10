---
id: 111
title: Customer downgrade applies now, and targeted D8 expiry auto-downgrade with its email
status: done
priority: high
created: 2026-08-02T03:43:12.233708157+02:00
updated: 2026-08-10T02:46:10.324647769+02:00
started: 2026-08-10T02:46:10.201663042+02:00
completed: 2026-08-10T02:46:10.201663042+02:00
tags:
    - plan-switching
    - day-08
due: "2026-08-10"
estimate: 1h 45m
depends_on:
    - 86
    - 95
    - 60
    - 99
class: standard
---

> **SLT-SW-02** · group `switching` · scheduled **D08** (2026-08-10)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Two legs on D8, the only authorised time-travel day. **A:** downgrade `SUB_PRO` from **SLT Plan Peer** ($15.00 day/1) to **SLT Plan Basic** ($5.00 day/1) — prove `auto_downgrade_timing = on_expire` does not defer a customer switch, that `prorate_immediately` applies it at once with net 0 plus a store credit, and that nothing is pending afterwards. **B:** expire `SUB_BASIC` (on Pro since SLT-SW-01) for the true `on_expire` auto-downgrade and its email.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (`slt-switch`)
- Plugins: both (store credit is pro)

## Preconditions
- SLT-SW-03 done: `SUB_PRO` on **SLT Plan Peer** (downgrade list [Basic]). SLT-SW-01 done: `SUB_BASIC` on **Pro** (`_arraysubs_auto_downgrade_product` = Basic).
- Run only after `SLT-TT-00` and the D8 follow-up of `SLT-SYN-10` are complete. Quote the shared non-SLT schedule baseline and `SLT-SYN-10`'s empty post-target diff before changing either subscription.
- Screenshot Tools → Scheduled Actions (Pending) first. Existing future non-SLT actions are expected on this shared site and are not blockers; abort only if a non-SLT action is already overdue or if the selected target is not exactly `SUB_BASIC`. Never drain a hook.

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
2. In `agent-browser --session cust-SLT-SW-02`, log in as `slt-switch` / `SltQa!2026#Pass`; open `SUB_PRO`'s portal page; **Change Plan** → **Upgrade/Downgrade**; SLT Plan Basic must show the badge **Downgrade**.
3. **Select** it; record T1, the three preview rows and the "You will receive a credit of $X" line; Confirm → **Change Plan**. Expect the toast "Your plan has been changed successfully!" and a reload — NOT a redirect to order-pay.
4. Re-dump numeric `$SUB_PRO`; compare exact before/after HPOS order sets and require no new order; its admin detail shows no pending-switch UI. Poll/classify the complete M0 delta in ≤60-second calls through two minutes, requiring only the exact store-credit mail and no switch/order mail. Set `M1=$(mailpit-agent latest-id)` immediately before Leg B.
5. Leg B: record the UTC command time, then `wp post meta update "$SUB_BASIC" _end_date "$(date -u -d '+2 minutes' '+%Y-%m-%d %H:%M:%S')" --allow-root` and fire that one subscription only: `wp eval "do_action('arraysubs_expire_subscription', (int) $SUB_BASIC);" --allow-root`. This is a targeted hook invocation, not an Action Scheduler row; do not invent or expect an action ID.
6. Re-dump numeric `$SUB_BASIC` meta/notes; capture exact pending invoice/charge rows and publish their gates. Poll immutable M1 in repeated calls no longer than 60 seconds through the five-minute cutoff for `has been changed to`; save/show the exact match/full delta and hand it to SLT-EML-08. Re-run the shared non-SLT diff empty, close `cust-SLT-SW-02`, and leave the card in progress.
7. Before the D9 manual-renewal gate, publish its exact action/deadline and take any required baseline in the final five minutes. After the gate, resolve the order from numeric SUB_BASIC plus scheduled-cycle relationship/reverse link, record whether it remained pending/manual, and reconcile its exact mail delta without force-running. In fresh `admin-SLT-SW-02-D9`, capture proof, close it, independently review both legs/follow-up, then move through `review` to `done` with Review empty. Any defect goes only in `issues/SLT-SW-02-<concise-slug>.md` with task/stage/plan path; products/subscriptions/orders/actions/messages; user ID/login/email/role; exact routes/sessions/hook time/gates; reproduction; expected/actual; and UI/meta/order/queue/log/Mailpit/non-SLT-diff proof.

## Expected results
1. Leg A classifies as `downgrade` (5.00 < 15.00 − 5 %) and applies **immediately**: `_product_id`=Basic, `_recurring_amount`=`5.00`. `auto_downgrade_timing` gates only `AutoDowngradeHandler`, never a customer switch.
2. No proration order (net 0 ⇒ `createProrationOrder()` never runs), no redirect, no payment. `SUB_PRO` `_next_payment_date` unchanged, `_plan_switch_history` gains `type=downgrade`, nothing shows as pending (that exists only under `apply_at_renewal`).
3. `_store_credit` = refund_amount. **If it reads 2 × refund_amount, write a standalone issue file under `issues/`**: `createDowngradeCredit()` writes `_store_credit`, then `arraysubs_downgrade_credit_created` → `addSubscriptionCredit()` adds it again to the same key.
4. Leg B: `SUB_BASIC` passes through `arraysubs-expired` and returns **`arraysubs-active`** on SLT Plan Basic, `_recurring_amount`=`5.00`, `_next_payment_date` = expiry + 1 day, note "Auto-downgraded from SLT Plan Pro to SLT Plan Basic due to subscription expiration."
5. Leg B detaches automatic billing: `_gateway_status=detached`, `_payment_gateway` and `_gateway_payment_method_id` deleted ⇒ the D9 renewal is manual.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `arraysubs_credit_added` (pro) | Leg A credit | slt-switch@example.test | `Store credit added` | `mailpit-agent list` |
| 2 | NONE for the downgrade | Leg A | — | — | no switch listener, no order, no order mail |
| 3 | `auto_downgrade` | Leg B | slt-switch@example.test | `has been changed to SLT Plan Basic` | immutable-baseline polls ≤60 seconds through the five-minute cutoff |
| 4 | NONE: `subscription_expired` / `subscription_activated` | Leg B | — | — | suppressed by `isProcessing()`; presence FAILS |

## Evidence to capture
- `SLT-SW-02-01-preview.png`, `-02-after.png`, `-03-no-pending.png`, `-04-queue.png`, `-05-notes.png`; both ids; before/after meta; Mailpit ids.

## Pass criteria
- [ ] Downgrade applies immediately: no order, no payment, nothing pending
- [ ] `_next_payment_date` unchanged; history `downgrade`; `_store_credit` = refund_amount
- [ ] Expiry auto-downgrades to Basic and re-activates with a new next payment date
- [ ] `auto_downgrade` mail arrives; no expired/activated mail; no non-SLT action moved
- [ ] D9 exact manual-renewal outcome, phase sessions, standalone findings, and review close with Review empty

## Isolation / teardown
- Both end on SLT Plan Basic; SLT-SETUP-99A cancels them on D10. The `_end_date` from step 5 is consumed by the expiry — do not re-add it. Nothing global changed.

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

[[2026-08-06]] Thu 20:42
Source-block note on 2026-08-06: this D8 switch/downgrade task requires both card 95 / SLT-SW-03 and card 86 / SLT-SW-01 to have completed first. Those upstream ladder fixtures are currently missing because card 72 / SLT-SW-00 closed UNVERIFIED, so this task cannot start until a later valid execution recreates the ladder chain and publishes real SUB_PRO/SUB_BASIC fixtures.

[[2026-08-10]] Mon 06:44
D08 final execution closeout is `UNVERIFIED` at the authored numeric source guard. Fresh exact resolution proves user 349 (`slt-switch` / `slt-switch@example.test`, customer) owns zero `arraysubs_data` relationships in total and zero relationships to Basic 12608, Pro 12611, or Peer 12620. Thus neither `SUB_BASIC` nor `SUB_PRO` resolves numerically; published products do not substitute for missing customer-owned subscriptions. Upstream cards 86 and 95 are done but both closed their authored flows `UNVERIFIED` for the same absent ladder seed. No customer switch, `_end_date` write, targeted expiry hook, order, queue row, subscription, setting, product, or Mailpit mutation occurred, and no D9 manual-renewal gate/action can be carried. Prior authenticated evidence `/home/server-manager/slt-evidence/SLT-SW-03-no-source.png` already shows the exact account notice `You have no subscriptions yet.` A D08 read-only login attempt did not authenticate and its non-proof login screenshot was removed from evidence and quarantined at `/tmp/SLT-SW-02-D08-login-nonproof.png`; it is not relied on. Full closeout: `/home/server-manager/slt-evidence/SLT-SW-02-D08-source-block.txt`. This is a source-fixture coverage miss, not observed product behavior, so no issue was filed.
