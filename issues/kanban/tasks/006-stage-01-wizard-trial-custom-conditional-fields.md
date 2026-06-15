---
id: 6
title: 'Stage 01: Wizard trial/custom conditional fields missing'
status: closed
priority: high
created: 2026-05-20T00:46:34.863908118+02:00
updated: 2026-05-21T23:52:40.314138827+02:00
started: 2026-05-21T23:23:36.841830258+02:00
completed: 2026-05-21T23:52:40.281250285+02:00
tags:
    - qa
    - bug
    - stage-01
claimed_by: mold-glade
claimed_at: 2026-05-21T23:52:40.314138527+02:00
class: standard
---

Stage 01 Task 03 findings on live site via Chromium/agent-browser fallback after agent-browser Chrome session failed. Sub-task 3.1: Selecting Do you offer free trials = Yes does not reveal expected follow-up questions 'Should a payment method be required for a free trial?' or 'Limit free trials to one per customer?'. Sub-task 3.2: Selecting billing cycle = Custom does not reveal expected custom interval fields 'Every how many billing periods?' and 'Which period should that custom interval use?'. Screenshots: /tmp/arraysubs-qa-agent-browser/stage01-task03-step1.png and dump-step1.png. Expected: follow-up fields appear immediately without reload. Actual: fields absent from visible UI and DOM field dump.

[[2026-05-21]] Thu 23:26
Verified Stage 01 Task 03. Current code conditionals are correct, and label-click via JS reveals trial/custom follow-ups, but browser automation clicking the radio accessibility target focuses the option without firing the controlled React onChange; selected value stays No/Monthly and follow-up fields stay hidden. Plan: make wizard radio controls resilient by setting the answer on input onClick as well as onChange for radio-card and regular radio fields. Then rebuild and browser-test automation click on Free trials Yes and Billing cycle Custom so follow-up fields appear immediately; confirm debug.log stays clean.

[[2026-05-21]] Thu 23:42
Found deployment/cache layer blocker during retest: async wizard chunk /wp-content/plugins/arraysubs/public/build/8089.chunk.js is cached by Cloudflare for 15 days and has no version query. Browser loaded old wizard JS even after rebuild, so fixed webpack async chunk filenames to include contenthash.

[[2026-05-21]] Thu 23:46
Root cause narrowed further: in short admin viewport the setup wizard sticky footer overlays the final radio controls. agent-browser/accessibility click targeted the Yes radio, but elementFromPoint at the radio center was .arraysubs-setup-wizard__footer. Removed sticky footer positioning so controls cannot sit under navigation.

[[2026-05-21]] Thu 23:49
Also found main admin CSS enqueued with fixed ARRAYSUBS_VERSION (1.7.10), so Cloudflare served stale sticky-footer CSS. Patched MainAdmin enqueue to use webpack asset version for mainadmin.css as well as mainadmin.js.

[[2026-05-21]] Thu 23:52
Verified fixed on live site with agent-browser after rebuild/opcache reset. Free trials: clicking Yes now checks Yes and reveals 'Should a payment method be required for a free trial?' plus 'Limit free trials to one per customer?'; clicking No hides them. Billing cycle: clicking Custom reveals 'Every how many billing periods?' and 'Which period should that custom interval use?'; clicking Monthly hides them. debug.log stayed 0 bytes. Skipped lint/phpcs per instruction.
