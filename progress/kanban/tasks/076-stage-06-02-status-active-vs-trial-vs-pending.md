---
id: 76
title: 'stage-06: 02 Status — Active vs Trial vs Pending'
status: closed
priority: high
created: 2026-05-19T22:56:08.643003666+02:00
updated: 2026-05-22T05:05:47.390677972+02:00
started: 2026-05-20T13:41:52.825638652+02:00
completed: 2026-05-20T13:58:32.434587494+02:00
tags:
    - qa
    - stage-06
claimed_by: mold-glade
claimed_at: 2026-05-22T05:05:47.390677611+02:00
class: standard
---

Source: stages/06-initial-lifecycle/02-status-active-vs-trial-vs-pending.md

[[2026-05-20]] Wed 13:58
QA complete. Active artifact from Stage 05 Task 01 is absent because classic checkout failed; using control active subscription #357 (customer-block@example.test, Standard Weekly, order #340), already filed issue #35. Active tab/control: #357 active, Standard Weekly, next payment 27 May 2026, recurring 9.99/week. Trial tab: #384 customer-trial@example.test appears with status Trial, product Trial Weekly, next/trial end 27 May 2026; task expected Trial 14-Day/14 days but actual Stage 05 artifact is 7-day Trial Weekly, filed issue #37. Pending case created by real browser checkout: customer-pending@example.test, Basic Monthly #197, BACS order #610, total 9.99, related subscription #618 Pending, next payment 20 June 2026. Admin Pending tab showed #618 Pending, Basic Monthly, next 20 June 2026. Marked order #610 completed via WP-CLI; subscription #618 moved to Active, completed_payments=1, next payment remains 2026-06-20 11:53:02. DB counts after promotion: active=7, pending=1 (older pending #441), trial=2. Trial and Pending rows also showed Delete row action; active Delete issue already filed as #36.

[[2026-05-22]] Fri 05:05
Issue #37 fixed: Stage 06 trial status docs now use Trial Weekly with 7-day trial. Browser verified subscription #384 shows Trial status, Trial Weekly, Trial Length 7 day(s), Trial Ends and Next Payment 27 May, 2026.
