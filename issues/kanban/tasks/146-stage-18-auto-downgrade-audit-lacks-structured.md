---
id: 146
title: stage-18 auto-downgrade audit lacks structured change diff
status: closed
priority: high
created: 2026-05-23T17:41:45.447859943+02:00
updated: 2026-05-24T21:50:44.375824909+02:00
started: 2026-05-24T21:44:38.700635553+02:00
completed: 2026-05-24T21:50:44.375823807+02:00
tags:
    - stage-18
    - qa
    - bug
claimed_by: shell-quartz
claimed_at: 2026-05-24T21:50:44.375824809+02:00
class: standard
---

Stage: qa/stages/18-renewal-followup/08-trial-auto-downgrade.md\n\nFixture: subscription #1575, source product #1569 Trial Weekly w/ Downgrade, target product #231 Basic Plan, customer member-trial-down@example.com.\n\nAfter time-traveling _trial_end_date/_next_payment_date to 2026-05-23 14:36:40 UTC and running arraysubs_process_trial_conversions action #1318, the subscription correctly auto-downgraded to Active Basic Plan with _product_id=231, recurring_amount=9.99, _trial_length=0, _trial_end_date cleared, next_payment=2026-05-30 15:37:07 UTC. Browser admin/customer portal verified.\n\nActivity Audits only shows plain system/email rows: Auto-downgraded from Trial Weekly w/ Downgrade to Basic Plan due to trial expiry; Your subscription has been automatically changed to Basic Plan; Shipping settings recalculated; Email sent. No changes link / structured audit diff row was available for product change, Status Trial -> Active, or Trial Length 7 -> 0.\n\nExpected by Task 18.08: Activity Audits row shows product change (_product_id old -> new), Status Trial -> Active, Trial Length 7 -> 0.\n\nImpact: admins cannot inspect structured before/after audit data for trial-expiry auto-downgrades.

[[2026-05-24]] Sun 21:46
Plan: capture auto-downgrade before-state (product, status, trial length), run existing downgrade flow, then attach structured audit_changes to the private Auto-downgraded note after the subscription is active and trial state is cleared. Verify via #1575 by resetting to Trial/source product, rerunning arraysubs_process_trial_conversions, checking REST audit payload and Activity Audits changes modal. Skip lint/phpcs per workspace instruction; run php -l only.

[[2026-05-24]] Sun 21:50
Fixed and verified live. Change: AutoDowngradeHandler now attaches structured audit_changes to the private auto-downgrade note: Product Trial Weekly w/ Downgrade (#1569) -> Basic Plan (#231), Status Trial -> Active, Trial length 7 -> 0. Verification: reset #1575 to Trial/source #1569, temporarily set plan_switching.auto_downgrade_timing=on_trial_expire, reran arraysubs_process_trial_conversions, restored setting to on_expire. Result: #1575 Active product #231 recurring 9.99 trial_length 0 next_payment 2026-05-31 19:48:33 UTC; audit note #3084 has structured changes. agent-browser/agent-browser verified Activity Audits row has changes link and modal values. Screenshot: qa/artifacts/issue-146/activity-audits-auto-downgrade-changes-modal.png.
