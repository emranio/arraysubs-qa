---
id: 42
title: 'stage-07: Trial subscription list row shows no next payment date'
status: closed
priority: medium
created: 2026-05-20T14:35:03.949418401+02:00
updated: 2026-05-22T05:27:37.481819339+02:00
started: 2026-05-22T05:22:06.797170122+02:00
completed: 2026-05-22T05:27:37.481818357+02:00
tags:
    - qa
    - stage-07
    - customer-portal
claimed_by: mold-glade
claimed_at: 2026-05-22T05:27:37.481819239+02:00
class: standard
---

Stage 07 Task 01.3 expects Trial rows to show a next payment/trial-end date. In My Account > Subscriptions, trial subscription #663 (Trial Weekly) displays Status Trial but Next Payment is "—" even though _next_payment_date is set. Expected: Trial rows should show the trial end / next payment date, similar to Active and On Hold rows.

[[2026-05-22]] Fri 05:23
Plan: verify Stage 07 Task 01 expectation and fixture #663 meta; update customer portal list next-payment condition to include arraysubs-trial when _next_payment_date exists; also align detail overview condition for trial subscriptions; syntax-check touched templates; browser verify cust1 My Account row #663 shows a real date instead of dash.

[[2026-05-22]] Fri 05:27
Fixed: customer portal list/detail now include arraysubs-trial in next-payment display when _next_payment_date exists. Syntax checks passed. agent-browser cust1 check: #663 Trial Weekly row shows Next Payment 27 May, 2026 6:31 PM (UTC+6), not dash; detail page also shows same Next Payment row.
