---
id: 127
title: SLT-RET-07 Retention analytics KPIs, charts, activity filters and source reconciliation
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags: [cycle-2, granular, retention, analytics, day-09]
due: "2026-09-01"
estimate: 2h
depends_on: [122, 123, 124, 125, 126]
class: standard
---

> **SLT-RET-07** · group `retention` · scheduled **D09** (2026-09-01)

## Objective
Reconcile the retention analytics UI against exact fresh-cycle events: eight KPIs, trend/category charts, recent activity, filters, date boundaries, empty state and no double counting.

## Required KPI reconciliation
Views, offers shown, accepted, declined, dismissals, cancellations prevented, retained recurring revenue, and acceptance/conversion rate (use the exact live labels if renamed; map them explicitly).

## Steps
1. Export exact source events from tasks 122-126 with offer/reason/subscription/user/gateway/product/time/value IDs. Freeze a UTC/site-local reporting cutoff.
2. Open retention analytics and record all eight KPI values, formula denominators and date range. Recompute from source rows and require exact equality/rounding.
3. Verify offer-type, reason, gateway, product, status/outcome and date filters alone and in combination; URL state, reset and pagination must be correct.
4. Reconcile trend chart buckets at site-local midnight, offer/reason distribution, saved-revenue values and recent-activity ordering against exact events.
5. Ensure view/decline/dismiss/accept and repeated reloads are not double counted; failed/rolled-back offers and non-SLT2 events are classified correctly.
6. Verify Stripe/Paddle segmentation and unsupported-offer negatives; no secret/provider payload appears in UI/CSV.
7. Test empty range, single event, boundary timestamp and CSV/export headers/rows. Require loading and user-visible error states for requests.
8. Capture UI/API/source tables and mark done only after every KPI/chart/filter row reconciles.

## Pass criteria
- [ ] Eight KPIs and formulas equal exact source events
- [ ] Charts/activity/filter/date/empty/export rows reconcile without duplicates
- [ ] Stripe/Paddle/product/reason/offer segmentation and privacy pass

Any discrepancy creates/updates the mandatory `qa/issues/` card with source event IDs, API/UI values, formula, date/timezone and screenshots; task remains blocked.
