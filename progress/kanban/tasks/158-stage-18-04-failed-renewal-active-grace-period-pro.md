---
id: 158
title: 'stage-18: 04 Failed Renewal — Active Grace Period (Pro)'
status: closed
priority: high
created: 2026-05-19T22:56:22.073918535+02:00
updated: 2026-05-24T21:05:51.840507415+02:00
started: 2026-05-23T08:06:53.471513353+02:00
completed: 2026-05-23T17:05:28.526582494+02:00
tags:
    - qa
    - stage-18
class: standard
---

Source: stages/18-renewal-followup/04-failed-renewal-grace-active.md

[[2026-05-23]] Sat 17:05
QA complete with defects. Seeded missing member-decline fixture because documented user was absent (#135): user #43, subscription #1467, parent order #1465, Stripe decline PM fixture pm_card_chargeDeclined with display last4 0341. Settings verified via arraysubs_settings: active grace 3 days, on-hold grace 7 days, payment_failed email enabled. Baseline browser: #1467 Active, Standard Weekly, Stripe card 0341, next 2026-05-30 14:47:30 UTC, completed_payments 1. Time-traveled next to 2026-05-23 13:48:22 UTC, ran generate action #1278 -> renewal order #1471 pending, then process action #1287 -> Stripe card_declined / Your card was declined. Expected Active during 3-day grace failed: subscription immediately moved ON HOLD, Subscription On Hold email audit fired, Retry Payment appeared; logged #139. Failed order #1471 and Renewal Payment Failed email audit present, category card_declined set. Ran check overdue action #1279; status remained on-hold. Browser pay link was accessible, but agent-browser could not type into Stripe iframe despite clicking secure payment frame; used plugin customer retry path with working Stripe PM to verify recovery. Recovery succeeded: order #1471 processing, sub Active, pending cleared, completed_payments 2, next 2026-05-30 13:48:22 UTC, Renewal Payment Successful email audit present. Failure reason/category remained after recovery; logged #140. Scheduled-Job Logs/Action Scheduler marked failed process action #1287 complete/success instead of failed; logged #141. Gateway Logs showed payment_intent.payment_failed for decline, but no recovery success event; appended #130. Internal _payment_tokens notice repeated; covered by #138. Cleanup for downstream Task 18.05: reset #1467 to Active, Stripe decline PM pm_card_chargeDeclined/display last4 0341, cleared local failure/retry/on_hold meta, next remains 2026-05-30 13:48:22 UTC, completed_payments 2.

[[2026-05-24]] Sun 13:42
Follow-up fix for issue #139 verified. Code now skips failed renewal orders in OrderIntegration::handleOrderFailure, preserving active grace. Retest on #1467: renewal order #2869 failed with card_declined, subscription remained Active, _on_hold_date empty, retry scheduled 2026-05-25 11:37:49, and manual overdue check did not move it on-hold. Browser/admin proof screenshot: qa/artifacts/issue-139-active-grace-subscription-1467.png. Current fixture #1467 is intentionally left in Task 18.04 post-failure state for Task 18.05: Active with pending failed renewal order #2869 and decline card 0341.

[[2026-05-24]] Sun 21:02
Issue #140 fixed and closed. Recovery/payment-method cleanup now clears all failure markers, including _last_payment_failure_category. Verified on #1467/#2869 recovery: Active, order processing, pending renewal cleared, completed payments 2, next payment advanced, failure/retry meta empty. Screenshot: qa/artifacts/issue-140/subscription-1467-recovered-meta-cleared.png.

[[2026-05-24]] Sun 21:05
Issue #141 verified and closed. Scheduled-Job Logs now records completed Action Scheduler renewal actions with business failure meta as Failed rows. Proof row: Process Renewal #1467 failed with QA #141 synthetic decline reason; screenshot qa/artifacts/issue-141/process-renewal-failed-log.png.
