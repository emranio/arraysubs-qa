---
id: 110
title: 'LATE renewal on S5: prove the schedule catches up to the cycle grid and that a past computed date queues zero legs'
status: todo
priority: high
created: 2026-08-02T03:43:12.149198837+02:00
updated: 2026-08-02T03:43:23.561895426+02:00
tags:
    - renewal
    - day-08
    - has-conflicts
due: "2026-08-10"
estimate: 3h
depends_on:
    - 19
    - 84
class: standard
---

> **SLT-LIFE-01** · group `renewal` · scheduled **D08** (2026-08-10)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`medium` · action-scheduler policy / broad-fire risk** — with `SLT-LIFE-04`, `SLT-EML-01`, `SLT-EML-10`, `SLT-ADM-05`, `SLT-SETUP-99`

- *Problem:* No task in the index issues a bare `wp action-scheduler run --hooks=<hook> --force`, so the largest hazard the audit named is currently absent - but the 'D8 is the only authorized Action Scheduler day' rule is broken by tasks that legitimately need to run one action: SLT-LIFE-04 step 9 hand-schedules HOOK_SEND_EXPIRING_SOON and runs it by id on D3 (2026-08-05) - which is also SLT-SYN-04's exclusive bracket day; SLT-EML-01 step 8 queues a duplicate reminder action on D3 and lets wp-cron claim it; SLT-ADM-05/ADM-03 depend on cron claiming their legs on D3/D4. Residual broad-fire risks that DO exist: (a) SLT-LIFE-01 back-dates S5's legs and relies on the per-minute runner, whose batch will claim any other action already due in that same tick; (b) SLT-EML-10 schedules HOOK_SEND_EXPIRING_SOON at time()-60; (c) SLT-SETUP-99's step 7 cancels pending actions found by searching the Scheduled Actions screen, which can match non-SLT rows; (d) SLT-ADM-01's bulk 'Delete Permanently' path issues DELETE wp/v2/arraysubs_data/<id>?force=true per selected id with no onDeleteCheck guard - one accidental confirm force-deletes irrecoverably.
- *Required fix:* Refine the rule into three tiers and publish it in the README isolation contract. (1) BANNED on every day, no exceptions: any `wp action-scheduler run` without a specific action id, and any `--hooks=` drain. (2) PERMITTED on any day: running ONE action by id from Tools -> Scheduled Actions, and queueing a single-subscription action and letting the per-minute cron claim it - provided the task first screenshots the Pending queue for the next 60 minutes and aborts if any non-SLT action is due. (3) D8 ONLY: editing _next_payment_date / _end_date / _renewal_scheduled_date to move an event in time, always paired with the 13 non-SLT _next_payment_date before/after proof. Under this rule LIFE-04 step 9, EML-01 step 8, EML-10 and ADM-05/03 are legal where they are; LIFE-01 and SETUP-99 stay on D8/D10 with the pre-flight. For SETUP-99, replace 'search and cancel' with 'cancel by action id, taken from the per-subscription action-id metas recorded in the registry'. For SLT-ADM-01, keep the bulk dialog cancelled and file the missing-guard finding as a bug, as authored.

**`medium` · impossible-timing / single-day contention** — with `SLT-SW-02`, `SLT-SYN-10`, `SLT-EML-08`, `SLT-EML-10`, `SLT-EML-14`, `SLT-DUN-05`

- *Problem:* D8 (2026-08-10) is the single authorized time-travel day and six tasks are stacked on it, each of which demands exclusive control of the pending Action Scheduler queue: SLT-SYN-10 (runs one month-renewal action by id and must prove no non-SLT date moved), SLT-SW-02 Leg B (hand-set _end_date + expire), SLT-EML-08 (expects an empty pending queue for its own _end_date write), SLT-EML-10 (queues an expiring-soon action in the past and runs it), SLT-LIFE-01 (back-dates S5's legs twice and leaves the queue empty for up to 3h waiting for the recovery sweep), SLT-EML-14 (read-only sweep whose whole value is that nothing moved). Each takes its own 'abort if a non-SLT action is due within 24h' pre-flight, and each would abort on the others' queued work. Run in any order but the right one, they invalidate each other's proofs.
- *Required fix:* Fix a strict D8 running order in the calendar and make it a precondition line in each body: (0) SLT-TT-00 pre-flight - one shared pending-queue screenshot plus the 13 non-SLT _next_payment_date snapshot, published to the registry and quoted by every other D8 task instead of re-taken; (1) SLT-TT-00 executes the month seg1/seg2 + week seg3 + flex-variable-tail renewals; (2) SLT-SYN-10 (month overflow, one action by id); (3) SLT-SW-02 (Leg A downgrade, then Leg B expiry auto-downgrade); (4) SLT-EML-08 (observes SW-02 Leg B; reactivates S_EML); (5) SLT-EML-10 (expiring-soon + card-expiring probes; cancels S_EML at teardown); (6) SLT-LIFE-01 (late-renewal phases A and B on S5 - last, because Phase B deliberately leaves S5 with zero legs and a past date for up to 3h); (7) SLT-EML-14 (read-only negative sweep, after everything). Close the day with the shared post-drain non-SLT diff.

---
## Objective
Run a renewal well after its due moment and settle whether the schedule CATCHES UP to the cycle grid or SLIPS from payment time. SLT-REF-07 Part B predicts catch-up: `isRenewalDue()` has no upper bound (RenewalProcessor.php:530-542) and the next date comes from the order's `_renewal_scheduled_date` anchor, not charge time (OrderIntegration.php:1629-1652). Its edge case: if the newly computed date is itself past, `scheduleSubscriptionRenewal()` returns at `$timestamp <= time()` (:1742-1744) and no legs are queued.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (`slt-core`)
- Plugins: free-only

## Preconditions
- SLT-LIFE-05 and SLT-LIFE-03 complete; S5 `arraysubs-active`, `_recurring_amount=20`, no pending skip or open renewal order.
- D8 (2026-08-09) is the only authorised time-travel day (audit C07/C17).
- **No bare hook drains:** re-queue the legs into the past and let the per-minute runner claim them; if a manual run is unavoidable, run one action by ID.
- Pre-flight (C07): screenshot the site-wide pending queue, ABORT if any non-SLT action is due in 24h; clear the chain transients (L9) and any stale S5 lock (L10).

## Test data
| Item | Value |
|---|---|
| Subscription | S5 (SLT Renewal Price Step, `slt-core`), $20.00/cycle |
| Phase A | `_next_payment_date` = now - 10h (under one cycle late) |
| Phase B | `_next_payment_date` = now - 30h (over one cycle late) |

## Steps
1. Baseline: dump `_next_payment_date,_completed_payments,_last_payment_date` for S5; compute k; screenshot S5's pending queue. `PREV=$(mailpit-agent latest-id)`.
2. **Phase A:** apply the SLT-REF-10 L2 recipe (write the meta, `RenewalScheduler::unschedule($id)`, `schedule($id, strtotime($due." UTC"))`) with `$due = gmdate("Y-m-d H:i:s", time()-36000)`; screenshot the queue at once - both legs present, timestamps past.
3. Poll up to 5 min (`wp action-scheduler list --hooks=arraysubs_process_renewal --status=complete`) and the order list; no drain. Read order OA (total, status, anchor, cycle number); re-dump metas; re-screenshot the queue; `wait-new "$PREV" 300 "Payment received"`.
4. **Phase B:** `PREV2=$(mailpit-agent latest-id)`; repeat with `time() - 108000`; poll; read OB; re-dump metas; screenshot the queue.
5. Re-screenshot the queue at +60 and +120 min: do the `generate_upcoming_renewals` / `check_overdue_renewals` sweeps recover it (Hooks.php:397,411 and 673-678)? Record the time.
6. Verify the 13 non-SLT active subscriptions still hold their original `_next_payment_date` (C07). Teardown: set `_next_payment_date` = now + 24h, unschedule, re-schedule, one leg each.

## Expected results
1. Phase A charges $20.00 within ~2 min of the re-queue - lateness never bars the charge; OA's anchor `_renewal_scheduled_date` = the back-dated due D_A (now-10h), not the charge moment.
2. After Phase A `_next_payment_date` = D_A + 24h = now + 14h. **The catch-up assertion:** a slip implementation would give charge_time + 24h. Legs re-queue at (D_A+24h)+k-6h and +k, k unchanged.
3. Phase B charges $20.00 again; OB's anchor = D_B (now-30h); the computed `_next_payment_date` = now - 6h, still past - so ZERO `arraysubs_process_renewal` and ZERO `arraysubs_generate_renewal_invoice` rows exist for S5 right after Phase B.
4. `_completed_payments` +1 per phase; S5 stays `arraysubs-active`; no other subscription moved.
5. Record recovery time; if nothing recovers S5 within 3h file an issue citing :1742-1744 and L24 (sweeps batch 50 rows from the lowest post ID across 354 subs).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | payment_successful x2 + WC order mails | Phase A and B charges | customer + admin | `Payment received for subscription #S5`, `#OA`/`#OB` | `wait-new $PREV 300`, `wait-new $PREV2 300`, `list 20` |
| 2 | NONE EXPECTED: payment_failed, on_hold, cancelled (lateness must not start dunning), renewal_invoice, renewal_reminder | — | — | `Payment failed`, `is on hold`, `been cancelled`, `Invoice for subscription`, `renews soon` | absent in `list 30` |

## Evidence to capture
- Screenshots `SLT-LIFE-01-01-preflight.png`, `-02-phaseA-queued.png`, `-03-phaseA-after.png`, `-04-phaseB-no-legs.png`, `-05-recovery-60.png`, `-06-recovery-120.png`.
- S5, OA, OB, k, meta dumps, the `wp eval` commands, AS rows, the 13 non-SLT dates, Mailpit IDs.

## Pass criteria
- [ ] Both late charges succeed at $20.00; each order anchored to its back-dated due date
- [ ] Phase A next date = D_A + 1 cycle (catch-up), not charge time + 1 cycle
- [ ] Phase B leaves the computed date past and queues zero legs
- [ ] Recovery observed and timed, or an issue filed after 3h
- [ ] No dunning email, no status change, no non-SLT action or date moved

## Isolation / teardown
- S5 left with `_next_payment_date` = now + 24h and one healthy pair of legs; SLT-SETUP-99A deletes it D10.
- No settings changed. Record which chain transients the pre-flight cleared.


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
