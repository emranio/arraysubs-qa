# SLT-SETUP-99B tail cohort source conflicts

Status: resolved 2026-08-11 — teardown is driven only by the published numeric tail registry

## Task / stage / plan

- QA progress task: planning/source-consistency issue derived from D13 teardown ownership and tail-cohort handoff tasks
- Stage: future-tail planning audit on Thursday, August 6, 2026
- Plan paths:
  - `qa/subscription-lifecycle-test/kanban/tasks/119-slt-setup-99b-post-watch-teardown-on-2026-08-15.md`
  - `qa/subscription-lifecycle-test/kanban/tasks/106-renewal-execution-after-a-synced-first-charge.md`
  - `qa/subscription-lifecycle-test/kanban/tasks/046-variation-level-flexible-sync-prove-the-purchased.md`
  - `qa/subscription-lifecycle-test/calendar.md`
  - `qa/subscription-lifecycle-test/plan-audit.md`

## QA progress task ID and stage

- Primary task: `#119 / SLT-SETUP-99B / setup / day-13`
- Related tasks: `#45 / SLT-SYN-06`, `#46 / SLT-SYN-13`, `#65 / SLT-CHK-13`,
  `#75 / SLT-SYN-11`, and `#106 / SLT-SYN-09`
- Relevant QA plan paths:
  - `qa/subscription-lifecycle-test/kanban/tasks/119-slt-setup-99b-post-watch-teardown-on-2026-08-15.md`
  - `qa/subscription-lifecycle-test/kanban/tasks/106-renewal-execution-after-a-synced-first-charge.md`
  - `qa/subscription-lifecycle-test/kanban/tasks/046-variation-level-flexible-sync-prove-the-purchased.md`
  - `qa/subscription-lifecycle-test/calendar.md`
  - `qa/subscription-lifecycle-test/plan-audit.md`

## Affected IDs

- Registry alias `SUB_2SEG`; numeric ID unresolved in this planning turn
- Authored `SUB_W`, Box Daily, and three `SLT-SYN-11` probe aliases; numeric IDs absent per source closeouts
- Authored `SLT-SYN-13` Full / Next Cycle variation subscriptions; live numeric IDs unresolved because task 46 says the fixtures do not exist
- Order IDs: `N/A`

## Affected user / customer context

- `slt-flex` / `slt-flex3` (variation-fixture owners named by task 46), numeric IDs unresolved in this planning turn
- `N/A` for `SUB_2SEG` owner in this issue file

## Exact routes / browser context

- `N/A` — planning/source-consistency issue found by comparing current task markdown and current board state

Reproduction steps
1. Read task 106 and note its handoff line: `SUB_2SEG` stays alive into the watch tail and must not be cancelled by the D10 wind-down.
2. Read the pre-fix and post-fix task 119 step-1 wording against the current 118/119 planning notes.
3. Observe that the original omission of `SUB_2SEG` from task 119 has been corrected, so the remaining requirement is to enforce that live 118 keep-alive cohort at D13 rather than trust any static authored list.
4. Read task 046's appended 2026-08-05 closeout note.
5. Observe that task 046 says the two `SLT-SYN-13` D02 variation purchases never existed and that no recovery path was taken.
6. Read current task 119 step 1 and observe that every source-absent alias is now conditional on numeric
   live-registry proof; the D13 operator does not infer fixtures from the authored list.

Expected result
- The authored D13 tail list should match current live task evidence:
  - every subscription that survives D10 should have an explicit D13 disposition
  - no nonexistent fixture should be required in the D13 tail-cancellation list

Actual result
- Before the Thursday, August 6, 2026 planning correction, `SUB_2SEG` was explicitly preserved past D10 by task 106 but omitted from task 119's explicit D13 tail list.
- Task 119's step-1 wording has now been corrected to include `SUB_2SEG` when 118 keeps it alive, but D13 must still reconcile the published 118 keep-alive cohort to the live registry before cancellation.
- Before the 2026-08-11 correction, task 119 still named source-absent `SUB_W`, Box Daily, SYN-11, and
  SYN-13 aliases in a way that could be misread as required tail members.

Concrete proof
- Task 106 handoff:
  - `SUB_2SEG` and both week subs stay alive into the watch tail; D10 must not cancel them.
- Task 119 step 1, after the 2026-08-11 correction:
  - names `SUB_2SEG` when 118's published keep-alive cohort carries it;
  - conditions `SUB_W`, Box Daily, all SYN-11 probes, and both SYN-13 variations on numeric registry proof.
- Task 046 appended note dated `2026-08-05`:
  - says no ArraySubs subscriptions owned by `slt-flex` or `slt-flex3` for parent product `SLT Flex Variable Daily` were found and no later authored recovery path exists.

## Known scope / counterexamples
- This issue is about teardown-source consistency, not plugin runtime behavior.
- Tail fixtures with current numeric-capable sources include:
  - `SUBID_GLOBAL` from task 61
  - `SLT-SYN-12` probes from task 88
  - `SLT-SYN-14` quantity subscription from task 62
- Source-absent aliases from tasks #45/#65/#75/#46 stay out of teardown unless a later valid replan
  publishes their numeric IDs.

## Resolution and verification

- The post-D10 calendar handoff now defines the D13 teardown cohort exclusively from 99A's published numeric
  tail registry.
- `SUB_2SEG` is retained when published by the live handoff. `SUB_W`, Box Daily, all SYN-11 probes, and both
  SYN-13 variations are conditional on numeric proof from their otherwise `UNVERIFIED` source tasks.
- Task #119's corrected live-registry contract agrees with the calendar; no nonexistent alias is a
  teardown prerequisite.
