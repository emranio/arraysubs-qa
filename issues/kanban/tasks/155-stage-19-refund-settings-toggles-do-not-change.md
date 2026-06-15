---
id: 155
title: stage-19 refund settings toggles do not change from browser clicks
status: closed
priority: high
created: 2026-05-23T18:51:05.625232429+02:00
updated: 2026-05-24T22:38:23.008356976+02:00
started: 2026-05-24T22:33:58.479146989+02:00
completed: 2026-05-24T22:38:18.296785844+02:00
tags:
    - qa
    - stage-19
    - settings
    - refunds
    - ui
class: standard
---

Stage 19 Task 01. Browser page exposes Automatic Gateway Refund and Allow Prorated Refunds as focusable checkboxes, but clicking the Automatic Gateway Refund checkbox did not change its checked state; saving then reloading left the setting true in both UI and DB. Pressing the control via agent-browser also mapped to Enter/no state change. Expected: toggle controls should visibly switch on/off and persist after Save Settings.

[[2026-05-24]] Sun 22:34
Plan: reproduce toggle behavior in browser, inspect FormBuilder Switch implementation, then fix the shared switch control so clicks/keyboard toggle state and settings persist. Build admin assets and re-run Stage 19 Task 01 toggle persistence checks for both switches.

[[2026-05-24]] Sun 22:38
Verification: current FormBuilder Switch implementation exposes role=switch controls with explicit click handlers. agent-browser test toggled Automatic Gateway Refund off/on and Allow Prorated Refunds off/on, saved after each change, reloaded after each save, and observed persisted states. Minimum Refund Amount persisted at 2.5, then final defaults restored. Screenshot: qa/artifacts/issue-155/refund-settings-toggles-default-after-persistence.png. WP-CLI final DB check: refunds.cancellation_behavior=immediate, auto_gateway_refund=true, allow_prorated_refunds=true, minimum_amount=0.
