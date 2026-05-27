---
id: 162
title: 'stage-18: 08 Trial Auto-Downgrade'
status: closed
priority: high
created: 2026-05-19T22:56:22.670522605+02:00
updated: 2026-05-24T21:50:44.381745141+02:00
started: 2026-05-23T08:06:53.474845271+02:00
completed: 2026-05-23T17:42:06.612244944+02:00
tags:
    - qa
    - stage-18
class: standard
---

Source: stages/18-renewal-followup/08-trial-auto-downgrade.md

[[2026-05-23]] Sat 17:31
Continuing QA. Documented fixture absent; creating member-trial-down + Trial Weekly w/ Downgrade source product, target Basic Plan #231, temp setting auto_downgrade_timing=on_trial_expire.

[[2026-05-23]] Sat 17:41
QA complete. Fixture created: user #45 member-trial-down@example.com, source product #1569 Trial Weekly w/ Downgrade, target Basic Plan #231, subscription #1575. Baseline browser: #1575 Trial on source product, product Linked Products auto-downgrade target Basic Plan #231. Temp setting plan_switching.auto_downgrade_timing=on_trial_expire. Time-traveled _trial_end_date/_next_payment_date to 2026-05-23 14:36:40 UTC, ran arraysubs_process_trial_conversions action #1318. Result: #1575 Active, product #231 Basic Plan, recurring 9.99/week, _trial_length=0, _trial_end_date cleared, next_payment=2026-05-30 15:37:07 UTC. Admin detail verified Active Basic Plan and Auto Downgrade note/email. Scheduled-Job Logs browser verified Process Trial Conversions Success. Customer portal verified Active Basic Plan, 30 May 2026, .99 every week. Restored global auto_downgrade_timing=on_expire. Defect logged #146: Activity Audits lacks structured change diff for product/status/trial fields. Existing mail catcher blocker #137 still blocks raw email-body verification.

[[2026-05-24]] Sun 21:50
Issue #146 fixed and reverified. Auto-downgrade audit row for subscription #1575 now exposes a changes modal with Product Trial Weekly w/ Downgrade (#1569) -> Basic Plan (#231), Status Trial -> Active, Trial length 7 -> 0. Screenshot: qa/artifacts/issue-146/activity-audits-auto-downgrade-changes-modal.png.
