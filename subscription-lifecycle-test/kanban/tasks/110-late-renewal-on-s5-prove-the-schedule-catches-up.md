---
id: 110
title: 'LATE renewal on S5: prove the schedule catches up to the cycle grid and that a past computed date queues zero legs'
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - renewal
    - day-08
due: "2026-08-31"
estimate: 3h
depends_on:
    - 19
    - 84
    - 108
class: standard
---

> **SLT-LIFE-01** · group `renewal` · scheduled **D08** (2026-08-31)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Run a renewal well after its due moment and settle whether the schedule CATCHES UP to the cycle grid or SLIPS from payment time. SLT-REF-07 Part B predicts catch-up: `isRenewalDue()` has no upper bound (RenewalProcessor.php:530-542) and the next date comes from the order's `_renewal_scheduled_date` anchor, not charge time (OrderIntegration.php:1629-1652). Its edge case: if the newly computed date is itself past, `scheduleSubscriptionRenewal()` returns at `$timestamp <= time()` (:1742-1744) and no legs are queued.

## Scope
- Gateway: Stripe test
- Checkout: N/A
- Account: existing (`slt2-core`)
- Plugins: free-only

## Preconditions
- SLT-LIFE-05 and SLT-LIFE-03 complete; S5 `arraysubs-active`, `_recurring_amount=20`, no pending skip or open renewal order.
- D8 (2026-08-31) is the only authorised date-meta time-travel day (audit C07/C17).
- The strict D8 order has reached this task after `SLT-EML-10`. Quote `SLT-TT-00`'s shared non-SLT2 schedule baseline and every preceding D8 task's empty diff.
- **No bare hook drains:** re-queue the legs into the past and let the per-minute runner claim them; if a manual run is unavoidable, run one action by ID.
- Pre-flight (C07): screenshot the site-wide pending queue. Future non-SLT2 rows are normal and must remain untouched; abort only if a non-SLT2 action is already overdue or will become due in the five-minute interval in which S5's past-due rows are exposed to cron. Clear only the documented SLT2 chain transients (L9) and any stale S5 lock (L10).

## Test data
| Item | Value |
|---|---|
| Subscription | S5 (SLT2 Renewal Price Step, `slt2-core`), $20.00/cycle |
| Phase A | `_next_payment_date` = now - 10h (under one cycle late) |
| Phase B | `_next_payment_date` = now - 30h (over one cycle late) |

## Steps
1. Resolve numeric `S5`. In `admin-SLT-LIFE-01-A`, perform the shared D8 pending/non-SLT2 preflight, dump S5 dates/payments, compute k, capture its exact action/order set, and set `PREV=$(mailpit-agent latest-id)` immediately before Phase A.
2. **Phase A:** apply the SLT-REF-10 L2 recipe (write the meta, `RenewalScheduler::unschedule($id)`, `schedule($id, strtotime($due." UTC"))`) with `$due = gmdate("Y-m-d H:i:s", time()-36000)`; screenshot the queue at once - both legs present, timestamps past.
3. Poll action/order state in checks no longer than 60 seconds through the five-minute cutoff; no drain. Resolve OA as the sole new order from S5 plus Phase-A backdated anchor/cycle and require reverse linkage. Re-dump/screenshot, then poll immutable PREV in ≤60-second calls through the five-minute cutoff for the exact payment-success message and classify the complete delta.
4. **Phase B:** record the post-A order set, set PREV2 immediately before the mutation, repeat with `time()-108000`, and poll action/order state in ≤60-second checks. Resolve OB as the sole new S5 order with the Phase-B anchor/cycle plus reverse link; poll immutable PREV2 in ≤60-second calls through the five-minute cutoff, then classify the complete delta.
5. Close `admin-SLT-LIFE-01-A` after Phase-B/no-legs/non-SLT2 proof. At +60 and +120 minutes use fresh read-only `admin-SLT-LIFE-01-RECOVERY` to capture whether exact global sweeps recover S5; close between reads. Continue bounded read-only checks through +3h without keeping a browser session open. If unrecovered at +3h, file the dedicated issue; never force a sweep.
6. Re-diff the **live shared non-SLT2 registry list** from SLT-TT-00 rather than the stale original count 13. Teardown in isolated `admin-SLT-LIFE-01-TEARDOWN`: set S5 next date to now+24h, unschedule/re-schedule, require exactly one healthy invoice/charge pair, close the session, independently review both phases/recovery/teardown, then move through `review` to `done` with Review empty. Any issue goes only in `qa/issues/` kanban card named `SLT-LIFE-01-<concise-slug>` with task/stage/plan path; subscription/order/action/message IDs; user ID/login/email/role; exact sessions/brackets/timestamps; reproduction; expected/actual; and meta/queue/log/order/Mailpit/non-SLT-diff proof.

## Expected results
1. Phase A charges $20.00 within ~2 min of the re-queue - lateness never bars the charge; OA's anchor `_renewal_scheduled_date` = the back-dated due D_A (now-10h), not the charge moment.
2. After Phase A `_next_payment_date` = D_A + 24h = now + 14h. **The catch-up assertion:** a slip implementation would give charge_time + 24h. Legs re-queue at (D_A+24h)+k-6h and +k, k unchanged.
3. Phase B charges $20.00 again; OB's anchor = D_B (now-30h); the computed `_next_payment_date` = now - 6h, still past - so ZERO `arraysubs_process_renewal` and ZERO `arraysubs_generate_renewal_invoice` rows exist for S5 right after Phase B.
4. `_completed_payments` +1 per phase; S5 stays `arraysubs-active`; no other subscription moved.
5. Record recovery time; if nothing recovers S5 within 3h, write a QA issue card under `qa/issues/` citing :1742-1744 and L24 (sweeps batch 50 rows from the lowest post ID across 354 subs).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | payment_successful x2 + WC order mails | Phase A and B charges | customer + admin | `Payment received for subscription #S5`, `#OA`/`#OB` | exact waits after `PREV` and `PREV2`; classify each complete delta by subscription/order id |
| 2 | NONE EXPECTED: payment_failed, on_hold, cancelled (lateness must not start dunning), renewal_invoice, renewal_reminder | — | — | `Payment failed`, `is on hold`, `been cancelled`, `Invoice for subscription`, `renews soon` | absent from both complete phase deltas |

## Evidence to capture
- Screenshots `SLT-LIFE-01-01-preflight.png`, `-02-phaseA-queued.png`, `-03-phaseA-after.png`, `-04-phaseB-no-legs.png`, `-05-recovery-60.png`, `-06-recovery-120.png`.
- S5, OA, OB, k, meta dumps, the `wp eval` commands, AS rows, the live registry-derived non-SLT2 date set, Mailpit IDs.

## Pass criteria
- [ ] Both late charges succeed at $20.00; each order anchored to its back-dated due date
- [ ] Phase A next date = D_A + 1 cycle (catch-up), not charge time + 1 cycle
- [ ] Phase B leaves the computed date past and queues zero legs
- [ ] Recovery observed and timed, or an issue filed after 3h
- [ ] No dunning email, no status change, no non-SLT2 action or date moved
- [ ] Exact phase orders/sessions and healthy teardown reviewed to `done` with Review empty

## Isolation / teardown
- S5 is left with `_next_payment_date` = now + 24h and one healthy pair of legs; SLT-SETUP-99A cancels it on D11, and SLT-SETUP-99B deletes it after the D12 watch closes.
- No settings changed. Record which chain transients the pre-flight cleared.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
