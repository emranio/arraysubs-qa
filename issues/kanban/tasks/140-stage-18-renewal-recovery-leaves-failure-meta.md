---
id: 140
title: 'stage-18: renewal recovery leaves failure meta uncleared'
status: closed
priority: high
created: 2026-05-23T17:00:27.79459107+02:00
updated: 2026-05-24T21:02:01.49217621+02:00
started: 2026-05-24T20:52:36.820872368+02:00
completed: 2026-05-24T21:02:01.492174948+02:00
tags:
    - qa
    - stage-18
    - renewals
    - stripe
    - recovery
claimed_by: shell-quartz
claimed_at: 2026-05-24T21:02:01.4921761+02:00
class: standard
---

Stage: qa/stages/18-renewal-followup/04-failed-renewal-grace-active.md\n\nAfter failed Stripe renewal #1471 on subscription #1467, manual retry/recovery succeeded with working Stripe PM. Order #1471 moved to processing, subscription returned Active, pending renewal cleared, completed_payments advanced 1 -> 2, next_payment_date advanced to 2026-05-30 13:48:22 UTC.\n\nObserved remaining failure meta after successful recovery:\n- _last_payment_failure is empty\n- _last_payment_failure_reason still = Your card was declined.\n- _last_payment_failure_category still = card_declined\n\nExpected: all payment failure markers are cleared after successful recovery, especially _last_payment_failure_category per Task 18.04 pass criteria.\n\nImpact: admin/customer UI, emails, audits, retry logic, or later QA stages can incorrectly treat a recovered subscription as having a current failure category.

[[2026-05-24]] Sun 20:52
Claimed. Inspecting failure meta reset helper and successful renewal recovery paths.

[[2026-05-24]] Sun 20:54
Implemented: Stripe payment-method recovery and other state-cleanup paths now use arraysubs_reset_payment_retry_attempts() or delete all failure markers including _last_payment_failure_category fallback.

[[2026-05-24]] Sun 21:02
Fixed and verified. Code paths that clear failure state now clear all markers through arraysubs_reset_payment_retry_attempts(), including _last_payment_failure_reason and _last_payment_failure_category. Verification: subscription #1467 before cleanup had failure=2026-05-24 11:37:48, reason='Your card was declined.', category=card_declined, retry attempts=1. Recovery verification completed pending failed order #2869: order -> processing, subscription stayed Active, completed_payments 1 -> 2, next_payment_date 2026-05-31 10:37:39, _pending_renewal_order_id=0, all failure/retry meta empty. Browser screenshot qa/artifacts/issue-140/subscription-1467-recovered-meta-cleared.png.
