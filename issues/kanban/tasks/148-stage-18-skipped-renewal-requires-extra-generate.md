---
id: 148
title: stage-18 skipped renewal requires extra generate at shifted date
status: closed
priority: high
created: 2026-05-23T18:15:09.993119201+02:00
updated: 2026-05-24T21:58:55.686520069+02:00
started: 2026-05-24T21:50:52.146339223+02:00
completed: 2026-05-24T21:58:55.686519037+02:00
tags:
    - stage-18
    - qa
    - bug
    - skip-pause
    - renewals
claimed_by: shell-quartz
claimed_at: 2026-05-24T21:58:55.686519959+02:00
class: standard
---

Stage: qa/stages/18-renewal-followup/10-skip-and-pause-over-renewal-cycles.md\n\nFixture: member-stripe@example.com, subscription #1436. Skip 2 cycles was applied successfully via customer REST dispatch: original next payment 2026-05-30 13:38:38 UTC, shifted next payment 2026-06-13 13:38:38 UTC, _skip_cycles_remaining=2.\n\nSub-task 10.2: Time-traveled _next_payment_date to 1 hour ago and ran per-sub arraysubs_generate_renewal_invoice action #1367. Correctly no invoice was created and no pending order was set. It scheduled arraysubs_process_skipped_cycle action #1368; after running it, remaining skip cycles became 1.\n\nSub-task 10.3: Restored the shifted date then time-traveled to 1 hour ago and ran arraysubs_generate_renewal_invoice action #1369. Expected by QA plan: renewal invoice created and skip deactivated. Actual: no invoice was created, _pending_renewal_order_id stayed empty, _skip_cycles_remaining stayed 1, and another arraysubs_process_skipped_cycle action #1370 was scheduled. Only after manually running #1370 and then running another generate invoice action #1371 did the renewal invoice #1643 get created and Stripe paid via process action #1373.\n\nExpected: when the shifted renewal date arrives for a 2-cycle skip, the skipped cycles should already be consumed, or the same run should consume the final skipped cycle and generate the due invoice without requiring a second generate attempt.\n\nImpact: a skipped subscription can miss billing at its shifted renewal date until another scheduler pass manually/incidentally runs after the skip counter is cleared.

[[2026-05-24]] Sun 21:52
Plan: fix skip lifecycle in core SkipRenewal. Use the stored shifted renewal target from skip history/original date. Interim skipped-cycle processing should restore/reschedule _next_payment_date back to the shifted target after decrementing. When the shifted renewal run is due, clear remaining skip meta and allow invoice generation in the same arraysubs_generate_renewal_invoice pass; for the QA path where one skipped cycle was already processed, consume the final remaining cycle and allow invoice. Verify on #1436 by replaying skip=2 original-date block then shifted-date generate.

[[2026-05-24]] Sun 21:58
Fixed and verified live. Verification on #1436: skip(2) set remaining=2 and shifted target=2026-06-14 19:55:38 UTC. Original-date generate produced no order; manual skipped-cycle processing reduced remaining 2->1 and restored _next_payment_date back to shifted target. Shifted-date generate then cleared skip meta in the same pass and created renewal order #3088 total 9.99 without requiring a second generate run; pending skipped-cycle actions=0. Alumnium verified admin detail Active, no cycles currently skipped, order #3088 pending 9.99 renewal. Screenshot: qa/artifacts/issue-148/subscription-1436-shifted-skip-generated-order-3088.png. Cleanup: cancelled verification order #3088, cleared pending order/skip/pause meta, restored #1436 Active with next_payment=2026-05-31 19:58:30 UTC.
