---
id: 110
title: 'LATE renewal on S5: prove the schedule catches up to the cycle grid and that a past computed date queues zero legs'
status: done
priority: high
created: 2026-08-02T03:43:12.149198837+02:00
updated: 2026-08-10T02:55:23.903097603+02:00
started: 2026-08-10T02:55:23.696371524+02:00
completed: 2026-08-10T02:55:23.696371524+02:00
tags:
    - renewal
    - day-08
due: "2026-08-10"
estimate: 3h
depends_on:
    - 19
    - 84
    - 108
class: standard
---

> **SLT-LIFE-01** · group `renewal` · scheduled **D08** (2026-08-10)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Run a renewal well after its due moment and settle whether the schedule CATCHES UP to the cycle grid or SLIPS from payment time. SLT-REF-07 Part B predicts catch-up: `isRenewalDue()` has no upper bound (RenewalProcessor.php:530-542) and the next date comes from the order's `_renewal_scheduled_date` anchor, not charge time (OrderIntegration.php:1629-1652). Its edge case: if the newly computed date is itself past, `scheduleSubscriptionRenewal()` returns at `$timestamp <= time()` (:1742-1744) and no legs are queued.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (`slt-core`)
- Plugins: free-only

## Preconditions
- SLT-LIFE-05 and SLT-LIFE-03 complete; S5 `arraysubs-active`, `_recurring_amount=20`, no pending skip or open renewal order.
- D8 (2026-08-10) is the only authorised date-meta time-travel day (audit C07/C17).
- The strict D8 order has reached this task after `SLT-EML-10`. Quote `SLT-TT-00`'s shared non-SLT schedule baseline and every preceding D8 task's empty diff.
- **No bare hook drains:** re-queue the legs into the past and let the per-minute runner claim them; if a manual run is unavoidable, run one action by ID.
- Pre-flight (C07): screenshot the site-wide pending queue. Future non-SLT rows are normal and must remain untouched; abort only if a non-SLT action is already overdue or will become due in the five-minute interval in which S5's past-due rows are exposed to cron. Clear only the documented SLT chain transients (L9) and any stale S5 lock (L10).

## Test data
| Item | Value |
|---|---|
| Subscription | S5 (SLT Renewal Price Step, `slt-core`), $20.00/cycle |
| Phase A | `_next_payment_date` = now - 10h (under one cycle late) |
| Phase B | `_next_payment_date` = now - 30h (over one cycle late) |

## Steps
1. Resolve numeric `S5`. In `admin-SLT-LIFE-01-A`, perform the shared D8 pending/non-SLT preflight, dump S5 dates/payments, compute k, capture its exact action/order set, and set `PREV=$(mailpit-agent latest-id)` immediately before Phase A.
2. **Phase A:** apply the SLT-REF-10 L2 recipe (write the meta, `RenewalScheduler::unschedule($id)`, `schedule($id, strtotime($due." UTC"))`) with `$due = gmdate("Y-m-d H:i:s", time()-36000)`; screenshot the queue at once - both legs present, timestamps past.
3. Poll action/order state in checks no longer than 60 seconds through the five-minute cutoff; no drain. Resolve OA as the sole new order from S5 plus Phase-A backdated anchor/cycle and require reverse linkage. Re-dump/screenshot, then poll immutable PREV in ≤60-second calls through the five-minute cutoff for the exact payment-success message and classify the complete delta.
4. **Phase B:** record the post-A order set, set PREV2 immediately before the mutation, repeat with `time()-108000`, and poll action/order state in ≤60-second checks. Resolve OB as the sole new S5 order with the Phase-B anchor/cycle plus reverse link; poll immutable PREV2 in ≤60-second calls through the five-minute cutoff, then classify the complete delta.
5. Close `admin-SLT-LIFE-01-A` after Phase-B/no-legs/non-SLT proof. At +60 and +120 minutes use fresh read-only `admin-SLT-LIFE-01-RECOVERY` to capture whether exact global sweeps recover S5; close between reads. Continue bounded read-only checks through +3h without keeping a browser session open. If unrecovered at +3h, file the standalone issue; never force a sweep.
6. Re-diff the **live shared non-SLT registry list** from SLT-TT-00 rather than the stale original count 13. Teardown in isolated `admin-SLT-LIFE-01-TEARDOWN`: set S5 next date to now+24h, unschedule/re-schedule, require exactly one healthy invoice/charge pair, close the session, independently review both phases/recovery/teardown, then move through `review` to `done` with Review empty. Any issue goes only in `issues/SLT-LIFE-01-<concise-slug>.md` with task/stage/plan path; subscription/order/action/message IDs; user ID/login/email/role; exact sessions/brackets/timestamps; reproduction; expected/actual; and meta/queue/log/order/Mailpit/non-SLT-diff proof.

## Expected results
1. Phase A charges $20.00 within ~2 min of the re-queue - lateness never bars the charge; OA's anchor `_renewal_scheduled_date` = the back-dated due D_A (now-10h), not the charge moment.
2. After Phase A `_next_payment_date` = D_A + 24h = now + 14h. **The catch-up assertion:** a slip implementation would give charge_time + 24h. Legs re-queue at (D_A+24h)+k-6h and +k, k unchanged.
3. Phase B charges $20.00 again; OB's anchor = D_B (now-30h); the computed `_next_payment_date` = now - 6h, still past - so ZERO `arraysubs_process_renewal` and ZERO `arraysubs_generate_renewal_invoice` rows exist for S5 right after Phase B.
4. `_completed_payments` +1 per phase; S5 stays `arraysubs-active`; no other subscription moved.
5. Record recovery time; if nothing recovers S5 within 3h, write a standalone issue file under `issues/` citing :1742-1744 and L24 (sweeps batch 50 rows from the lowest post ID across 354 subs).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | payment_successful x2 + WC order mails | Phase A and B charges | customer + admin | `Payment received for subscription #S5`, `#OA`/`#OB` | exact waits after `PREV` and `PREV2`; classify each complete delta by subscription/order id |
| 2 | NONE EXPECTED: payment_failed, on_hold, cancelled (lateness must not start dunning), renewal_invoice, renewal_reminder | — | — | `Payment failed`, `is on hold`, `been cancelled`, `Invoice for subscription`, `renews soon` | absent from both complete phase deltas |

## Evidence to capture
- Screenshots `SLT-LIFE-01-01-preflight.png`, `-02-phaseA-queued.png`, `-03-phaseA-after.png`, `-04-phaseB-no-legs.png`, `-05-recovery-60.png`, `-06-recovery-120.png`.
- S5, OA, OB, k, meta dumps, the `wp eval` commands, AS rows, the live registry-derived non-SLT date set, Mailpit IDs.

## Pass criteria
- [ ] Both late charges succeed at $20.00; each order anchored to its back-dated due date
- [ ] Phase A next date = D_A + 1 cycle (catch-up), not charge time + 1 cycle
- [ ] Phase B leaves the computed date past and queues zero legs
- [ ] Recovery observed and timed, or an issue filed after 3h
- [ ] No dunning email, no status change, no non-SLT action or date moved
- [ ] Exact phase orders/sessions and healthy teardown reviewed to `done` with Review empty

## Isolation / teardown
- S5 is left with `_next_payment_date` = now + 24h and one healthy pair of legs; SLT-SETUP-99A cancels it on D10, and SLT-SETUP-99B deletes it after the D12 watch closes.
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
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence and do not force a natural-watch action.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run a bare or `--hooks=` Action Scheduler drain. Run one known action ID at a time only when the task explicitly authorizes it and after the required queue pre-flight; natural-watch actions are never forced.
- Evidence goes under `/home/server-manager/slt-evidence/` using task-key-prefixed filenames.

[[2026-08-06]] Thu 22:03
Carry-forward/source-block note on 2026-08-06: the S5 late-renewal experiment itself is still a real future D8 task, but its authored D8 order requires card 108 / SLT-EML-10 first. Card 108 is currently source-blocked behind cards 107 and 111 because the ladder-switch fixtures from card 72 never existed, so do not start this card on Monday, August 10, 2026 unless that upstream chain has been recreated on a later valid execution and the earlier D8 tasks have actually completed.

[[2026-08-10]] Mon 06:55
D08 execution closes `UNVERIFIED` at the card's explicit upstream-chain guard. Card 108 completed its execution record without running its active-source flow because cards 107/111 could not recreate the missing ladder-switch chain; fresh exact state confirms `S_EML=12263` remains `arraysubs-cancelled` with no next-payment date. Consequently the D8 late-renewal date-meta experiment was not authorised: no Phase A/Phase B write, queue exposure, Mailpit baseline, browser session, recovery poll, or teardown mutation occurred. Read-only S5 proof confirms `S5=12234` remains active for customer 347/product 12087 at `$20.00`, payments 6, next `2026-08-10 07:50:13Z`, no pending-renewal-order or active skip meta, and exactly its five completed relationship orders. Its natural D08 invoice/charge pair 16429/16430 remains pending and unattempted for 11:43:36/17:43:36 site, untouched for later watch phases. This is missing prerequisite test data rather than observed product behavior, so no issue was filed. Full proof: `/home/server-manager/slt-evidence/SLT-LIFE-01-D08-source-block.txt`.
