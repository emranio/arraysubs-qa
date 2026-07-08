---
id: 194
title: 'stage-21: 07 Renewal Execution & Date Advancement (Manual + Stripe)'
status: closed
priority: critical
created: 2026-07-08T02:50:39.645082+06:00
updated: 2026-07-08T01:03:26.879588185+02:00
started: 2026-07-08T00:50:28.131291538+02:00
completed: 2026-07-08T01:03:26.879587153+02:00
tags:
    - qa
    - stage-21
    - flexible-renewal-sync
estimate: 60m
depends_on:
    - 189
    - 190
    - 191
    - 192
claimed_by: reef-maple
claimed_at: 2026-07-08T01:03:26.879588085+02:00
class: standard
---

Source: stages/21-flexible-renewal-sync/07-renewal-execution-and-advancement.md

Time-travel the subscriptions from tasks 02-05 and run their renewals: invoice order for the FULL recurring amount (never the prorated value), renewal-invoice email + pay link for manual, off-session PaymentIntent auto-charge for Stripe, next payment re-anchors to the boundary (first synced renewal re-anchors from _renewal_sync_first_full_renewal_date by design), segment-3 skipped boundary produces no invoice/email/charge, and a second advancement stays boundary-to-boundary.

[[2026-07-08]] Wed 01:03
QA result: EXECUTED with issues #173, #176, and #177. Manual full #8682: time-traveled to 2026-07-06 00:00:00 UTC, ran actions #9080/#9081, renewal order #8818 pending for $30.00 with _is_renewal_order=yes, cycle=2, scheduled date 2026-07-06 00:00:00; after BACS order was marked processing, subscription stayed active, completed payments advanced 1->2, and next payment re-anchored to 2026-08-31 18:00:00 UTC. Dev Assist mail capture was inactive before this first run, so invoice body proof for #8818 is from ArraySubs subscription notes/order notes rather than QA Mail Log.

Manual prorate #8705: renewal order #8826 billed the full recurring amount $30.00, not the original $22.26 prorate; QA Mail Log captured Invoice for subscription #8705; after BACS processing, completed payments advanced to 2 and next payment became 2026-08-31 18:00:00 UTC. Manual next_cycle #8728: pre-run B1 silence passed, with zero linked renewal orders and only B2 actions #9098/#9099 pending; no mail-log entry for #8728 before B2. After simulated B2, renewal order #8834 billed $30.00, invoice email captured, and after payment #8728 advanced to 2026-09-30 18:00:00 UTC.

Stripe #8751: off-session renewal order #8842 completed automatically for $30.00; PaymentIntent pi_3Tqhx9JG5OzSNVs20n8KiXub and charge ch_3Tqhx9JG5OzSNVs20bBn84YT were stored, subscription stayed active, completed payments advanced to 2, and next payment became 2026-08-31 18:00:00 UTC. Failure #176: QA Mail Log also captured an unexpected customer renewal invoice email for #8751 before the automatic charge, contrary to task expectation. Issue #173 repeated: debug.log gained duplicate webhook-event DB errors for payment_intent.succeeded and charge.succeeded during #8842 processing; one extra debug line was caused by my WP-CLI inspection of an internal Woo meta key and is not product runtime evidence.

Second advancement #8682 failed: order #8854 cycle=3 billed $30.00 and payment advanced completed payments to 3, but _next_payment_date reset backward to 2026-07-31 18:00:00 UTC instead of the expected subsequent boundary 2026-09-30 18:00:00 UTC; filed issue #177. Browser/admin proof: qa/artifacts/stage-21-task-194-qa-mail-log.png and qa/artifacts/stage-21-task-194-subscription-8751-after-renewal.png.
