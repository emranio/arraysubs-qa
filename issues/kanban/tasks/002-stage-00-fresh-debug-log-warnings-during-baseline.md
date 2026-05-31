---
id: 2
title: 'Stage 00: Fresh debug.log warnings during baseline checks'
status: closed
priority: high
created: 2026-05-19T22:38:45.053594072+02:00
updated: 2026-05-21T23:12:36.818902535+02:00
started: 2026-05-21T23:07:53.330946302+02:00
completed: 2026-05-21T23:12:36.818901563+02:00
tags:
    - qa
    - bug
    - stage-00
claimed_by: mold-glade
claimed_at: 2026-05-21T23:12:36.818902425+02:00
class: standard
---

Found in qa/stages/00-preflight/01-fresh-install-check.md, Sub-Task 1.6. `wp-content/debug.log` contains fresh warnings on 2026-05-19, including repeated `wp-config.php` constant redefinition warnings and admin warning entries.

[[2026-05-21]] Thu 23:09
Verified issue against Stage 00 Task 01 Sub-Task 1.6. Current wp-content/debug.log contains historical PHP Deprecated/Fatal/DB/parse entries, including recent WP-CLI parse errors from this QA session. wp-config.php guards WP_DEBUG but defines WP_DEBUG_LOG and WP_DEBUG_DISPLAY unconditionally, matching reported constant redefinition risk. Plan: guard wp-config debug/custom constants so repeated bootstrap/predefined constants do not emit redefinition warnings, set display default false while keeping WP_DEBUG_LOG true for QA, reset debug.log baseline after preserving findings in task note, then use Alumnium admin page loads plus WP-CLI log tail to confirm no new PHP Notice/Warning/Deprecated/Fatal entries from ArraySubs/ArraySubsPro.

[[2026-05-21]] Thu 23:12
Fixed and verified. Code/env: guarded WP_DEBUG_LOG, WP_DEBUG_DISPLAY, FS_METHOD, and DISABLE_WP_CRON in wp-config.php; WP_DEBUG still true, WP_DEBUG_LOG true, WP_DEBUG_DISPLAY false. Preserved old log at /tmp/arraysubs-debug-issue2-before.log, reset wp-content/debug.log baseline. Browser QA with Alumnium: admin Dashboard login, WooCommerce Settings, and ArraySubs General Settings loaded; ArraySubs General Settings showed no PHP warning/notice/fatal/red banner. WP-CLI after browser smoke: wp-content/debug.log remained 0 bytes. Lint/phpcs skipped per instruction.
