---
id: 84
title: 'stage-07: 04 Cancel at End of Billing Period'
status: closed
priority: high
created: 2026-05-19T22:56:09.851781657+02:00
updated: 2026-05-22T05:47:59.081110299+02:00
started: 2026-05-20T13:41:52.882636595+02:00
completed: 2026-05-20T14:46:26.108601411+02:00
tags:
    - qa
    - stage-07
claimed_by: mold-glade
claimed_at: 2026-05-22T05:47:59.081109748+02:00
class: standard
---

Source: stages/07-customer-portal/04-cancel-end-of-period.md

[[2026-05-20]] Wed 14:46
QA notes (2026-05-20, Chrome headless via agent-browser):
- Set cancellation.cancel_immediately=false via WP-CLI and confirmed modal behavior in browser on cust1 active Basic Monthly #643.
- Modal warning correctly used EOP language: subscription remains active until end of current billing period, then access ends. Reason dropdown appeared.
- Task-required reason "Just need a temporary break" is not present in configured options; selected nearest available "Not using it enough". Logged issue #49.
- Clicking Continue after valid reason showed "Failed to cancel subscription. Please try again". #643 remains Active with no pending-cancellation metadata/state and no Undo Cancellation button. Logged critical issue #48.
- Because scheduling failed, could not verify pending indicator, hidden Cancel button, Action Scheduler removal/EOP action, or admin Cancellation Details card.
Result: FAIL.

[[2026-05-22]] Fri 05:47
Issue #49 rechecked: required reason Just need a temporary break now appears in settings and customer cancel dropdown; no new code needed.
