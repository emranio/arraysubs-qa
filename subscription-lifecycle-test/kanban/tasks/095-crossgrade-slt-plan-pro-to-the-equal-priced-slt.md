---
id: 95
title: Crossgrade SLT Plan Pro to the equal-priced SLT Plan Peer and prove the date does not shift
status: todo
priority: high
created: 2026-08-02T03:43:11.018552355+02:00
updated: 2026-08-02T03:43:22.171551908+02:00
tags:
    - plan-switching
    - day-06
    - has-conflicts
due: "2026-08-08"
estimate: 1h
depends_on:
    - 60
    - 11
    - 12
class: standard
---

> **SLT-SW-03** · group `switching` · scheduled **D06** (2026-08-08)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · same-subscription collision / duplicate coverage** — with `SLT-EML-08`, `SLT-SW-02`, `SLT-SW-01`, `SLT-PROD-11`

- *Problem:* Both tasks run on D8 and both drive the on_expire auto-downgrade of a slt-switch plan-ladder subscription. SLT-EML-08 step 5 sets _end_date on 'S_PRO - slt-switch's active Pro subscription' and fires arraysubs_expire_subscription to capture the auto_downgrade email and the expired-suppression negative. SLT-SW-02 Leg B does exactly the same on 'S-BASIC (on Pro since SLT-SW-01)'. There are only two slt-switch ladder subscriptions and SLT-SW-03 (d6) already crossgraded the other one (S-PRO) off Pro onto SLT Plan Peer - at which point Pro's _arraysubs_auto_downgrade_product no longer applies to it and EML-08's leg is unrunnable as written. Whichever task expires the remaining Pro subscription first consumes the other's canvas.
- *Required fix:* Single owner: SLT-SW-02 Leg B owns the hand-set _end_date and the expiry of S-BASIC (which SLT-SW-01 left on SLT Plan Pro). SLT-EML-08 becomes observation-only for that leg - it reads the auto_downgrade mail ('has been changed to SLT Plan Basic'), proves the subscription_expired suppression negative (EmailManager.php:317-322) and confirms S-BASIC re-activated on Basic at $5.00 - and runs strictly after SW-02 in the D8 order. Delete EML-08 steps 4-5 (queue screenshot + _end_date write) and replace with 'quote SLT-SW-02's pre-flight queue screenshot and _end_date timestamp'. Update EML-08's Test data to name S-BASIC, not S_PRO.

**`high` · dependency-gap / unowned purchases** — with `SLT-ADM-07`, `SLT-MYA-04`, `SLT-ADM-08`, `SLT-SW-01`, `SLT-SW-02`, `SLT-EML-08`

- *Problem:* Five purchases that multiple tasks treat as preconditions are owned by no task key in the index - they existed only as free-text 'purchases owned by other groups' rows in the superseded calendar. (a) S_FEE: slt-core's SLT Signup Fee Daily subscription, required by SLT-ADM-07 ('bought D3 by slt-core'), SLT-MYA-04 and SLT-ADM-08 (which refunds and cancels it). (b) S-BASIC and S-PRO: slt-switch's SLT Plan Basic and SLT Plan Pro subscriptions 'bought D4', required by SLT-SW-01, SW-03, SW-02 and SLT-EML-08. (c) SLT Flex Month Segments segment-3 by slt-flex3 on 2026-08-08, required by SLT-SYN-10 (SUB_S3, _next_payment_date 2026-09-30 18:00:00). (d) The D8 time-travel renewals for month segment-1/segment-2, week segment-3 (SLT-SYN-07's tail, due 2026-08-15) and the flex-variable tail - audit C17 mandates one dedicated D8 owner and none exists. (e) SLT-SYN-10 also references SUB_S2 which SLT-SYN-06 does buy, so only seg-3 is missing.
- *Required fix:* Assign explicit owners. Add step 0 to SLT-ADM-07: 'slt-core buys SLT Signup Fee Daily on D3 after 12:00 (order + subscription ids to the registry)'. Create SLT-SW-00 on D4: 'slt-switch buys SLT Plan Basic and SLT Plan Pro on Stripe after 12:00' as the ladder canvas for SW-01/02/03 and EML-08. Add step 0 to SLT-SYN-10: 'slt-flex3 buys SLT Flex Month Segments on 2026-08-08 (D6) after 12:00 - day-in-cycle 8, past both boundaries, resolves to segment 3, next payment 2026-10-01 00:00 site = 2026-09-30 18:00 UTC'. Create SLT-TT-00 on D8 as the single time-travel owner: pre-flight pending-queue screenshot + the 13 non-SLT _next_payment_date snapshot, then the month seg1/seg2 and week seg3 renewals and the flex-variable tail, single-action-by-id only, then the post-drain non-SLT diff proof - and have SYN-10, SW-02, EML-08, EML-10 and LIFE-01 quote its snapshot instead of each taking their own.

---
## Objective
Crossgrade `slt-switch`'s **SLT Plan Pro** subscription (S-PRO, $15.00 day/1) to the equal-priced **SLT Plan Peer** ($15.00 day/1) and prove the money is a wash — classification `crossgrade` via the ±5 % daily-rate tolerance, credit exactly equal to charge, **Amount due $0.00**, no proration order, no store credit — and that the next payment date does not silently move.

## Scope
- Gateway: Stripe test (no charge occurs)
- Checkout: N/A
- Account: existing (`slt-switch`)
- Plugins: free-only

## Preconditions
- SLT-PROD-11 done: Pro's `_arraysubs_crossgrade_products` = [Peer], Peer's = [Pro]; both $15.00 day/1.
- `slt-switch` owns the **active** SLT Plan Pro subscription bought D4 (**S-PRO**) — not the one SLT-SW-01 upgraded.
- No Action Scheduler command may be run in this task (D8 is the only drain day).

## Test data
| Item | Value |
|---|---|
| Subscription | S-PRO (slt-switch / SLT Plan Pro) |
| Source → target | Pro $15.00 day/1 → Peer $15.00 day/1 |
| Portal | `/my-account/view-subscription/<S-PRO>/`, session `cust-SLT-SW-03` |

Classification and Branch-A maths (`cycle_days=1` both sides, r = 1 − days since `_last_payment_date`):
```
current_daily = new_daily = 15.00 ; tolerance = 0.75/day
=> |new-current| < tolerance -> crossgrade
credit = round(15.00*r,2) = charge = round(15.00*r,2) -> net = 0.00, refund = 0.00
```

## Steps
1. Record from S-PRO: `_product_id`, `_recurring_amount`, `_last_payment_date`, `_next_payment_date`, `_store_credit`, `_plan_switch_history`. From Tools → Scheduled Actions record BOTH pending legs for `[S-PRO]` (`arraysubs_create_renewal_invoice`, `arraysubs_process_renewal`): action id + scheduled GMT.
2. Compute `offset = crc32('arraysubs-spread-'.<S-PRO>)%21600` and confirm the recorded GMTs equal due+offset−6h and due+offset.
3. `mailpit-agent latest-id` → M0.
4. Log in as `slt-switch` / `SltQa!2026#Pass`; open the portal page; **Change Plan**.
5. The modal must expose a second tab **Others** — SLT Plan Peer is a crossgrade and is rendered there with the badge **Change**, not under **Upgrade/Downgrade**. Screenshot both tabs.
6. **Select** SLT Plan Peer; record the preview rows; **Amount due** must read `$0.00` and no "You will receive a credit" line may appear.
7. Confirm → **Change Plan**. Expect the success toast and a reload; no redirect to order-pay.
8. Re-dump the meta; re-read both pending legs for `[S-PRO]`; check the HPOS orders list for `slt-switch` — no order may have been created.
9. `mailpit-agent list 20` and compare against M0.

## Expected results
1. `switch_type` = `crossgrade` (equal daily rates fall inside the ±5 % band), option shown on the **Others** tab.
2. Preview: credit == charge to the cent, **Amount due $0.00**, no switch fee.
3. No proration order anywhere (`net_amount > 0` is false, so `createProrationOrder()` is never called) and no payment page.
4. `_product_id`=Peer, `_recurring_amount`=`15.00`, `_billing_period=day`, `_billing_interval=1`, title `SLT Plan Peer - Subscription #<S-PRO>`.
5. `_next_payment_date` is **byte-identical** to step 1 — Branch A returns the current date unchanged.
6. Both renewal legs are re-created: `RenewalScheduler::unschedule()`+`schedule()` runs because `new_next_payment_date` is non-empty, so each action **id changes** while its **scheduled GMT is identical** to step 1 (same due, same crc32 offset). A shifted GMT is a real bug — file it with both queue screenshots.
7. `_store_credit` unchanged (`refund_amount` is 0, not negative); `_plan_switch_history` gains one `type=crossgrade` entry; status stays `arraysubs-active`.
8. The next daily renewal still charges **$15.00** at the same time of day as before the crossgrade.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | whole task | — | — | no switch email listener, no order, no status change and no credit ⇒ `mailpit-agent latest-id` must equal M0 |

## Evidence to capture
- `SLT-SW-03-01-others-tab.png`, `-02-preview-zero.png`, `-03-portal-after.png`, `-04-queue-before.png`, `-05-queue-after.png`; S-PRO id; before/after meta; both action ids + GMTs; the unchanged Mailpit id.

## Pass criteria
- [ ] Classified crossgrade and offered on the **Others** tab with badge **Change**
- [ ] Credit == charge, Amount due $0.00, no order, no payment page
- [ ] Product/title/recurring amount updated to SLT Plan Peer at $15.00
- [ ] `_next_payment_date` byte-identical; both legs keep the same scheduled GMT (ids may change)
- [ ] `_store_credit` unchanged; one `crossgrade` history entry
- [ ] Zero mail (latest-id unchanged)

## Isolation / teardown
- Hands S-PRO to SLT-SW-02 sitting on **SLT Plan Peer** (Peer's downgrade list = [Basic]). Do not switch it again.
- Nothing global changed; no Action Scheduler command issued. `agent-browser close --session cust-SLT-SW-03`.

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
