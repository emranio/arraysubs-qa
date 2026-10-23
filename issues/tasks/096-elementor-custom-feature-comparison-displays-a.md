---
id: 96
title: Elementor custom feature comparison displays a blank default operator
status: closed
priority: low
created: 2026-10-24T03:16:17.967337+06:00
updated: 2026-10-24T03:25:10.758159+06:00
started: 2026-10-24T03:16:45.769277+06:00
completed: 2026-10-24T03:25:10.717735+06:00
tags:
    - bug
    - qa
    - elementor
class: standard
---

Active QA: task 2; scheduled day 2026-10-24 (Asia/Dhaka); plan qa/unified-content-restrictions-2026-10-24/plan.md.
Affected subscriptions/order IDs: N/A; authoring issue only.
Affected user: WordPress administrator ID 1, login admin, role administrator; customers N/A.
Fixture: disposable Elementor page ID 20008, QA unified author Elementor 20261024.
Exact URL: http://localhost:10013/wp-admin/post.php?post=20008&action=elementor; isolated admin browser session unified-author-elementor.
Steps: Select the page Container, Advanced, ArraySubs Content Restrictions. Enable restriction. Choose Plan Feature Team Seats. Choose How To Check It = Custom comparison (advanced).
Expected: Comparison initially selects At least (>=), matching the default restriction comparator and other editors.
Actual: Comparison is blank; no option selected. Read-only browser observation shows the settings model default is HTML-encoded &gt;= while actual select option value is >=.
Proof: qa/unified-content-restrictions-2026-10-24/evidence/elementor-feature-comparison-before.png and elementor-feature-comparison-before.json.
Scope: Elementor advanced feature comparator default; has/min_total children and shared feature evaluator work. Other editors unaffected.
Fix plan: Use entity-safe internal operator option keys and map them to the shared comparator symbols at render time; verify the actual editor after reload, select a comparison and persist it through the UI.

[[2026-10-24]] Sat 03:25
Implemented: Elementor comparator option keys are now entity-safe gte/gt/eq/neq/lt/lte; render-time mapping translates those keys to shared comparison operators. The runtime also decodes entity-encoded symbols before applying the existing allowed comparator handling. Browser checks: new control default metadata is gte; the gte selection displays At least (>=) after publish/reload. Changing the UI selector to lt and publishing/reloading displays Less than (<) with settings value lt. PHP reflection checks passed 27 mapping/amount/aggregation assertions across all operator keys plus shared raw/entity symbol input. Evidence elementor-final-reloaded.png/.json and elementor-comparison-lt-reloaded.png/.json. The pre-existing fixture encoded default was intentionally selected through the UI once because development does not require migration of old settings.
