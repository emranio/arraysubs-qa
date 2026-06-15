---
id: 87
title: 'stage-07: 07 Pause Subscription and Resume'
status: closed
priority: high
created: 2026-05-19T22:56:10.26376236+02:00
updated: 2026-05-20T14:53:53.128759783+02:00
started: 2026-05-20T13:41:52.887058111+02:00
completed: 2026-05-20T14:53:53.1287583+02:00
tags:
    - qa
    - stage-07
claimed_by: mold-glade
claimed_at: 2026-05-20T14:53:53.128759693+02:00
class: standard
---

Source: stages/07-customer-portal/07-pause-and-resume.md

[[2026-05-20]] Wed 14:53
QA notes (2026-05-20, Chrome headless via agent-browser):
- pause_subscription settings enabled via WP-CLI: enabled=true, customer_can_pause=true, max_duration_days=30, require_reason=false. Used active #633; original next payment 20 June 2026 6:30 PM (UTC+6).
- Pause Subscription control visible. Modal opened with heading Pause Subscription, days input default 30, range 0-30, helper text "0 = indefinite", Cancel and Continue. No reason field because require_reason=false.
- Continue opened confirmation; clicking Pause failed. agent-browser reported an assertive failure alert after modal closed. WP-CLI confirms #633 stayed arraysubs-active, no pause_start/end metadata, pause_count=0, next payment unchanged. Logged issue #52.
- Because pause failed, could not verify On-Hold status, paused indicator, Resume Now, shifted next payment, auto-resume scheduling, resume cleanup, or notes audit.
Result: FAIL.
