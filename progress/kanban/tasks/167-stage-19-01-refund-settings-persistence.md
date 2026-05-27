---
id: 167
title: 'stage-19: 01 Refund Settings Persistence'
status: closed
priority: medium
created: 2026-05-19T22:56:23.493724769+02:00
updated: 2026-05-24T22:38:12.963269731+02:00
started: 2026-05-23T08:06:53.480218257+02:00
completed: 2026-05-23T18:52:52.241396571+02:00
tags:
    - qa
    - stage-19
class: standard
---

Source: stages/19-refunds/01-refund-settings.md

[[2026-05-23]] Sat 18:46
Starting Stage 19 Task 01 refund settings persistence. Reading settings schema/UI and will use browser for save/reload proof; no lint/phpcs.

[[2026-05-23]] Sat 18:52
QA complete. Browser loaded Refunds page and confirmed controls/labels visible, defaults displayed: cancellation=Allow Immediate Refund, gateway=true, prorated=true, minimum=0. Minimum Refund Amount UI PASS: set 2.50, Save Settings showed success, DB stored 2.5. REST save handler persistence/sanitization PASS: end_of_period/off/off/2.5 saved; none/on/on/-5 sanitized to 0; immediate/on/on/9999999 capped at 10000; final restore immediate/on/on/0 saved. Browser reload after restore showed default values. General setting cross-check unchanged for grace days 3/7 and renewal reminder days 3; full arraysubs_settings retains refund keys. Failures: #154 cancellation behavior is not accessible as radio/select and could not be changed by browser; #155 switch clicks did not toggle Automatic Gateway Refund in browser. Downstream Task 02 start state restored.

[[2026-05-24]] Sun 22:33
Follow-up issue #154 fixed Refund on Cancellation control accessibility: it now renders as native radio inputs and browser role checks can select Refund at End of Period. Screenshot: qa/artifacts/issue-154/refund-settings-radio-group-eop-selected.png.

[[2026-05-24]] Sun 22:38
Follow-up issue #155 verified: Automatic Gateway Refund and Allow Prorated Refunds switches persist off/on through Save Settings + reload; minimum amount persists; final refund defaults restored and confirmed via WP-CLI.
