---
id: 154
title: stage-19 refund behavior control is not accessible as radio/select
status: closed
priority: high
created: 2026-05-23T18:48:39.434159867+02:00
updated: 2026-05-24T22:33:42.105094522+02:00
started: 2026-05-24T22:24:48.321760697+02:00
completed: 2026-05-24T22:33:42.10509312+02:00
tags:
    - qa
    - stage-19
    - settings
    - accessibility
    - refunds
claimed_by: shell-quartz
claimed_at: 2026-05-24T22:33:42.105094422+02:00
class: standard
---

Stage 19 Task 01 expects Refund on Cancellation to be a radio group with Allow Immediate Refund / Refund at End of Period / No Automatic Refund. Browser accessibility tree exposes only static generic text 'Allow Immediate Refund' under the label; no interactive select/radio/options are available. Alumnium could not choose Refund at End of Period. This blocks real browser verification of changing the setting through the UI and also mismatches the documented radio-group affordance.

[[2026-05-24]] Sun 22:27
Plan: change RefundsSettings cancellation behavior from custom Select to existing FormBuilder Radio so the documented three options render as native radio inputs with labels. Build admin assets, then browser-verify Refund at End of Period can be selected from the real admin UI.

[[2026-05-24]] Sun 22:33
Fix: Refund on Cancellation now uses existing FormBuilder Radio instead of custom Select. Built arraysubs admin assets. Verified with Alumnium accessibility tree: three native radios exposed; Alumnium selected Refund at End of Period. Verified with Playwright role selector and screenshot: qa/artifacts/issue-154/refund-settings-radio-group-eop-selected.png; proof endOfPeriod=true, immediate=false, none=false.
