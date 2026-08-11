# SLT-MYA-05 future follow-up source conflicts

Status: resolved 2026-08-11 — follow-ups B/C are source-gated

## Task / stage / plan

- QA progress task: planning/source-consistency issue derived from the future-affected MYA follow-up task below
- Stage: future-watch planning audit on Thursday, August 6, 2026
- Plan paths:
  - `qa/subscription-lifecycle-test/kanban/tasks/036-member-access-reacting-to-status-pro-member-add.md`
  - `qa/subscription-lifecycle-test/watch-schedule.md`
  - `qa/subscription-lifecycle-test/calendar.md`

## QA progress task ID and stage

- Primary future-affected task:
  - `#36 / SLT-MYA-05 / admin / day-02`
- Relevant future watch/schedule consumers:
  - `watch-schedule.md` rows D5 and D7
  - `calendar.md` rows D5 and D7
  - downstream dunning recovery planning
- Relevant QA plan paths:
  - `qa/subscription-lifecycle-test/kanban/tasks/036-member-access-reacting-to-status-pro-member-add.md`
  - `qa/subscription-lifecycle-test/watch-schedule.md`
  - `qa/subscription-lifecycle-test/calendar.md`

## Affected IDs

- `S_FAIL` source subscription alias; numeric ID unresolved because task 36 says no source fixture exists
- Order IDs: `N/A`

## Affected user / customer context

- user `351`, login/email `slt-fail` / `slt-fail@example.test`, role customer

## Exact routes / browser context

- `N/A` — planning/source-consistency issue found by comparing current task markdown with future schedule expectations

Reproduction steps
1. Read task 36's appended 2026-08-05 closeout note.
2. Observe that task 36 closes `UNVERIFIED (no S_FAIL source fixture)`.
3. Read the D5 and D7 schedule/watch rows.
4. Observe that they still expect:
   - D5 `SLT-MYA-05` follow-up B and immediate teardown
   - D7 `SLT-MYA-05` follow-up C after `SLT-DUN-04`

Expected result
- Future schedule rows should not expect `SLT-MYA-05` follow-ups if the source `S_FAIL` activation never existed and task 36 already closed `UNVERIFIED`.

Actual result
- Task 36 says the source purchase and bracket never existed.
- The future D5/D7 schedule rows still assume the follow-up observations occur.

Concrete proof
- Task 36 appended note dated `2026-08-05`:
  - says `slt-fail` exists as user 351 but owns no ArraySubs subscription rows
  - says task-specific members-access rule ids and setup bracket were never opened
  - closes the card `UNVERIFIED` without adding rules, pages, features, or a late checkout
- `watch-schedule.md` D5 row still starts with:
  - `SLT-MYA-05` follow-up B
- `watch-schedule.md` D7 row still includes:
  - immediate `SLT-MYA-05` follow-up C before `SLT-DUN-05`
- `calendar.md` D5 and D7 rows still include the same follow-up assumptions

## Known scope / counterexamples
- This issue is about future schedule consistency, not plugin runtime behavior.
- It is downstream of the same missing `S_FAIL` source fixture already noted in the dunning planning issue.

## Resolution and verification

- Task #36 is now named in the authoritative calendar/watch source gate.
- D5 follow-up B and D7 follow-up C remain conditional-only and are skipped for this run because no numeric
  source subscription or opened settings bracket exists.
