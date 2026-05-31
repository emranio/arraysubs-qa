---
id: 139
title: 'stage-18: failed Stripe renewal bypasses active grace'
status: closed
priority: critical
created: 2026-05-23T16:50:33.797673986+02:00
updated: 2026-05-24T13:42:06.468957985+02:00
started: 2026-05-24T13:33:22.090840119+02:00
tags:
    - qa
    - stage-18
    - renewals
    - stripe
    - grace
class: standard
---

Stage: qa/stages/18-renewal-followup/04-failed-renewal-grace-active.md\n\nTask expects a failed automatic Stripe renewal to keep subscription Active during configured active grace period (renewals.grace_days_before_on_hold=3) when _next_payment_date is only 1 hour in the past.\n\nObserved in QA:\n- Subscription #1467, customer member-decline@example.com, Standard Weekly, Stripe decline fixture.\n- _next_payment_date changed to 2026-05-23 13:48:22 UTC.\n- arraysubs_generate_upcoming_renewals action #1278 created renewal order #1471.\n- arraysubs_process_renewal action #1287 attempted Stripe off-session charge with pm_card_chargeDeclined.\n- Stripe returned card_declined / Your card was declined.\n- Order #1471 moved to failed as expected.\n- Subscription #1467 immediately changed to arraysubs-on-hold.\n- _last_payment_failure_category=card_declined, retry #1 scheduled for 2026-05-24 14:49:51.\n\nExpected: subscription remains arraysubs-active until the overdue checker moves it on-hold after the 3-day active grace window. No on-hold transition should happen during the renewal payment attempt itself.\n\nImpact: active grace period is bypassed for failed automatic renewals; customer may lose access immediately and receive on-hold behavior earlier than configured.

[[2026-05-23]] Sat 16:51
Browser admin confirmed the same failure: #1467 shows ON HOLD, Retry Payment button, failed order #1471, card_declined message, Renewal Payment Failed email audit, Subscription On Hold email audit, and note: Status changed from Active to On Hold because payment did not complete. This also violates Task 04 expectation that no On-Hold email/note occurs during active grace.

[[2026-05-24]] Sun 13:42
Fixed. Root cause: OrderIntegration::handleOrderFailure treated failed renewal orders like initial checkout failures and forced linked subscriptions on-hold. Change: skip _is_renewal_order=yes in handleOrderFailure so RenewalProcessor records the decline and the overdue checker owns the later grace transition. Verification: reset #1467 to active with Stripe decline token, time-traveled _next_payment_date to 2026-05-24 10:37:39 UTC, created renewal order #2869, processed automatic renewal. Result: order #2869 status=failed, _last_payment_failure_category=card_declined, retry_next=2026-05-25 11:37:49, subscription #1467 stayed arraysubs-active with empty _on_hold_date. Ran arraysubs_check_overdue_renewals with due date only 1 hour past; #1467 stayed arraysubs-active. Alumnium admin check showed subscription status ACTIVE; Playwright screenshot saved at qa/artifacts/issue-139-active-grace-subscription-1467.png.
