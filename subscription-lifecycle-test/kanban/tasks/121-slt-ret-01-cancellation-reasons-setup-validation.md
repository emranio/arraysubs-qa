---
id: 121
title: SLT-RET-01 Cancellation reasons setup, required validation, Other text and persistence
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags: [cycle-2, granular, retention, cancellation, day-03]
due: "2026-08-26"
estimate: 1h15m
depends_on: [10, 11, 60]
class: standard
---

> **SLT-RET-01** · group `retention` · scheduled **D03** (2026-08-26)

## Objective
Rebuild and verify the cancellation-reason foundation used by every retention offer: defaults, custom reason CRUD/order, required selection, Other free text, storage, notes/audit and exact restoration.

## Steps
1. Save the exact cancellation/retention settings presence/value snapshot. Work in an exclusive restore bracket.
2. In admin settings verify the seven default reasons, labels, enablement and order. Add custom key `shipping_issues`, label `Shipping issues`, save/reload and reorder it; require no duplicate or unrelated setting drift.
3. Enable Require reason and the Other option. On a dedicated active Stripe subscription open cancel flow; require a visible modal and validation when continuing without a reason.
4. Select every built-in/custom reason one at a time without final confirmation; verify offer eligibility changes by selected reason and dismiss safely.
5. Select Other, require non-empty free text, test blank/whitespace rejection and a sanitized Unicode/HTML-like input. Confirm once on the task-owned fixture.
6. Verify stored reason key/text, actor, timestamp, subscription note/audit/analytics event and admin/customer rendering. Raw markup must not execute or render unescaped.
7. Verify X, Escape, Back and browser refresh do not persist a reason, consume an offer or mutate status before confirmation.
8. Restore exact settings snapshot, prove diff empty, close sessions and register the confirmed fixture/state for task 98.

## Pass criteria
- [ ] Seven defaults plus `shipping_issues` CRUD/order persist correctly
- [ ] Required-reason and Other-text validation work in the real portal
- [ ] Confirmed reason is sanitized and stored once with correct actor/time/note/audit
- [ ] Dismissal paths mutate nothing; settings restore diff is empty

Any failure creates/updates a mandatory `qa/issues/` kanban card with task/stage/plan, subscription/user IDs, routes/session, steps, expected/actual and UI/settings/meta/note/audit proof; keep this task blocked until rerun passes.
