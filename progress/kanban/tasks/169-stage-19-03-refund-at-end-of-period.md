---
id: 169
title: 'stage-19: 03 Refund at End of Period'
status: closed
priority: medium
created: 2026-05-19T22:56:23.779470381+02:00
updated: 2026-05-24T23:07:13.595362184+02:00
started: 2026-05-23T08:06:53.484417418+02:00
completed: 2026-05-23T19:36:16.076571113+02:00
tags:
    - qa
    - stage-19
class: standard
---

Source: stages/19-refunds/03-refund-at-end-of-period.md

[[2026-05-23]] Sat 19:36
QA complete with failures logged. Fixture: cust3@example.com user #50, Basic Monthly #197 subscription #1719, Stripe order #1716, charge ch_3TaJH1JG5OzSNVs21oYOFuh9, amount 9.99/month, last=2026-05-08 17:23:46 UTC, next=2026-06-07 17:23:46 UTC. Set refunds to end_of_period via REST because settings UI selection remains covered by #154/#155; browser verified Refund at End of Period, auto gateway on, prorated on, min 0. Admin UI cancel modal worked: selected Cancel at End of Period, reason 'QA EOP refund test'. Browser verified Active + Pending Cancellation, scheduled date 7 Jun 2026, Undo Scheduled Cancellation, order #1716 refunded '-' and no immediate refund; DB confirmed order refunded=0/refunds=0. Customer portal before EOP verified Active; Pending Cancellation, cancels on 7 Jun, Undo Scheduled Cancellation on detail. Forced _next_payment_date and _cancellation_scheduled_date to 2026-05-23 16:31:55 UTC, ran Action Scheduler #1404 arraysubs_check_overdue_renewals successfully. DB/browser/customer portal verified final Cancelled, order #1716 still refund=0, no renewal charge. No refund/no-refund explanation was logged despite EOP refund policy; issue #159. Pending renewal invoice/process actions canceled, but reminder [1719,3] stayed pending; appended to issue #101 and manually canceled it. Fresh admin detail still showed Skip & Pause > Vacation Mode 'Subscription is active' on cancelled record; appended to #103. Reset refund settings to immediate/auto/prorated/min 0. Mailbox content remains blocked by #137.

[[2026-05-24]] Sun 23:07
Follow-up issue #159 fixed EOP refund audit handling. Throwaway subscription #3111 processed scheduled EOP cancellation to Cancelled and now records explicit no-refund reason when no unused billing time remains. Screenshot: qa/artifacts/issue-159/subscription-3111-eop-no-refund-note.png.
