---
id: 124
title: 'stage-17: Logging Settings popover missing always-on entity note'
status: closed
priority: medium
created: 2026-05-23T14:38:51.129579539+02:00
updated: 2026-05-24T19:48:04.152128871+02:00
started: 2026-05-24T19:44:07.512596804+02:00
completed: 2026-05-24T19:48:04.152127759+02:00
tags:
    - qa
    - stage-17
    - audits
    - logging-settings
    - ui
claimed_by: shell-quartz
claimed_at: 2026-05-24T19:48:04.152128771+02:00
class: standard
---

Source task: qa/stages/17-audits-and-logs/02-audit-logging-settings.md Sub-Task 02.1.\n\nBrowser: Activity Audits -> Logging Settings popover.\n\nObserved: Popover lists Product, Coupon, Email, Settings toggles, all checked, with descriptions for product-related changes, coupon applications, email send events, and plugin settings changes. No note is present stating Subscription, Member, Order, and System-level entries cannot be disabled.\n\nExpected: Info note states that Subscription, Member, Order, and System-level entries cannot be disabled.

[[2026-05-24]] Sun 19:45
Plan: add the expected always-on entity note to the existing Logging Settings popover, keep it in the current ActivityAudits.jsx/SCSS structure, rebuild arraysubs assets, then verify the popover in browser with screenshot proof.

[[2026-05-24]] Sun 19:47
Fix applied: added an always-on note to the Logging Settings popover and styled it in the Activity Audits SCSS. Rebuilt arraysubs assets with npm run build.

Verification: agent-browser opened Activity Audits, clicked the Logging Settings gear, and confirmed Product/Coupon/Email/Settings toggles are present plus the note: Subscription, Member, Order, and System-level entries are always recorded and cannot be disabled. Screenshot: qa/artifacts/issue-124/logging-settings-always-on-note.png.
