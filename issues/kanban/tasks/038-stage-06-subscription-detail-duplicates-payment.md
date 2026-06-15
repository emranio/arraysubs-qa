---
id: 38
title: 'stage-06: Subscription detail duplicates payment received event'
status: closed
priority: medium
created: 2026-05-20T14:00:29.228368826+02:00
updated: 2026-05-22T05:10:50.705003443+02:00
started: 2026-05-22T05:05:52.514651433+02:00
completed: 2026-05-22T05:10:50.705002121+02:00
tags:
    - qa
    - stage-06
    - subscription-detail
    - payment-timeline
    - notes
claimed_by: mold-glade
claimed_at: 2026-05-22T05:10:50.705003363+02:00
class: standard
---

Stage 06 Task 03 on active subscription #618 showed duplicate Payment Received entries in both Payment Timeline and Subscription Notes: 'Payment successful - Order #610 (9.99)' appears twice at 20 May 2026 5:55 PM (UTC+6). Expected one system event/note for the initial payment. Subscription otherwise shows active status, order #610 completed, and completed_payments=1.

[[2026-05-22]] Fri 05:08
Plan: trace order-paid note path; add idempotency guard so same subscription/order/event cannot create duplicate payment success note; clean duplicate QA fixture note for subscription #618; verify DB + admin detail timeline/notes show one Order #610 payment-success entry.

[[2026-05-22]] Fri 05:10
Fixed: AutoNotes now skips duplicate payment-success notes for same subscription/order/event. Deleted duplicate QA fixture note #628 for subscription #618. Verified with WP-CLI before=1 after direct onPaymentComplete=1; agent-browser confirms one Payment Timeline event + one Subscription Notes entry for Order #610.
