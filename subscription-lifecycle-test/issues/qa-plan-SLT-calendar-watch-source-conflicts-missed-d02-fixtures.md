# SLT calendar/watch source conflicts from missed D02 fixtures

Status: open planning blocker

## Task / stage / plan

- QA progress task: planning/source-consistency issue derived from the missed D02 source-fixture chain and its future watch consumers
- Stage: future-watch planning audit on Thursday, August 6, 2026
- Plan paths:
  - `qa/subscription-lifecycle-test/kanban/tasks/030-classic-checkout-with-stripe-sca-card-3ds-at.md`
  - `qa/subscription-lifecycle-test/kanban/tasks/031-complete-both-0-00-today-trial-checkouts-card.md`
  - `qa/subscription-lifecycle-test/kanban/tasks/034-trial-started-trial-ending-at-days-before-3-and.md`
  - `qa/subscription-lifecycle-test/kanban/tasks/035-utc-6-midnight-boundary-renewal-date-correctness.md`
  - `qa/subscription-lifecycle-test/kanban/tasks/043-stripe-sca-at-renewal-4000-0027-6000-3184.md`
  - `qa/subscription-lifecycle-test/kanban/tasks/045-segment-2-prorate-prove-the-arithmetic-to-the-cent.md`
  - `qa/subscription-lifecycle-test/kanban/tasks/046-variation-level-flexible-sync-prove-the-purchased.md`
  - `qa/subscription-lifecycle-test/watch-schedule.md`
  - `qa/subscription-lifecycle-test/calendar.md`

## QA progress task ID and stage

- Primary source tasks:
  - `#30 / SLT-CHK-05 / checkout / day-02`
  - `#31 / SLT-CHK-15 / checkout / day-02`
  - `#34 / SLT-EML-09 / email / day-02`
  - `#35 / SLT-IMP-01 / edge-cases / day-02`
  - `#43 / SLT-REN-05 / renewal / day-03`
  - `#45 / SLT-SYN-06 / renewal-sync / day-02`
  - `#46 / SLT-SYN-13 / renewal-sync / day-02`
- Relevant future watch/schedule consumers:
  - `watch-schedule.md` rows D5, D6, D8, D9, D10, D11
  - `calendar.md` rows D8, D10 and post-D10 teardown note
  - `/home/server-manager/slt-evidence/D12-watch-runbook-2026-08-14.txt`
- Relevant QA plan paths:
  - `qa/subscription-lifecycle-test/kanban/tasks/030-classic-checkout-with-stripe-sca-card-3ds-at.md`
  - `qa/subscription-lifecycle-test/kanban/tasks/031-complete-both-0-00-today-trial-checkouts-card.md`
  - `qa/subscription-lifecycle-test/kanban/tasks/034-trial-started-trial-ending-at-days-before-3-and.md`
  - `qa/subscription-lifecycle-test/kanban/tasks/035-utc-6-midnight-boundary-renewal-date-correctness.md`
  - `qa/subscription-lifecycle-test/kanban/tasks/043-stripe-sca-at-renewal-4000-0027-6000-3184.md`
  - `qa/subscription-lifecycle-test/kanban/tasks/045-segment-2-prorate-prove-the-arithmetic-to-the-cent.md`
  - `qa/subscription-lifecycle-test/kanban/tasks/046-variation-level-flexible-sync-prove-the-purchased.md`
  - `qa/subscription-lifecycle-test/watch-schedule.md`
  - `qa/subscription-lifecycle-test/calendar.md`

## Affected IDs

- `S_TR` trial source alias — `N/A` live numeric ID because task 34 says no source subscription exists
- `slt-sca` / `SLT Daily Core` SCA source alias — `N/A` live numeric ID because tasks 30 and 43 say no source subscription exists
- `S_TZ` / `slt-tz` midnight-boundary source alias — `N/A` live numeric ID because task 35 says no source subscription exists
- `SLT-SYN-13` Full / Next Cycle variation aliases — `N/A` live numeric IDs because task 46 says the D02 variation purchases never existed
- `SLT-SYN-06` flex segment source aliases — `N/A` live numeric IDs because task 45 says the D02 purchases never existed
- Order IDs: `N/A`

## Affected user / customer context

- `slt-trial` / `slt-trial@example.test` / customer
- `slt-sca` / `slt-sca@example.test` / customer
- `slt-tz` / `N/A` / customer
- `slt-flex` / `slt-flex3` / customer

## Exact routes / browser context

- `N/A` — planning/source-consistency issue found by comparing current task markdown with current future schedule expectations

Reproduction steps
1. Read the appended 2026-08-05 closeout notes on tasks 30, 31, 34, 35, 43, 45, and 46.
2. Observe that each closes `UNVERIFIED` because its D02 execution window was missed or the required source subscription does not exist.
3. Read `watch-schedule.md` rows D5, D6, D8, D9, D10, and D11 plus `calendar.md` rows D8, D10 and the post-D10 teardown note.
4. Observe that these future docs still expect:
   - `slt-sca` renewal / requires-action follow-up,
   - trial-mail / trial-subscription consequences from `S_TR`,
   - `S_TZ` midnight-boundary renewal evidence,
   - `SLT-SYN-13` variation renewals on D5, D8, D11 and teardown ownership after D10.
5. Read `/home/server-manager/slt-evidence/D12-watch-runbook-2026-08-14.txt`.
6. Observe that it still asks for zero post-cancellation mail for `S_FAIL` even though that ladder never existed.

Expected result
- Future watch and calendar docs should treat these authored D02 fixtures as absent unless the live registry contradicts the appended task outcomes.
- Teardown planning should not require nonexistent `SLT-SYN-13` fixtures, and final-watch docs should not assert silence for ladders that never existed.

Actual result
- Several future watch/calendar rows still treat missed D02 fixtures as if they will later renew or generate proof points.
- The D12 runbook still asks for post-cancellation silence on a ladder that current task evidence says never existed.

Concrete proof
- Task 30 appended note dated `2026-08-05`:
  - `UNVERIFIED (missed D02 execution window)`
  - no `slt-sca` user or source subscription exists
- Task 31 appended note dated `2026-08-05`:
  - `UNVERIFIED (missed D02 execution window)`
  - `slt-trial` user exists but owns no ArraySubs subscription rows
- Task 34 appended note dated `2026-08-05`:
  - `UNVERIFIED (no source S_TR)`
- Task 35 appended note dated `2026-08-05`:
  - `UNVERIFIED (missed D02 timed checkout window)`
  - no `slt-tz` user or subscription exists
- Task 43 appended note dated `2026-08-05`:
  - `UNVERIFIED (no source subscription)`
- Task 45 appended note dated `2026-08-05`:
  - `UNVERIFIED (missed D02 purchase window)`
- Task 46 appended note dated `2026-08-05`:
  - `UNVERIFIED (missed D02 purchase window)`
  - no `SLT-SYN-13` variation subscriptions exist
- `watch-schedule.md`
  - D5 still expects `SLT-SYN-13` Full renewal and `slt-sca`
  - D6 still expects `slt-sca`, `S_TZ`, and `SLT-SYN-13` Next Cycle
  - D8 and D11 still expect `SLT-SYN-13` renewals
  - D10 still treats `SLT-SYN-13` as a guaranteed surviving tail fixture
- `calendar.md`
  - D8 still expects the variable-flex natural events unconditionally
  - post-D10 teardown note still includes two `SLT-SYN-13` variation subscriptions as guaranteed tail members
- `D12-watch-runbook-2026-08-14.txt`
  - still lists `zero new lifecycle mail for S_FAIL after cancellation`

## Known scope / counterexamples
- This issue is about authoritative QA-plan drift, not plugin runtime behavior.
- Separate planning issues already cover the dunning ladder specifically, `SLT-MYA-05` follow-ups, and the explicit D13 tail conflict around `SUB_2SEG` and `SLT-SYN-13`.
- This issue is narrower: future docs still reference other missed-D02 fixtures as if they remain live authored evidence.
