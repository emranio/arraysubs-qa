---
id: 156
title: stage-19 prorated refund floors half-day usage
status: closed
priority: high
created: 2026-05-23T19:04:59.091791063+02:00
updated: 2026-05-24T22:43:25.292658122+02:00
started: 2026-05-24T22:38:37.387256821+02:00
completed: 2026-05-24T22:43:17.98080379+02:00
tags:
    - qa
    - stage-19
    - refunds
    - proration
class: standard
---

Task: stages/19-refunds/02-prorated-refund-on-cancellation.md

Fixture: cust2@example.com, Standard Weekly subscription #1704, Stripe order #1701, last_payment_date=2026-05-20 05:03:55 UTC, next_payment_date=2026-05-27 05:03:55 UTC. This is exactly halfway through a 7-day cycle.

Expected: preview should show cycle_days=7, days_unused=3.5, daily_rate≈2.86, refund_amount≈10.00.

Observed via REST preview /arraysubs/v1/subscriptions/1704/prorated-refund-preview: days_used=3, days_unused=4, refund_amount=11.42.

Code cause: ArraySubs\Features\PlanSwitching\Services\ProrationCalculator::getDaysUsed() returns int floor(diff / DAY_IN_SECONDS), so partial days are discarded before refund calculation. RefundProcessor note/reason also formats unused days with %d, losing decimals.

Impact: half-cycle weekly cancellations over-refund by about .42 on 9.99/week and do not match documented formula.

[[2026-05-24]] Sun 22:38
Plan: inspect prorated refund preview/calculation path, change day math to preserve fractional days instead of flooring, update note/reason formatting so decimal unused days are not lost, then verify REST preview for subscription #1704 returns half-cycle values before closing.

[[2026-05-24]] Sun 22:43
Fix: ProrationCalculator::getDaysUsed() now preserves fractional days rounded to 2 decimals instead of flooring to an int. Added ProrationCalculator::formatDayCount() and updated prorated refund reason/note/UI copy to keep decimal unused-day counts. Verification: PHP syntax checks passed; direct calculator for #1704 with controlled half-cycle dates returned cycle_days=7, days_used=3.5, days_unused=3.5, daily_rate=2.8557, refund_amount=10. Browser-authenticated REST preview /arraysubs/v1/subscriptions/1704/prorated-refund-preview returned same values; JSON artifact qa/artifacts/issue-156/subscription-1704-prorated-preview.json. Restored #1704 meta after verification.
