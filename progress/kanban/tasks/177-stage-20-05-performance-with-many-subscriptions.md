---
id: 177
title: 'stage-20: 05 Performance With Many Subscriptions'
status: closed
priority: high
created: 2026-05-19T22:56:24.855685268+02:00
updated: 2026-05-23T21:39:50.378548997+02:00
started: 2026-05-23T08:06:53.494649672+02:00
completed: 2026-05-23T21:39:50.378548075+02:00
tags:
    - qa
    - stage-20
claimed_by: mold-glade
claimed_at: 2026-05-23T21:39:50.378548907+02:00
class: standard
---

Source: stages/20-edge-and-regression/05-performance-with-many-subscriptions.md

[[2026-05-23]] Sat 21:28
Seeded 250 performance subscriptions via WP-CLI direct arraysubs_create_subscription(). Total subscriptions now 301. Counts: active 179, trial 42, on-hold 28, cancelled 35, expired 14, pending 3.

[[2026-05-23]] Sat 21:39
QA results: REST /wp/v2/arraysubs_data all page1 419.9ms, page2 244.6ms, page3 307.2ms, page5 288.4ms, last page 36.0ms; total 301, 16 pages. Filters: trial 399.7ms total 42/3 pages; on-hold 261.3ms total 28; cancelled 239.6ms total 35; expired 249.6ms total 14; active 263.3ms total 179. Combined active+perf pages 1-2 ~278ms, total 100/5 pages. Browser verified subscriptions list 301, status counts, Trial(42) page 1 of 3, next page 2 of 3. Analytics Overview browser loaded cards; REST performance 260ms, charts 181-917ms. Scheduled-job logs browser loaded page 1 of 10/283; REST 23.8ms. PHP memory_limit 512M; peak WP REST memory 145.81MB; debug.log unchanged since 18:01 UTC, no new notices/fatals during perf run. FAIL carried: exact email search still empty; existing issue #91 updated.
