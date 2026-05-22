---
id: 89
title: 'stage-07: 09 Change Plan — Apply at Renewal'
status: closed
priority: high
created: 2026-05-19T22:56:10.509854192+02:00
updated: 2026-05-20T14:54:37.208271649+02:00
started: 2026-05-20T13:41:52.896630234+02:00
completed: 2026-05-20T14:54:37.208270627+02:00
tags:
    - qa
    - stage-07
claimed_by: mold-glade
claimed_at: 2026-05-20T14:54:37.208271549+02:00
class: standard
---

Source: stages/07-customer-portal/09-change-plan-apply-at-renewal.md

[[2026-05-20]] Wed 14:54
QA notes (2026-05-20, Chrome headless via Alumnium):
- Apply-at-renewal plan switch could not proceed because the Change Plan modal fails before any plan options load. On #633, clicking Change Plan shows "Failed to load plan options. Please try again."
- No deferred-switch preview, no no-charge-today confirmation, no pending-switch banner, no replacement prompt, and no pending-switch cancellation could be tested.
- Logged issue #53; also related to existing Stage 06 issue #39.
Result: FAIL/BLOCKED by plan-options load failure.
