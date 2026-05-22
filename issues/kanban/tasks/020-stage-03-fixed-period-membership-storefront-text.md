---
id: 20
title: 'stage-03: Fixed period membership storefront text missing'
status: closed
priority: high
created: 2026-05-20T08:34:15.019069183+02:00
updated: 2026-05-22T01:12:00.934752898+02:00
started: 2026-05-22T01:08:16.854418418+02:00
completed: 2026-05-22T01:12:00.934752037+02:00
tags:
    - qa
    - stage-03
    - fixed-period
    - pro
    - display
claimed_by: mold-glade
claimed_at: 2026-05-22T01:12:00.934752788+02:00
class: standard
---

Observed with Alumnium on 2026-05-20 for Library Week Pass (Absolute) #239 and Academic Year Pass (Recurring) #241. Products have fixed-period meta set and Pro active. Storefront displayed only normal recurring price (4.99 Every week / 9 Every year) and Subscribe Now. Expected Membership ends line, enrollment window for recurring product, not-yet-purchasable state outside enrollment window, and auto-renews text after Renew option. Actual fixed-period membership text/blocked state absent.

[[2026-05-22]] Fri 01:10
Verified Stage 03 Task 11, product meta, and Pro code. Products #239/#241 have fixed-period configuration but use existing meta flag _arraysubs_use_fixed_end_date=yes, while Pro display/purchasability checks only _arraysubs_fixed_end_date_enabled=yes, so EndDateCalculator::isFixedPeriodProduct() returns false and product page/cart filters never run. Plan: make fixed-period detection accept either fixed-period enabled flag, keep date calculation unchanged, align enrollment copy to 'Enrollment window:', reset opcache, then browser-verify #239 membership end and no enrollment/renew text, #241 membership end + enrollment window + renew copy + blocked purchase state.

[[2026-05-22]] Fri 01:11
Fix applied. Fixed-period membership detection now accepts both current _arraysubs_fixed_end_date_enabled and existing _arraysubs_use_fixed_end_date flags; enrollment copy now uses 'Enrollment window:'. Browser QA: Library Week Pass #239 shows 4.99 Every week, Membership ends: 27 May, 2026 (UTC+6), Subscribe Now, no enrollment window, no auto-renews text. Academic Year Pass #241 shows 9.00 Every year, Membership ends: 31 August, 2026 (UTC+6), Auto-renews into the next membership period., Enrollment window: 1 August, 2026 (UTC+6) – 31 October, 2026 (UTC+6), and no add-to-cart button because current date is outside enrollment window. debug.log remains 0 bytes.
