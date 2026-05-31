---
id: 164
title: 'stage-18: 10 Skip and Pause Over Renewal Cycles'
status: closed
priority: high
created: 2026-05-19T22:56:23.117865699+02:00
updated: 2026-05-24T21:58:55.487048708+02:00
started: 2026-05-23T08:06:53.476470943+02:00
completed: 2026-05-23T18:18:06.207133663+02:00
tags:
    - qa
    - stage-18
class: standard
---

Source: stages/18-renewal-followup/10-skip-and-pause-over-renewal-cycles.md

[[2026-05-23]] Sat 17:59
Starting QA on existing member-stripe fixture: user #42, subscription #1436, Standard Weekly product #200, Stripe gateway, Active, next_payment=2026-05-30 13:38:38 UTC, completed_payments=2. Precheck skip/pause enabled and customer allowed; skip cutoff was 0, set to required stage prereq value 2.

[[2026-05-23]] Sat 18:17
QA complete with issues #147 and #148. Fixture: member-stripe user #42, Standard Weekly #200, subscription #1436, Stripe Visa 4242. Precheck Active, next=2026-05-30 13:38:38 UTC, completed_payments=2; skip/pause settings enabled, set skip_renewal.cutoff_days to stage prereq 2. Customer browser showed Skip Next Renewal and Pause Subscription controls. Browser skip flow selected 2 cycles but failed with generic error; server-side customer REST dispatch succeeded and set skip original=2026-05-30 13:38:38 UTC, shifted=2026-06-13 13:38:38 UTC, remaining=2 (#147). Customer page verified Skipping 2 cycle(s), next 13 June. Original-date time travel: _next_payment_date set one hour ago, generated invoice action #1367; no pending order/no new order, process skipped cycle #1368 ran and remaining became 1. Shifted-date attempt: generate invoice #1369 at shifted due date did not create invoice; only scheduled process skipped cycle #1370, requiring #1370 plus a second generate action #1371 before invoice #1643 was created (#148). Process Renewal #1373 charged Stripe; order #1643 processing 9.99, completed_payments=3, #1436 remained Active, next=2026-05-30 15:07:58 UTC before pause. Browser pause flow entered 14 days but failed with generic error; server-side customer REST dispatch succeeded (#147), moving #1436 On Hold, pause_start=2026-05-23 16:11:58 UTC, pause_end=2026-06-06 16:11:58 UTC, next shifted to 2026-06-13 15:07:58 UTC, resume action #1377 queued. During pause, time-traveled next_payment one hour ago and ran Generate Renewal Invoice #1378; no pending order/no invoice. For auto-resume simulation, set pause_start to 2026-05-09 15:13:06 UTC and pause_end to 2026-05-23 15:13:06 UTC, then ran resume action #1377. Final: Active, pause meta cleared, no pending resume action, next=2026-06-13 15:07:58 UTC (14-day shift from pre-pause next), completed_payments=3. Browser admin and customer portal verified Active, next 13 June, Stripe Visa 4242, no active skip, pause cooldown notice. Scheduled-Job Logs verified success rows for Resume Subscription, Process Skipped Cycle, Generate Renewal Invoice, Process Renewal. Activity Audits verified skip, pause, resume, invoice, and order rows; resume row has changes link with Status On Hold -> Active. Stripe remote pause sync not applicable: Stripe delegate reports no remote pause support for ArraySubs-managed subscriptions. Mail body/no-invoice email verification remains blocked by existing issue #137.

[[2026-05-24]] Sun 14:02
Fix verification 2026-05-24 for issue #147: updated customer portal skip/pause client calls to use shared requestPortalJson(). Live retest as member-stripe@example.com user #42 on subscription #1436: Skip Next Renewal selected 2 cycles, REST returned 200 and set skip_remaining=2, next=2026-06-14 11:53:32. Pause Subscription retested from clean active fixture for 14 days, REST returned 200 and moved #1436 on-hold with pause_end=2026-06-07 12:00:28. Screenshots saved under qa/artifacts/issue-147-*.png. Fixture restored to active baseline afterward: next=2026-05-31 12:01:22, skip=0, pause=0.

[[2026-05-24]] Sun 21:58
Issue #148 fixed and reverified. Skip 2 on #1436 now restores the shifted target after interim skipped-cycle processing, and the shifted-date generate creates the renewal invoice in the same pass. Proof order #3088 was created for 9.99 with skip meta cleared; screenshot qa/artifacts/issue-148/subscription-1436-shifted-skip-generated-order-3088.png. Fixture restored active/no skip/no pending afterward; order #3088 cancelled as QA cleanup.
