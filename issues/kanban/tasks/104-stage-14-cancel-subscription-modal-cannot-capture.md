---
id: 104
title: 'stage-14: Cancel Subscription modal cannot capture reason and details separately'
status: closed
priority: high
created: 2026-05-23T12:06:44.0310419+02:00
updated: 2026-05-24T16:31:12.884079685+02:00
started: 2026-05-24T16:16:51.59277627+02:00
completed: 2026-05-24T16:31:12.884078853+02:00
tags:
    - qa
    - stage-14
    - admin-subscriptions
    - cancellation
    - analytics
claimed_by: shell-quartz
claimed_at: 2026-05-24T16:31:12.884079594+02:00
class: standard
---

Stage 14 task 08 Sub-Y #508: Cancel Subscription modal exposed only one 'Reason (optional)' textarea. QA plan expects a reason bucket 'Too expensive' plus additional details 'QA test'. Entering both values saved UI Reason as 'Too expensive QA test' and meta _cancellation_reason_details as {"reason":"Too expensivenQA test"}; no separate Additional Details appears in Cancellation Details. This breaks structured analytics/reason reporting.

[[2026-05-24]] Sun 16:17
Fix pass started 2026-05-24 by shell-quartz. Verifying Stage 14 cancellation-button expectations, then tracing modal payload and cancellation meta fields.

[[2026-05-24]] Sun 16:31
Fixed admin Cancel Subscription modal: reason is now a selectable cancellation bucket and Additional Details is stored separately as reason_details.other_reason. REST detail payload now exposes cancellation_reasons; cancel endpoint sanitizes structured reason_details. Confirm button uses SpinnerButton loading state. Verified with temp subscription #2939 via Playwright modal: selected Too expensive, entered QA test, final card showed Reason: Too expensive and Additional Details: QA test. DB meta confirmed _cancellation_reason=too_expensive and _cancellation_reason_details={"other_reason":"QA test"}. Screenshots: qa/artifacts/issue-104-modal-fields.png and qa/artifacts/issue-104-cancel-details.png. php -l and npm run build passed. Temp subscriptions #2934/#2939 removed.
