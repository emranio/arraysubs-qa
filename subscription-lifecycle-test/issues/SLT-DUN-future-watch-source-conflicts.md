# SLT dunning future-watch source conflicts

Status: open planning blocker

## Task / stage / plan

- QA progress task: planning/source-consistency issue derived from the future-affected dunning tasks below
- Stage: future-watch planning audit on Thursday, August 6, 2026
- Plan paths:
  - `qa/subscription-lifecycle-test/kanban/tasks/082-payment-failed-one-customer-one-admin-email-per.md`
  - `qa/subscription-lifecycle-test/kanban/tasks/101-grace-phase-2-terminal-cancellation-three-days.md`
  - `qa/subscription-lifecycle-test/kanban/tasks/102-mid-grace-recovery-new-card-in-my-account-pay-the.md`
  - `qa/subscription-lifecycle-test/watch-schedule.md`

## QA progress task ID and stage

- Primary future-affected tasks:
  - `#82 / SLT-EML-04 / email / day-05`
  - `#101 / SLT-DUN-04 / renewal / day-07`
  - `#102 / SLT-DUN-05 / renewal / day-07`
- Relevant future watch/teardown consumers:
  - `watch-schedule.md` rows D8, D9, D10, D11
  - `#118 / SLT-SETUP-99A`
- Relevant QA plan paths:
  - `qa/subscription-lifecycle-test/kanban/tasks/082-payment-failed-one-customer-one-admin-email-per.md`
  - `qa/subscription-lifecycle-test/kanban/tasks/101-grace-phase-2-terminal-cancellation-three-days.md`
  - `qa/subscription-lifecycle-test/kanban/tasks/102-mid-grace-recovery-new-card-in-my-account-pay-the.md`
  - `qa/subscription-lifecycle-test/watch-schedule.md`

## Affected IDs

- `S_FAIL` — canonical original dunning ladder alias; numeric ID unresolved because the task outcome says no source fixture exists
- `SUB_FAIL_RECOVERY` — authored second-ladder alias; numeric ID unresolved because task 102 says the scenario cannot start honestly
- Order IDs: `N/A`

## Affected user / customer context

- user `351`, login/email `slt-fail` / `slt-fail@example.test`, role customer

## Exact routes / browser context

- `N/A` — planning/source-consistency issue found by comparing current task markdown with current watch expectations

Reproduction steps
1. Read task 82's appended 2026-08-05 closeout note.
2. Observe that task 82 closes `UNVERIFIED (no S_FAIL source fixture)`.
3. Read task 102's appended 2026-08-05 closeout note.
4. Observe that task 102 says the downstream `SUB_FAIL_RECOVERY` recovery scenario cannot start honestly because the original `S_FAIL` ladder never existed.
5. Read the future watch rows for D8, D9, D10, and D11 in `watch-schedule.md`.
6. Observe that those rows still expect:
   - D8 `SLT Retry Daily` cancellation state and mails,
   - D9 `SUB_FAIL_RECOVERY` failure and same-day recovery,
   - D10 "nothing should have failed except the designated dunning cohort",
   - D11 possible second-ladder on-hold carry-over.

Expected result
- Future watch rows should align with current task outcomes:
  - no nonexistent `S_FAIL` ladder should be treated as a real future fixture
  - no nonexistent `SUB_FAIL_RECOVERY` scenario should be expected on D9–D11

Actual result
- Task 82 says the original `S_FAIL` ladder never existed.
- Task 102 says the authored second-ladder recovery scenario cannot start honestly.
- The future watch schedule still expects both the original ladder closeout and the second recovery ladder.

Concrete proof
- Task 82 appended note:
  - `UNVERIFIED (no S_FAIL source fixture)` on 2026-08-05
  - says upstream execution published `S_FAIL unavailable`
- Task 102 appended note:
  - `UNVERIFIED (no original S_FAIL ladder; downstream recovery scenario impossible)` on 2026-08-05
  - says live re-check still returns zero ArraySubs subscription rows for user `351` / product `12108`
- `watch-schedule.md`
  - D8 still expects the cancelled `SLT Retry Daily` ladder outcome
  - D9 still expects `SUB_FAIL_RECOVERY` to fail and then recover
  - D10 still references the designated dunning cohort as if it existed
  - D11 still allows for a second dunning ladder to reach on-hold

## Known scope / counterexamples
- This issue is about future-watch planning consistency, not plugin runtime behavior.
- The D3 and D4 watch rows already contain partial corrective language:
  - they explicitly say the ladder is `UNVERIFIED (no source fixture)` where appropriate.
- The stale expectations are concentrated in D8–D11 future rows and any teardown prep that assumes `SUB_FAIL_RECOVERY` exists.
