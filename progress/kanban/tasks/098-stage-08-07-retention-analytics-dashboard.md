---
id: 98
title: 'stage-08: 07 Retention Analytics Dashboard'
status: closed
priority: high
created: 2026-05-19T22:56:11.973336093+02:00
updated: 2026-05-22T05:39:59.123561104+02:00
started: 2026-05-20T13:41:52.926728671+02:00
completed: 2026-05-20T15:21:11.352582467+02:00
tags:
    - qa
    - stage-08
claimed_by: mold-glade
claimed_at: 2026-05-22T05:39:59.123560764+02:00
class: standard
---

Source: stages/08-retention/07-retention-analytics.md

[[2026-05-20]] Wed 15:21
QA notes: WooCommerce Analytics Retention page loads at wc-admin path with date inputs, Products filter, eight summary cards, charts, and Activity Log. Summary cards visible: Total Cancellations 2, Churn Rate 0.0%, Avg. Age at Cancel 0d, Avg. Payments Before Cancel 0, Offers Shown 0, Offers Accepted 0, Offer Success Rate 0.0%, Retained Revenue $0.00. Offer KPIs fail expected non-zero values because Stage 08 offers could not load/accept (issue #61). Cancellation Reasons chart shows only not_provided 100% even though Activity Log Scheduled Cancel rows show reason not_using (issue #62). Offer Outcomes section says No offer data for this period. Trend chart renders series legends for Cancellations, New Subscriptions, Offers Accepted, Offers Shown. Activity Log event filter works: selecting Offer Accepted shows No activity logs found for this period. Products filter search opens and returns [QA] Pro Monthly / Pro Monthly options; selection interaction was flaky in agent-browser. No Pro upsell observed.

[[2026-05-22]] Fri 05:39
Issue #62 fixed: Retention analytics reason chart now includes real cancellation-intent reasons from cancelled + scheduled_cancel logs, not only not_provided. Browser verified legend includes found_alternative, not_using, temporary_pause, qa role mapping, and Activity Logs show customer/product names.
