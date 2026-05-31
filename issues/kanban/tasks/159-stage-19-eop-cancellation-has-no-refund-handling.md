---
id: 159
title: stage-19 EOP cancellation has no refund handling or no-refund note
status: closed
priority: high
created: 2026-05-23T19:32:51.861861206+02:00
updated: 2026-05-24T23:07:28.096346505+02:00
started: 2026-05-24T23:02:32.634826479+02:00
completed: 2026-05-24T23:07:21.431403645+02:00
tags:
    - qa
    - stage-19
    - refunds
    - eop
    - cancellation
class: standard
---

Task: stages/19-refunds/03-refund-at-end-of-period.md

Fixture: cust3@example.com, Basic Monthly subscription #1719, Stripe order #1716, amount 9.99/month. Refund settings were set to cancellation_behavior=end_of_period, auto_gateway_refund=true, allow_prorated_refunds=true, minimum_amount=0.

Flow: scheduled cancellation from admin with reason 'QA EOP refund test'; subscription remained Active with pending cancellation until scheduled date. Then _next_payment_date and _cancellation_scheduled_date were moved to 2026-05-23 16:31:55 UTC and Action Scheduler action #1404 arraysubs_check_overdue_renewals was run successfully.

Expected: when EOP cancellation fires, documented refund handling occurs. If no refund is applicable, subscription notes should document why (for example no unused portion remaining at EOP). Order/pending renewal should not be charged.

Observed: subscription #1719 became Cancelled and order #1716 remained completed with refunded=0 and no refund rows. Subscription notes only show scheduled cancellation and status changed Active -> Cancelled; no refund attempt, no gateway refund ID, and no 'no unused portion remaining' explanation. Code path RecurringBilling\Services\Hooks::processWaitingCancellation() only flips status, clears waiting meta, unschedules actions, and fires arraysubs_data_cancelled; it does not consult refund settings or RefundProcessor.

Impact: Refund at End of Period setting does not produce visible refund handling/audit at EOP, making the documented refund policy unverifiable.

[[2026-05-24]] Sun 23:07
Fix: scheduling EOP cancellation now stores the refund policy at scheduling time. When the EOP cancellation processor fires, it handles refund policy explicitly: for end_of_period it processes a prorated refund when unused time remains, or writes a private audit note explaining why no refund was issued; for none it writes a no-automatic-refund note. Verification: php -l passed; FPM reloaded. Created throwaway active subscription #3111 with EOP scheduled in the past and zero unused time, invoked waiting-cancellation processor, and verified status became Cancelled, waiting meta cleared, and note says: 'No prorated refund was issued for this end-of-period cancellation because no unused billing time remained.' Browser screenshot: qa/artifacts/issue-159/subscription-3111-eop-no-refund-note.png.
