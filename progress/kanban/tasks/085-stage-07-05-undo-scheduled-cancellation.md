---
id: 85
title: 'stage-07: 05 Undo Scheduled Cancellation'
status: closed
priority: high
created: 2026-05-19T22:56:10.022389428+02:00
updated: 2026-05-20T14:49:18.020342332+02:00
started: 2026-05-20T13:41:52.884893473+02:00
completed: 2026-05-20T14:49:18.02034111+02:00
tags:
    - qa
    - stage-07
claimed_by: mold-glade
claimed_at: 2026-05-20T14:49:18.020342222+02:00
class: standard
---

Source: stages/07-customer-portal/05-undo-scheduled-cancel.md

[[2026-05-20]] Wed 14:49
QA notes (2026-05-20, Chrome headless via agent-browser):
- Task 04 did not produce a real scheduled-cancel artifact because portal EOP cancellation failed (#48). To exercise the Undo UI, created pending cancellation state on #643 with arraysubs_schedule_cancellation(643, "not_using", "customer", []).
- After refresh, #643 showed expected pending state: banner "This subscription is scheduled to cancel on 20 June, 2026 6:31 PM (UTC+6)", Status row Active + Pending Cancellation, Cancels On row, Cancel button hidden, Undo Scheduled Cancellation button visible, and note with reason Not using it enough.
- Clicking Undo Scheduled Cancellation opened a confirmation modal with Cancel and Undo Cancellation. Clicking Undo Cancellation closed the modal, but no success notice appeared and pending state remained. WP-CLI still shows _waiting_cancellation=1, _cancellation_scheduled_date=2026-06-20 12:31:06, _cancellation_reason=not_using. Logged issue #50.
- Metadata cleanup, restored scheduling, and cancel-again reset checks failed/not reachable.
Result: FAIL.
