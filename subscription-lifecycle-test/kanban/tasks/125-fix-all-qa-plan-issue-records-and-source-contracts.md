---
id: 125
title: Fix all QA-plan issue records and source contracts
status: done
priority: critical
created: 2026-08-11T22:29:27.565708904+02:00
updated: 2026-08-11T22:46:27.9867201+02:00
started: 2026-08-11T22:46:27.986719168+02:00
completed: 2026-08-11T22:46:27.986719168+02:00
tags:
    - qa-plan
    - maintenance
class: standard
---

Resolve every qa-plan-prefixed issue in subscription-lifecycle-test/issues by correcting its source task, calendar, watch schedule, or reference contract; preserve product findings; mark each plan issue resolved after consistency checks.

[[2026-08-11]] Tue 22:32
Inventory complete: audited all 13 qa-plan issue records against source tasks, watch-schedule.md, calendar.md, README source rules, and SLT-REF-03. Confirmed missing/UNVERIFIED fixture branches must be source-gated; implementation phase started.

[[2026-08-11]] Tue 22:45
Implementation complete in worktree /home/server-manager/qa-task-125: added authoritative source gates, corrected D4-D12/calendar/tail expectations, fixed task 119 and REF-03, normalized all 13 qa-plan records to resolved, and corrected the external D12 runbook. Starting final validation and merge.

[[2026-08-11]] Tue 22:46
Validation PASS: git diff --check; 13/13 qa-plan records carry resolved status; category index has 13/13 links; watch D1-D12 rows each retain 10 pipe fields; calendar D0-D10 rows each retain 7; task 119, REF-03, and external D12 runbook source gates verified. Docs/plan-only repair, so browser/plugin-code retest and PHPCS are not applicable.

[[2026-08-11]] Tue 22:46
Merged focused repair to QA main: source commit f19beda via merge commit bde3126. Self-review found no unresolved status tokens, broken category links, malformed tables, or stale named conflict wording.
