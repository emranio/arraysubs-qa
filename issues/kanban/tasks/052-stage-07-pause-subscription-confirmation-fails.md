---
id: 52
title: 'stage-07: Pause subscription confirmation fails'
status: closed
priority: critical
created: 2026-05-20T14:53:43.590846679+02:00
updated: 2026-05-21T22:35:15.763065305+02:00
started: 2026-05-21T22:25:07.092992035+02:00
completed: 2026-05-21T22:35:15.763064283+02:00
tags:
    - qa
    - stage-07
    - customer-portal
    - skip-pause
claimed_by: mold-glade
claimed_at: 2026-05-21T22:35:15.763065195+02:00
class: standard
---

Stage 07 Task 07. Enabled pause_subscription settings: enabled=true, customer_can_pause=true, max_duration_days=30, require_reason=false. On active cust1 subscription #633, Pause Subscription button opened modal with days input default 30, range 0-30, Cancel and Continue. Continuing opened confirmation; clicking Pause dismissed modal and showed a failure alert (Alumnium reported the subscription pause failed). WP-CLI confirms #633 remains arraysubs-active with no _pause_start_date, _pause_end_date, or pause count, and next payment unchanged. Expected: success notice "Subscription paused. Will auto-resume on [date].", status On-Hold, paused indicator, Resume Now button, shifted next payment, and auto-resume schedule.

[[2026-05-21]] Thu 22:27
Plan: verify #633 fixture/settings; patch customer pause/resume to run AJAX inside confirmDialog onConfirm with spinner/inline error and send REST key days; patch PauseManager to shift next/end date on pause, store original dates, recalc from originals on resume, reschedule renewal after resume; build arraysubs; browser-test pause 30d + early resume with Alumnium; verify status/meta/actions/notes; close or block.

[[2026-05-21]] Thu 22:35
Fixed/verified: customer pause/resume AJAX now runs inside confirmDialog onConfirm with modal spinner/error; pause request sends REST days; PauseManager stores original next/end dates, shifts visible timeline on pause, and restores timeline from original + actual paused days on resume. Built arraysubs. Browser QA on #633: pause 30d -> On Hold, Subscription Paused, Resume Now, next payment 20 July 2026 6:30 PM (UTC+6), auto-resume action #821 at 2026-06-20 20:32:41, no renewal process/invoice pending during pause. Resume Now -> Active, Skip Next Renewal + Pause Subscription visible, next payment restored 20 June 2026 6:30 PM (UTC+6), auto-resume cleared, renewal invoice/action #826/#827 scheduled. Notes #872 pause and #877 resume present.
