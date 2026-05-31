---
id: 60
title: 'stage-08: Customer cancel modal uses stale reason list and enabled Continue'
status: closed
priority: high
created: 2026-05-20T15:10:54.211628133+02:00
updated: 2026-05-22T03:45:25.189134249+02:00
started: 2026-05-22T03:40:15.173385516+02:00
completed: 2026-05-22T03:45:25.189133127+02:00
tags:
    - qa
    - stage-08
    - retention
    - customer-portal
claimed_by: mold-glade
claimed_at: 2026-05-22T03:45:25.189134139+02:00
class: standard
---

Task 01 after configuring require_reason=true and eight reasons in admin/settings: customer cancel modal on #643 still lists only six reasons (Too expensive, Not using it enough, Found a better alternative, Missing features I need, Technical issues, Other). It omits temporary_pause / Just need a temporary break and custom shipping_issues / Shipping or delivery problems. Continue button is visible/enabled before a reason is selected, though task expects disabled until selection. Selecting Other does reveal textarea and accepts typed text.

[[2026-05-22]] Fri 03:45
Fix: cancel modal now disables Continue while a required reason is missing, re-enables it on reason selection, and the hidden template renders Continue disabled when require_reason is true. Built arraysubs customer-portal asset. Re-established Stage 08 Task 01 fixture with eight reasons: defaults plus shipping_issues before other. Verification: WP-CLI arraysubs_get_cancellation_reasons returns temporary_pause and shipping_issues; admin Retention Flow full reload shows Reason 6 temporary_pause, Reason 7 shipping_issues, Reason 8 other; customer #643 cancel modal lists all eight labels and Continue is disabled on placeholder; selecting Other reveals textarea, accepts text, and enables Continue; Keep Subscription closes modal; #643 remains Active with no waiting cancellation.
