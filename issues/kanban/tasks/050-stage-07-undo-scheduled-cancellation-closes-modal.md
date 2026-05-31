---
id: 50
title: 'stage-07: Undo scheduled cancellation closes modal but does not clear pending state'
status: closed
priority: critical
created: 2026-05-20T14:49:00.97745059+02:00
updated: 2026-05-21T22:10:08.412725975+02:00
started: 2026-05-21T21:43:01.056695307+02:00
completed: 2026-05-21T22:10:08.412724873+02:00
tags:
    - qa
    - stage-07
    - customer-portal
    - cancellation
claimed_by: mold-glade
claimed_at: 2026-05-21T22:10:08.412725874+02:00
class: standard
---

Stage 07 Task 05. Because real EOP cancellation failed (#48), seeded pending-cancellation state on subscription #643 through arraysubs_schedule_cancellation(), which displayed the expected pending banner, Active + Pending Cancellation status, Cancels On date, and Undo Scheduled Cancellation button. Clicking Undo Scheduled Cancellation opened a confirmation modal. Clicking Undo Cancellation closed the modal but left the page in pending-cancellation state: banner still visible, button still says Undo Scheduled Cancellation, and WP-CLI still shows _waiting_cancellation=1, _cancellation_scheduled_date=2026-06-20 12:31:06, _cancellation_reason=not_using. Expected success notice "Scheduled cancellation removed successfully. Your subscription will continue renewing normally." and all cancellation metadata cleared.

[[2026-05-21]] Thu 21:43
Plan: use existing pending #643 from Task 04; verify undo REST/helper path clears cancellation meta and reschedules renewal jobs; browser-test Undo Scheduled Cancellation with Stage 07 settings; if AJAX/backend still fails, patch customer undo handler/helper; confirm Active state, Cancel button restored, pending meta/action cleared, renewal actions restored.

[[2026-05-21]] Thu 22:10
Fixed: customer portal undo confirmation now runs AJAX inside shared confirm onConfirm, keeps spinner/error in modal, hides page background from the confirm/prompt accessibility tree so modal buttons are targeted correctly, and clears stale cancellation timestamp on undo. Verified in browser on #643: clicked Undo Scheduled Cancellation -> Undo Cancellation; page returned Active, Cancel Subscription visible, no Pending Cancellation/Undo button. WP meta only _next_payment_date remains; pending renewal invoice/process actions restored and cancel action absent. Builds passed for arraysubs and arraysubspro.
