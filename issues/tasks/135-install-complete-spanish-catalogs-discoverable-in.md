---
id: 135
title: Install complete Spanish catalogs discoverable in Loco Translate
status: closed
priority: high
created: 2026-10-28T03:28:05.509017+06:00
updated: 2026-10-28T03:44:40.613595+06:00
started: 2026-10-28T03:28:17.569934+06:00
completed: 2026-10-28T03:44:40.613595+06:00
tags:
    - bug
    - i18n
class: standard
---

User reports no way to translate Subscribe Now from its General setting and Loco Translate reports no arraysubs Spanish catalog. New QA scope 2026-10-28, plan qa/translation-followup-2026-10-28/plan.md. Users/orders/subscriptions N/A; admin context, http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general and Loco plugin overview. Proof: user screenshots and languages folders contain only POT files; cached reports are sample_only and did not publish PO/MO/JSON. Plan: run complete Spanish-only translation for both domains using existing Luna workflow; make source-label translation action discoverable beside settings and default buttons findable; verify Loco catalogs plus actual Spanish static and saved-label output in browser, preserve source and other site settings.

[[2026-10-28]] Wed 03:35
User narrowed scope: fix only the missing Spanish catalogs in Loco; no dedicated translation UI. Reverted this follow-up’s default-label UI/registry/runtime experiments and cleared its temporary default-label translation. Remove the earlier screen’s menu and route. Continue full Spanish catalogs only, then verify Loco for both text domains and representative PHP/JS translations. Existing storefront/provider compatibility remains.

[[2026-10-28]] Wed 03:36
Final correction from user: leave existing UI exactly as it is. All UI/default-label experiments and attempted menu/route removals have been restored to the original committed source; both product source repos are clean. Only missing complete es_ES PO/MO/JSON catalogs remain in scope. Plan updated accordingly.

[[2026-10-28]] Wed 03:40
Both complete catalogs now installed and verified in real Loco: core 7,375 strings and Pro 1,945 strings, each 100% with zero pending. User additionally requests fixing the npm translation script. Address run reliability: actionable repeated-brand repair diagnostics, isolate custom/test output reports from installed catalog reports, clear sample/full installation output and stale script-reference preflight. Preserve existing translation policy/cache to avoid retranslating completed work.

[[2026-10-28]] Wed 03:44
Completed: both Loco overviews and editors show es_ES 100% with 0 pending (core7375, Pro1945). Full PO/MO plus77/29 current-path JSON catalogs installed. Shared script fixes passed51 offline tests; actual command reruns reused every translation with0 SDK calls. Existing UI/source settings unchanged. Evidence/report: qa/translation-followup-2026-10-28/report.md. Core script committed and Pro draft recorded.
