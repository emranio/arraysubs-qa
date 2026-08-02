---
id: 99
title: 'Calendar overflow on the month flex product: the 31st day is absorbed into the last active segment'
status: todo
priority: high
created: 2026-08-02T03:43:11.288208461+02:00
updated: 2026-08-02T03:43:22.541978879+02:00
tags:
    - renewal-sync
    - day-06
    - has-conflicts
due: "2026-08-08"
estimate: 2h
depends_on:
    - 21
    - 22
    - 13
class: standard
---

> **SLT-SYN-10** · group `sync` · scheduled **D06** (2026-08-08)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · session collision (shared admin session)** — with `SLT-EML-01`, `SLT-EML-02`, `SLT-EML-03`, `SLT-EML-05`, `SLT-EML-10`, `SLT-EML-12`

- *Problem:* More than twenty tasks open `--session admin` by that bare name, in direct violation of audit C09's fix (which only got applied to guest/customer sessions). agent-browser sessions are keyed by name, so these tasks share one browser profile: one task's `agent-browser close --session admin` logs out another mid-run; one task navigating the SPA away from a settings screen invalidates another's snapshot; and any admin session that ever adds to cart (SLT-ADM-01's bulk-action screen, SLT-EML-12's status juggling) puts a cart on the admin user shared by all of them. The failures this produces look like flaky UI, not contamination.
- *Required fix:* Rename every admin session to `admin-<TASK-KEY>` (SLT-ADM-01/02/03/04/05 already do this correctly - copy the pattern). Each task closes only its own session by name; `agent-browser close --all` is reserved to the last task of the day, named explicitly in the calendar. Add this to the isolation contract as rule 9 and add a pass criterion to every task that opens an admin session: 'the session name contains this task's key'.

**`high` · dependency-gap / unowned purchases** — with `SLT-ADM-07`, `SLT-MYA-04`, `SLT-ADM-08`, `SLT-SW-01`, `SLT-SW-03`, `SLT-SW-02`

- *Problem:* Five purchases that multiple tasks treat as preconditions are owned by no task key in the index - they existed only as free-text 'purchases owned by other groups' rows in the superseded calendar. (a) S_FEE: slt-core's SLT Signup Fee Daily subscription, required by SLT-ADM-07 ('bought D3 by slt-core'), SLT-MYA-04 and SLT-ADM-08 (which refunds and cancels it). (b) S-BASIC and S-PRO: slt-switch's SLT Plan Basic and SLT Plan Pro subscriptions 'bought D4', required by SLT-SW-01, SW-03, SW-02 and SLT-EML-08. (c) SLT Flex Month Segments segment-3 by slt-flex3 on 2026-08-08, required by SLT-SYN-10 (SUB_S3, _next_payment_date 2026-09-30 18:00:00). (d) The D8 time-travel renewals for month segment-1/segment-2, week segment-3 (SLT-SYN-07's tail, due 2026-08-15) and the flex-variable tail - audit C17 mandates one dedicated D8 owner and none exists. (e) SLT-SYN-10 also references SUB_S2 which SLT-SYN-06 does buy, so only seg-3 is missing.
- *Required fix:* Assign explicit owners. Add step 0 to SLT-ADM-07: 'slt-core buys SLT Signup Fee Daily on D3 after 12:00 (order + subscription ids to the registry)'. Create SLT-SW-00 on D4: 'slt-switch buys SLT Plan Basic and SLT Plan Pro on Stripe after 12:00' as the ladder canvas for SW-01/02/03 and EML-08. Add step 0 to SLT-SYN-10: 'slt-flex3 buys SLT Flex Month Segments on 2026-08-08 (D6) after 12:00 - day-in-cycle 8, past both boundaries, resolves to segment 3, next payment 2026-10-01 00:00 site = 2026-09-30 18:00 UTC'. Create SLT-TT-00 on D8 as the single time-travel owner: pre-flight pending-queue screenshot + the 13 non-SLT _next_payment_date snapshot, then the month seg1/seg2 and week seg3 renewals and the flex-variable tail, single-action-by-id only, then the post-drain non-SLT diff proof - and have SYN-10, SW-02, EML-08, EML-10 and LIFE-01 quote its snapshot instead of each taking their own.

**`medium` · impossible-timing / single-day contention** — with `SLT-LIFE-01`, `SLT-SW-02`, `SLT-EML-08`, `SLT-EML-10`, `SLT-EML-14`, `SLT-DUN-05`

- *Problem:* D8 (2026-08-10) is the single authorized time-travel day and six tasks are stacked on it, each of which demands exclusive control of the pending Action Scheduler queue: SLT-SYN-10 (runs one month-renewal action by id and must prove no non-SLT date moved), SLT-SW-02 Leg B (hand-set _end_date + expire), SLT-EML-08 (expects an empty pending queue for its own _end_date write), SLT-EML-10 (queues an expiring-soon action in the past and runs it), SLT-LIFE-01 (back-dates S5's legs twice and leaves the queue empty for up to 3h waiting for the recovery sweep), SLT-EML-14 (read-only sweep whose whole value is that nothing moved). Each takes its own 'abort if a non-SLT action is due within 24h' pre-flight, and each would abort on the others' queued work. Run in any order but the right one, they invalidate each other's proofs.
- *Required fix:* Fix a strict D8 running order in the calendar and make it a precondition line in each body: (0) SLT-TT-00 pre-flight - one shared pending-queue screenshot plus the 13 non-SLT _next_payment_date snapshot, published to the registry and quoted by every other D8 task instead of re-taken; (1) SLT-TT-00 executes the month seg1/seg2 + week seg3 + flex-variable-tail renewals; (2) SLT-SYN-10 (month overflow, one action by id); (3) SLT-SW-02 (Leg A downgrade, then Leg B expiry auto-downgrade); (4) SLT-EML-08 (observes SW-02 Leg B; reactivates S_EML); (5) SLT-EML-10 (expiring-soon + card-expiring probes; cancels S_EML at teardown); (6) SLT-LIFE-01 (late-renewal phases A and B on S5 - last, because Phase B deliberately leaves S5 with zero legs and a past date for up to 3h); (7) SLT-EML-14 (read-only negative sweep, after everything). Close the day with the shared post-drain non-SLT diff.

---
## Objective
Prove flexible sync partitions a month cycle on a NOMINAL 30 days while the calendar month it lands in has 31, and that the surplus days are absorbed into the LAST ACTIVE segment — not segment 3, not an error, not `flex_day_unresolved`. Then prove it on live data by date-meta manipulation plus one calendar-month renewal across 31-day October.

## Scope
- Gateway: Stripe
- Checkout: N/A
- Account: existing (`slt-flex2`, `slt-flex3`)
- Plugins: pro-required

## Preconditions
- SLT-PROD-12: `SLT Flex Month Segments` `<MONTH_ID>`, month/1, $30.00, seg1_end=2, seg2_end=6, all active — the plan's only month product. SLT-PROD-14: `SLT Flex Daily Two Seg` `<TWO_ID>`. NO product meta written on either.
- Cohorts bought: `<SUB_S2>` segment-2 (slt-flex2); `<SUB_S3>` segment-3 (slt-flex3, `_next_payment_date 2026-09-30 18:00:00`).
- D8 = 2026-08-10 is the only authorised Action Scheduler day. This task owns ONE targeted action; the D8 task owns the rest.

## Test data
| Item | Value |
|---|---|
| Cycle under test | 2026-08-01 .. 2026-09-01 site = **31 real days**, nominal 30 |
| Boundaries UTC | cycle_start `2026-07-31 18:00:00`, due `2026-08-31 18:00:00` (00:00 site = 18:00 UTC) |

## Steps
1. `mailpit-agent latest-id` -> `M0`.
2. `SP` = `wp eval 'use ArraySubsPro\…\FlexibleRenewalSync\Services\SegmentPlan as S; …' --allow-root`. Dump `getConfig(<MONTH_ID>)`, `getPartition($c)`, `getNominalCycleDays("month",1)`, `resolveSegment($d,$c)`+`getSegmentMode()` for d=1,2,3,6,7,29,30,31,32.
3. `SP`: dump `getConfig(<TWO_ID>)` (a real 2-active shape), then replay the overflow in it — `$two=[cycle_days 30, actives [1,2], boundaries [15]]` d=14,15,16,30,31,32; `$one=[cycle_days 30, actives [3], boundaries []]` d=30,31,32.
4. `wp post meta list <SUB_S2> --allow-root`; record `_start_date` and the `_renewal_sync_*` set verbatim.
5. Date-meta manipulation against the shipping resolver — `SP`: `getDayInCycle($s,"2026-07-31 18:00:00")` for `$s` = that `_start_date`, then `2026-08-29/30/31` + `2026-09-01`, each `07:00:00` UTC.
6. `agent-browser --session admin` open Tools -> Scheduled Actions, `pending`, by schedule; screenshot. **ABORT** if a non-SLT action is due within 2 h. Dump `_next_payment_date` for active non-SLT subs -> `nonslt-before.txt`.
7. Record `<SUB_S3>._next_payment_date`, then `wp post meta update <SUB_S3> _next_payment_date "<now+8min UTC>" --allow-root`.
8. Click **Run** on the single `arraysubs_scheduled_subscription_payment` row whose args contain `<SUB_S3>`. Never a bare `--hooks=` drain.
9. Re-dump `<SUB_S3>` meta, open the renewal order, re-dump non-SLT -> `nonslt-after.txt`, diff vs step 6. Then `mailpit-agent wait-new M0 300 "Payment received"`.

## Expected results
1. `getConfig(<MONTH_ID>)`: `cycle_days` 30, `actives [1,2,3]`, `boundaries [2,6]`; partition `1-2 / 3-6 / 7-30`.
2. resolveSegment: 1,2 -> 1 `full`; 3,6 -> 2 `prorate`; 7,29,30 -> 3 `next_cycle`; **31 -> 3, 32 -> 3** by fall-through to `end(actives)` — never 0, never an error.
3. 2-active: 14,15 -> 1; 16,30 -> 2; **31 -> 2, 32 -> 2 `prorate`** — overflow follows the LAST ACTIVE segment, not segment 3. The 3-active case cannot distinguish this; this one is the proof. 1-active: 30,31,32 -> 3 `next_cycle`.
4. `getDayInCycle` returns 29, 30, **31**, 32, and the stored `_start_date` reproduces SLT-PROD-12's predicted day.
5. `<SUB_S3>` renewal order totals **$30.00**; `_next_payment_date` becomes **`2026-10-31 18:00:00`** UTC = 2026-11-01 00:00 site (`2026-10-30 18:00:00` would be a 30-day add across 31-day October: defect). It stays `arraysubs-active`, mode still `next_cycle`, and the step-9 diff is EMPTY.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | `payment_successful` + Woo renewal order mail | step 8 | slt-flex3@example.test | `Payment received for subscription #<SUB_S3>` | `wait-new M0 300`, then `list 20` |
| 2 | `renewal_invoice` NONE EXPECTED; none from steps 2-8 | — | — | — | no `Invoice for #<SUB_S3>`; `latest-id`==`M0` until step 8 |

## Evidence to capture
- `SLT-SYN-10-01-pending-before.png`, `-02-action-run.png`, `-03-renewal-order.png`, `-04-pending-after.png`.
- `wp eval` transcripts for steps 2, 3, 5 — the boundary proof. Plus `<SUB_S2>`/`<SUB_S3>` meta before+after, renewal order ID/total, Mailpit IDs, `nonslt` diff.

## Pass criteria
- [ ] Partition 1-2 / 3-6 / 7-30 on nominal 30 days; days 31, 32 -> last active segment (3-active)
- [ ] Days 31, 32 -> segment 2 `prorate` (2-active)
- [ ] `getDayInCycle` yields 31 for a 2026-08-31 start
- [ ] `<SUB_S3>` next payment = `2026-10-31 18:00:00` UTC; one action run; no non-SLT date moved

## Isolation / teardown
- Only `<SUB_S3>._next_payment_date` is edited; prior value recorded, deliberately not restored.
- Handoff: `<SUB_S3>` renews 2026-11-01, outside the window — D9..D12 watch must record NO further renewal for it and not score that a miss. `slt-flex`/`slt-flex2` left for the D8 task.

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
