---
id: 120
title: Audit and repair core JavaScript translation coverage
status: closed
priority: high
created: 2026-10-28T01:12:16.498484+06:00
updated: 2026-10-28T01:34:47.793381+06:00
started: 2026-10-28T01:12:39.593748+06:00
completed: 2026-10-28T01:34:47.793381+06:00
tags:
    - i18n
    - core
parent: 119
class: standard
---

User-authorized translation readiness audit. Active QA plan/day N/A; subscription/order/customer IDs N/A. Scope: core JS/JSX display strings, domains, pluralization and translator comments. Parent issue #119 contains the browser reproduction and overall plan.

[[2026-10-28]] Wed 01:12
Audit plan: inspect all 324 core JS/JSX files with an AST-based display-string scan; repair confirmed untranslated UI strings, wrong text domains, unsafe interpolation/plural handling and shared-library defaults; parse changed files and inspect line counts; parent handles production build and browser verification.

[[2026-10-28]] Wed 01:21
Core JS readiness handoff: audited all 326 JS/JSX source files with Babel AST. Repaired shared DataList/FormBuilder defaults, portal fallbacks and proration copy, email placeholder help, statuses, modal/toast labels, dynamic gettext keys, English-only fragments/count suffixes, locale-aware numbers/dates/currency, and checkout session detection that depended on English UI text. Added standard rc-field-form validation-message configuration wrapper preserving hooks/statics/custom messages, and shared requested-locale helper. Added missing source translator comments. Focused executable checks passed with installed WordPress i18n and rc-field-form: Spanish required validation, static APIs, Russian three-form billing period plurals, request-locale currency, malformed-locale fallback, and Spanish checkout stale/loaded-state decisions. Babel syntax audit has zero errors; changed JS files all under 3000 lines (maximum 2173). Parent handles production build, browser integration, changelog and closing this task. No source edits remain planned.

[[2026-10-28]] Wed 01:25
Final catalog review follow-up: repaired residual schedule.js English plural suffix in every-N-period labels and fixed/flexible cycle-length messages using _n. Full-flexible allowed periods remain singular rather than receiving Array.map indexes as counts. Russian 1/2/5 schedule-period and cycle-length checks passed against the installed WordPress i18n runtime. schedule.js is 86 lines. Parent must rebuild core and any Pro entrypoint importing this shared module.

[[2026-10-28]] Wed 01:34
Parent integration and browser signoff completed: source merged to core main at 8532f16; both production builds and POT generation passed. Fresh Loco Spanish translations render for the lazy core action and shared bulk-action labels; core-only mode passed. Customer pause-dialog Cancel translated and was dismissed without changing status. Bundled/system catalogs work with Loco inactive, native WordPress translation assertions passed (14 + 17 + 17), and temporary locale/catalog state was restored. Closing this audit subtask after parent verification.
