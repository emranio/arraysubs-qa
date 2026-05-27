---
id: 157
title: 'stage-18: 03 Successful Automatic Renewal (Pro)'
status: closed
priority: high
created: 2026-05-19T22:56:21.959368692+02:00
updated: 2026-05-24T20:52:25.848463431+02:00
started: 2026-05-23T08:06:53.46928377+02:00
completed: 2026-05-23T16:45:40.445623303+02:00
tags:
    - qa
    - stage-18
class: standard
---

Source: stages/18-renewal-followup/03-successful-automatic-renewal-pro.md

[[2026-05-23]] Sat 16:45
QA complete with issues logged. Seeded missing documented fixture because member-stripe@example.com was absent (#135): created customer user #42, Stripe test customer cus_UZPVTxnQ61WDKN, PM/token #7 ending 4242, active Standard Weekly subscription #1436, parent order #1434. Baseline browser: Active, Stripe/Stripe Visa 4242, next 2026-05-30 14:37:44 UTC, completed_payments 1, no pending. Time-traveled _next_payment_date to 2026-05-23 13:38:38 UTC, ran arraysubs_generate_upcoming_renewals action #1261, creating pending renewal order #1440 total 19.99. Scheduled and ran arraysubs_process_renewal action #1273 for [1436]; Stripe off-session PaymentIntent pi_3TaGiJJG5OzSNVs21iChByOw succeeded, order #1440 processing, subscription remained Active, pending cleared, completed_payments 2, next_payment_date 2026-05-30 13:38:38 UTC, failure meta empty. Browser admin verified Visa 4242, last tx pi_3TaGiJJG5OzSNVs21iChByOw, order #1440 renewal row, Renewal Invoice and Renewal Payment Successful email audit entries, Process Renewal success timeline. Customer portal login member-stripe@example.com verified subscription #1436 Active with next payment 30 May 2026 and renewal order #1440 Processing. Action Scheduler verified #1273 complete and reminder #1277 pending for [1436,3]. Mail body verification blocked by #137. Gateway Logs expected recent payment_intent.succeeded/charge.succeeded but remained stale; appended to #130. Renewal order creation emitted WooCommerce internal _payment_tokens notices; logged #138.

[[2026-05-24]] Sun 20:52
Issue #138 fixed and closed. Renewal orders now attach saved payment tokens through WooCommerce order token API instead of writing _payment_tokens directly. Verified on member-stripe subscription #1436/order #3019: token ID 7 attached via get_payment_tokens(), no internal meta, no doing_it_wrong output. Screenshot: qa/artifacts/issue-138/subscription-1436-token-renewal-order.png.
