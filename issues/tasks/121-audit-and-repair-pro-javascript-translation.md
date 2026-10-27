---
id: 121
title: Audit and repair Pro JavaScript translation coverage
status: closed
priority: high
created: 2026-10-28T01:12:16.510828+06:00
updated: 2026-10-28T01:34:52.724109+06:00
started: 2026-10-28T01:12:40.555749+06:00
completed: 2026-10-28T01:34:52.724109+06:00
tags:
    - i18n
    - pro
parent: 119
class: standard
---

User-authorized translation readiness audit. Active QA plan/day N/A; subscription/order/customer IDs N/A. Scope: Pro JS/JSX display strings, domains, pluralization and translator comments. Parent issue #119 contains the overall plan.

[[2026-10-28]] Wed 01:12
Plan: run a Babel AST audit of JS/JSX visible text and i18n calls, repair confirmed coverage/domain/interpolation/plural problems without changing data keys, verify parseability and exact extraction readiness, then hand off to parent for build/POT/browser integration.

[[2026-10-28]] Wed 01:19
Implemented Pro JS/JSX coverage repairs in /tmp/arraysubs-translation-readiness/arraysubspro. Audited all 140 JS/JSX files with Babel: no unwrapped visible JSX text/display attributes, no dynamic gettext keys or missing/wrong literal domains. Localized Pro DataList/FormBuilder defaults/actions/accessibility strings and complete messages; repaired Select dynamic key; preserved API filter values/stored toggle values; fixed feature/bulk/relative-time plurals; migrated premium AnalyticsOverview strings to arraysubspro; replaced positional-placeholder-unsafe formatter with WP sprintf; used shared core getRequestLocale for Intl. All 140 files parse; modified source files <=3000 lines; diff whitespace check passed. Focused helper verification using real local WordPress hooks+i18n passed reordered/numbered placeholders, percent escapes, singular/plural days, contextual day/percentage-point abbreviations, and request-locale precedence. No build/POT or browser testing performed by this agent; parent coordinating those. Awaiting parent integration/browser verification before closure.

[[2026-10-28]] Wed 01:24
Independent parent runtime/build review found and helped resolve two additional defects: invalid entrypoint JSON was still embedded by native WP printing after preparatory validation; newly inserted wp-i18n dependencies lacked native group metadata, then needed promotion to earliest consumer group. Parent fixed both and added regressions. Re-ran real WP_Scripts head/footer output: no loader PHP warnings; head target and dependency chain print in head in order; footer target and chain stay in footer in order. Own DataList added explicit translated restore/default bulk prompts and tidied newly added sprintf blocks. Remaining integration/browser checks belong to parent.

[[2026-10-28]] Wed 01:26
Added portable actual-render regression at core workflow-scripts/tests/script-translations-render.php at parent request. It uses fresh native WP_Scripts, no filesystem/catalog fixtures, and optional footer argument; asserts no sorting/rendering warnings, repaired dependency metadata, head/footer promotion, and actual script output order. Restores global registry/hooks/output buffers. WP-CLI passed 17 assertions in head mode and 17 in footer mode; unrelated Elementor bootstrap deprecation occurs before capture. File has 125 lines; whitespace check passed.

[[2026-10-28]] Wed 01:34
Parent integration and browser verification completed. Pro source merged as 6d5a403; core integration 8532f16. Spanish Pro Horizon control works with Loco custom catalogs and standard system catalogs with Loco disabled; shared core labels embedded in Pro subscription-bundle catalogs work in both locations; core-only case passes. Native script suite passed 14 assertions, plus actual-render head and footer tests passed 17 each. Parent restored test locale, sample catalogs, and plugin state. Translation audit and integration are complete; multilingual file-generation workflow remains deferred as requested.
