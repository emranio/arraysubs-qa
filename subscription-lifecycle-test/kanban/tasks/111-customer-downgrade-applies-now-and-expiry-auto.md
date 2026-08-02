---
id: 111
title: Customer downgrade applies now, and expiry auto-downgrade with its email on the D8 drain day
status: todo
priority: high
created: 2026-08-02T03:43:12.233708157+02:00
updated: 2026-08-02T03:43:23.655492406+02:00
tags:
    - plan-switching
    - day-08
    - has-conflicts
due: "2026-08-10"
estimate: 1h 45m
depends_on:
    - 86
    - 95
    - 60
class: standard
---

> **SLT-SW-02** · group `switching` · scheduled **D08** (2026-08-10)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · shared-global-setting / same-day bracket collision** — with `SLT-SW-08`, `SLT-SW-04`, `SLT-ADM-01`, `SLT-MYA-04`, `SLT-DUN-05`

- *Problem:* SLT-SW-08 (d7) sets proration.switch_fees.upgrade from 0 to 7.50 globally and restores it in the same task, declaring 'no other SLT switch may run between set and restore'. SLT-SW-04 (d7) performs a Basic->Pro upgrade the same day and asserts its proration order matches SLT-SW-01's record-for-record with 'no switch-fee row'. If SW-04 runs inside SW-08's bracket its order gains a $7.50 'Plan Upgrade switch fee' line and the comparison fails for the wrong reason. The bracket file exists but nothing sequences the two tasks.
- *Required fix:* Fix the D7 order explicitly in the calendar and in both task bodies: SLT-SW-04 completes and its proration order is PAID before SLT-SW-08 opens its bracket. SW-08's step 2 gains a pre-flight assertion: 'SLT-SW-04 is done on the board and no plan_switch order created today is still unpaid'. SW-08's bracket file must record open/close UTC and be posted to the registry so any switch order created inside it can be attributed and re-run.

**`high` · same-subscription collision / duplicate coverage** — with `SLT-EML-08`, `SLT-SW-03`, `SLT-SW-01`, `SLT-PROD-11`

- *Problem:* Both tasks run on D8 and both drive the on_expire auto-downgrade of a slt-switch plan-ladder subscription. SLT-EML-08 step 5 sets _end_date on 'S_PRO - slt-switch's active Pro subscription' and fires arraysubs_expire_subscription to capture the auto_downgrade email and the expired-suppression negative. SLT-SW-02 Leg B does exactly the same on 'S-BASIC (on Pro since SLT-SW-01)'. There are only two slt-switch ladder subscriptions and SLT-SW-03 (d6) already crossgraded the other one (S-PRO) off Pro onto SLT Plan Peer - at which point Pro's _arraysubs_auto_downgrade_product no longer applies to it and EML-08's leg is unrunnable as written. Whichever task expires the remaining Pro subscription first consumes the other's canvas.
- *Required fix:* Single owner: SLT-SW-02 Leg B owns the hand-set _end_date and the expiry of S-BASIC (which SLT-SW-01 left on SLT Plan Pro). SLT-EML-08 becomes observation-only for that leg - it reads the auto_downgrade mail ('has been changed to SLT Plan Basic'), proves the subscription_expired suppression negative (EmailManager.php:317-322) and confirms S-BASIC re-activated on Basic at $5.00 - and runs strictly after SW-02 in the D8 order. Delete EML-08 steps 4-5 (queue screenshot + _end_date write) and replace with 'quote SLT-SW-02's pre-flight queue screenshot and _end_date timestamp'. Update EML-08's Test data to name S-BASIC, not S_PRO.

**`high` · dependency-gap / unowned purchases** — with `SLT-ADM-07`, `SLT-MYA-04`, `SLT-ADM-08`, `SLT-SW-01`, `SLT-SW-03`, `SLT-EML-08`

- *Problem:* Five purchases that multiple tasks treat as preconditions are owned by no task key in the index - they existed only as free-text 'purchases owned by other groups' rows in the superseded calendar. (a) S_FEE: slt-core's SLT Signup Fee Daily subscription, required by SLT-ADM-07 ('bought D3 by slt-core'), SLT-MYA-04 and SLT-ADM-08 (which refunds and cancels it). (b) S-BASIC and S-PRO: slt-switch's SLT Plan Basic and SLT Plan Pro subscriptions 'bought D4', required by SLT-SW-01, SW-03, SW-02 and SLT-EML-08. (c) SLT Flex Month Segments segment-3 by slt-flex3 on 2026-08-08, required by SLT-SYN-10 (SUB_S3, _next_payment_date 2026-09-30 18:00:00). (d) The D8 time-travel renewals for month segment-1/segment-2, week segment-3 (SLT-SYN-07's tail, due 2026-08-15) and the flex-variable tail - audit C17 mandates one dedicated D8 owner and none exists. (e) SLT-SYN-10 also references SUB_S2 which SLT-SYN-06 does buy, so only seg-3 is missing.
- *Required fix:* Assign explicit owners. Add step 0 to SLT-ADM-07: 'slt-core buys SLT Signup Fee Daily on D3 after 12:00 (order + subscription ids to the registry)'. Create SLT-SW-00 on D4: 'slt-switch buys SLT Plan Basic and SLT Plan Pro on Stripe after 12:00' as the ladder canvas for SW-01/02/03 and EML-08. Add step 0 to SLT-SYN-10: 'slt-flex3 buys SLT Flex Month Segments on 2026-08-08 (D6) after 12:00 - day-in-cycle 8, past both boundaries, resolves to segment 3, next payment 2026-10-01 00:00 site = 2026-09-30 18:00 UTC'. Create SLT-TT-00 on D8 as the single time-travel owner: pre-flight pending-queue screenshot + the 13 non-SLT _next_payment_date snapshot, then the month seg1/seg2 and week seg3 renewals and the flex-variable tail, single-action-by-id only, then the post-drain non-SLT diff proof - and have SYN-10, SW-02, EML-08, EML-10 and LIFE-01 quote its snapshot instead of each taking their own.

**`medium` · impossible-timing / single-day contention** — with `SLT-LIFE-01`, `SLT-SYN-10`, `SLT-EML-08`, `SLT-EML-10`, `SLT-EML-14`, `SLT-DUN-05`

- *Problem:* D8 (2026-08-10) is the single authorized time-travel day and six tasks are stacked on it, each of which demands exclusive control of the pending Action Scheduler queue: SLT-SYN-10 (runs one month-renewal action by id and must prove no non-SLT date moved), SLT-SW-02 Leg B (hand-set _end_date + expire), SLT-EML-08 (expects an empty pending queue for its own _end_date write), SLT-EML-10 (queues an expiring-soon action in the past and runs it), SLT-LIFE-01 (back-dates S5's legs twice and leaves the queue empty for up to 3h waiting for the recovery sweep), SLT-EML-14 (read-only sweep whose whole value is that nothing moved). Each takes its own 'abort if a non-SLT action is due within 24h' pre-flight, and each would abort on the others' queued work. Run in any order but the right one, they invalidate each other's proofs.
- *Required fix:* Fix a strict D8 running order in the calendar and make it a precondition line in each body: (0) SLT-TT-00 pre-flight - one shared pending-queue screenshot plus the 13 non-SLT _next_payment_date snapshot, published to the registry and quoted by every other D8 task instead of re-taken; (1) SLT-TT-00 executes the month seg1/seg2 + week seg3 + flex-variable-tail renewals; (2) SLT-SYN-10 (month overflow, one action by id); (3) SLT-SW-02 (Leg A downgrade, then Leg B expiry auto-downgrade); (4) SLT-EML-08 (observes SW-02 Leg B; reactivates S_EML); (5) SLT-EML-10 (expiring-soon + card-expiring probes; cancels S_EML at teardown); (6) SLT-LIFE-01 (late-renewal phases A and B on S5 - last, because Phase B deliberately leaves S5 with zero legs and a past date for up to 3h); (7) SLT-EML-14 (read-only negative sweep, after everything). Close the day with the shared post-drain non-SLT diff.

---
## Objective
Two legs on D8, the only authorised time-travel day. **A:** downgrade S-PRO from **SLT Plan Peer** ($15.00 day/1) to **SLT Plan Basic** ($5.00 day/1) — prove `auto_downgrade_timing = on_expire` does not defer a customer switch, that `prorate_immediately` applies it at once with net 0 plus a store credit, and that nothing is pending afterwards. **B:** expire S-BASIC (on Pro since SLT-SW-01) for the true `on_expire` auto-downgrade and its email.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (`slt-switch`)
- Plugins: both (store credit is pro)

## Preconditions
- SLT-SW-03 done: S-PRO on **SLT Plan Peer** (downgrade list [Basic]). SLT-SW-01 done: S-BASIC on **Pro** (`_arraysubs_auto_downgrade_product` = Basic).
- Screenshot Tools → Scheduled Actions (Pending) first; ABORT if a non-SLT action is due within 24 h; never drain a hook.

## Test data
| Item | Value |
|---|---|
| Leg A | S-PRO: Peer $15.00 → Basic $5.00 |
| Leg B | S-BASIC on Pro $15.00, expiry → Basic |
| Portal | `/my-account/view-subscription/<id>/`, session `cust-SW-02` |

Leg-A arithmetic (Branch A, `cycle_days=1`), r = 1 − days since `_last_payment_date`:
```
credit=round(15.00*r,2)  charge=round(5.00*r,2)  net=charge-credit < 0
=> refund_amount=|net|, net_amount=0 -> no proration order
```

## Steps
1. Dump both subscriptions (`_product_id`, `_recurring_amount`, `_next_payment_date`, `_last_payment_date`, `_store_credit`, `_plan_switch_history`, `_payment_gateway`); `latest-id` → M0.
2. Log in as `slt-switch` / `SltQa!2026#Pass`; open S-PRO's portal page; **Change Plan** → **Upgrade/Downgrade**; SLT Plan Basic must show the badge **Downgrade**.
3. **Select** it; record T1, the three preview rows and the "You will receive a credit of $X" line; Confirm → **Change Plan**. Expect the toast "Your plan has been changed successfully!" and a reload — NOT a redirect to order-pay.
4. Re-dump S-PRO; the HPOS orders screen for `slt-switch` shows no new order; its admin detail screen shows no "Plan switch at next renewal" badge and no **Pending Plan Switch** card. `latest-id` → M1.
5. Leg B: `wp post meta update <S-BASIC> _end_date "$(date -u -d '+2 minutes' '+%Y-%m-%d %H:%M:%S')" --allow-root`, then fire that one subscription: `wp eval "do_action('arraysubs_expire_subscription', <S-BASIC>);" --allow-root`.
6. Re-dump S-BASIC meta + notes; capture its new pending `arraysubs_process_renewal` action; `mailpit-agent wait-new M1 300 "has been changed to"`. On D9 record whether its renewal charged or produced a manual order.

## Expected results
1. Leg A classifies as `downgrade` (5.00 < 15.00 − 5 %) and applies **immediately**: `_product_id`=Basic, `_recurring_amount`=`5.00`. `auto_downgrade_timing` gates only `AutoDowngradeHandler`, never a customer switch.
2. No proration order (net 0 ⇒ `createProrationOrder()` never runs), no redirect, no payment. S-PRO `_next_payment_date` unchanged, `_plan_switch_history` gains `type=downgrade`, nothing shows as pending (that exists only under `apply_at_renewal`).
3. `_store_credit` = refund_amount. **If it reads 2 × refund_amount, file an issue**: `createDowngradeCredit()` writes `_store_credit`, then `arraysubs_downgrade_credit_created` → `addSubscriptionCredit()` adds it again to the same key.
4. Leg B: S-BASIC passes through `arraysubs-expired` and returns **`arraysubs-active`** on SLT Plan Basic, `_recurring_amount`=`5.00`, `_next_payment_date` = expiry + 1 day, note "Auto-downgraded from SLT Plan Pro to SLT Plan Basic due to subscription expiration."
5. Leg B detaches automatic billing: `_gateway_status=detached`, `_payment_gateway` and `_gateway_payment_method_id` deleted ⇒ the D9 renewal is manual.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `arraysubs_credit_added` (pro) | Leg A credit | slt-switch@example.test | `Store credit added` | `mailpit-agent list` |
| 2 | NONE for the downgrade | Leg A | — | — | no switch listener, no order, no order mail |
| 3 | `auto_downgrade` | Leg B | slt-switch@example.test | `has been changed to SLT Plan Basic` | `wait-new M1 300` |
| 4 | NONE: `subscription_expired` / `subscription_activated` | Leg B | — | — | suppressed by `isProcessing()`; presence FAILS |

## Evidence to capture
- `SLT-SW-02-01-preview.png`, `-02-after.png`, `-03-no-pending.png`, `-04-queue.png`, `-05-notes.png`; both ids; before/after meta; Mailpit ids.

## Pass criteria
- [ ] Downgrade applies immediately: no order, no payment, nothing pending
- [ ] `_next_payment_date` unchanged; history `downgrade`; `_store_credit` = refund_amount
- [ ] Expiry auto-downgrades to Basic and re-activates with a new next payment date
- [ ] `auto_downgrade` mail arrives; no expired/activated mail; no non-SLT action moved

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
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence before forcing.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run `wp action-scheduler run` without `--hooks=`; prefer a single action by ID.
- Evidence goes in `qa/subscription-lifecycle-test/evidence/<TASK-KEY>/`.
