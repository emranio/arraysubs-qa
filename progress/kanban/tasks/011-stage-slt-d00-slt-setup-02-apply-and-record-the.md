---
id: 11
title: 'stage-slt-d00: SLT-SETUP-02 Apply and record the five window-wide baseline setting changes'
status: closed
priority: critical
created: 2026-08-22T20:43:39.660029378+02:00
updated: 2026-08-23T06:54:04.467420547+02:00
started: 2026-08-22T21:06:10.406026361+02:00
completed: 2026-08-22T21:20:31.537525565+02:00
tags:
    - cycle-2
    - subscription-lifecycle
    - stage-slt-d00
    - slt-setup-02
due: "2026-08-23"
estimate: 45m
depends_on:
    - 10
class: standard
---

Lifecycle task 11 / SLT-SETUP-02. Execute the complete numeric task at qa/subscription-lifecycle-test/kanban/tasks/011-slt-setup-02-apply-and-record-the-four-window-wide.md and record its individual browser, HPOS/meta, scheduler, provider, Mailpit, and issue result here. Stripe runs first; Paddle parity runs where declared. PayPal and Mollie are excluded. Do not close this progress task while the lifecycle task or a linked issue is unresolved.

## Result — PASS (2026-08-23 site date)

Real wp-admin saves established the five declared window-wide boolean changes. Full recursive comparison against the D0 settings blob found exactly those five differences and no unrelated drift; `sync_first_charge_mode` stayed `full` and retired `allow_reactivation` stayed absent. Save-toast/browser screenshots, priors, full post-save blob, JSON projection, Mailpit delta, and scheduler health are in `/home/server-manager/slt-evidence/SLT-SETUP-02-*`. Zero task-attributable mail, scheduler failure, debug-log change, browser error, or QA issue.

[[2026-08-23]] Sun 02:48

## Early-morning watcher reconciliation — 2026-08-23

Lifecycle task 11 / SLT-SETUP-02 remains passed and closed. The D00 facts snapshot and current-cycle settings evidence were reconciled. This watcher opened no settings/plugin/date/email bracket and performed no save; task 131 remains separately blocked on issue #1. Evidence: `/home/server-manager/slt-evidence/SLT-SETUP-02-settings-projection.json` and `automation/logs/D00-2026-08-23-early-morning-facts.txt`.

[[2026-08-23]] Sun 06:54
## D00 late-morning read-only reconciliation — 2026-08-23

Lifecycle `11 / SLT-SETUP-02` remains done: all declared values are preserved, with no undeclared settings drift. Current Shop Access exclusions for SLT2 product IDs `31340`, `31347`, `31357`, `31363` are declared product-task handoffs under issue #2, not watcher changes. No settings bracket or save occurred. Evidence: `/home/server-manager/slt-evidence/SLT-WATCH-D00-LATE-scheduler-mail-reconciliation.json` and the merged D00 watch report.
