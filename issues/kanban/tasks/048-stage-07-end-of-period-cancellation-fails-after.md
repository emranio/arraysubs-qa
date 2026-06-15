---
id: 48
title: 'stage-07: End-of-period cancellation fails after valid reason'
status: closed
priority: critical
created: 2026-05-20T14:46:16.043769371+02:00
updated: 2026-05-21T21:42:56.49719001+02:00
started: 2026-05-21T21:38:27.176724084+02:00
completed: 2026-05-21T21:42:56.497188857+02:00
tags:
    - qa
    - stage-07
    - customer-portal
    - cancellation
claimed_by: mold-glade
claimed_at: 2026-05-21T21:42:56.497189909+02:00
class: standard
---

Stage 07 Task 04. Set cancellation.cancel_immediately=false via WP-CLI and opened cust1 active subscription #643. Modal warning correctly changed to end-of-period wording: subscription remains active until end of current billing period. Task-required reason "Just need a temporary break" is not present in configured reason options, so selected nearest available "Not using it enough". Clicking Continue shows "Failed to cancel subscription. Please try again". Subscription remains Active with no pending-cancellation metadata, no undo button, no scheduled cancellation state. Expected: success notice "Subscription scheduled for cancellation at the end of your current billing period.", Active status with pending-cancellation indicator, Cancel hidden, Undo Cancellation visible.

[[2026-05-21]] Thu 21:39
Plan: set Stage 07 Task 04 settings (cancel immediately off, require reason on, retention offers off); verify #643 has future next payment and temporary_pause reason appears; retest customer portal EOP cancellation in browser; if backend/UI still fails, patch the canonical scheduled-cancellation response/state path; verify Active + pending cancellation meta/action state, then restore fixture for later issues.

[[2026-05-21]] Thu 21:42
Fix/verification: no extra code change needed beyond shared customer cancellation fix from issue #47 (REST success copy now exact and frontend displays REST message). Verified Stage 07 Task 04 in browser as cust1 on #643 with cancel_immediately=false, require_reason=true, retention_offers_enabled=false. Modal showed EOP wording and included Just need a temporary break. Continue completed, page showed Active + Pending Cancellation, Cancels On 2026-06-20 12:31:06, Undo Scheduled Cancellation visible, Cancel hidden, renewal/invoice actions empty, cancel action scheduled. Used temporary agent-browser XHR repair for known tool XMLHttpRequest Illegal invocation.
