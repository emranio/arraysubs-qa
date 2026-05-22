---
id: 25
title: 'stage-04: Different billing cycle error text does not match QA spec'
status: closed
priority: high
created: 2026-05-20T10:56:31.046783263+02:00
updated: 2026-05-22T01:47:37.398667645+02:00
started: 2026-05-22T01:40:53.882598049+02:00
completed: 2026-05-22T01:47:37.398666462+02:00
tags:
    - qa
    - stage-04
    - cart-validation
    - billing-cycles
claimed_by: mold-glade
claimed_at: 2026-05-22T01:47:37.398667545+02:00
class: standard
---

Stage 04 Task 04 expected exact error: These subscription plans use different billing cycles. Observed with Allow different billing cycles disabled: 'These subscription plans use different billing cycles. Keep only plans with the same renewal schedule, or enable mixed billing cycles in settings.' Blocking behavior works in Standard Weekly -> Basic Monthly and reverse order, but exact error contract fails due appended guidance.

[[2026-05-22]] Fri 01:41
Verified Stage 04 Task 04 and code. Failure is message contract only: blocking logic already rejects weekly+monthly and reverse order. Plan: replace different-billing-cycle validation strings in add-to-cart validation and cart/block validation with exact QA string; syntax-check touched PHP; set allow_different_cycles=0 and browser-verify weekly+monthly, reverse order, same weekly allowed, daily+weekly blocked; restore allow_different_cycles=1 and verify mixed weekly+monthly allowed.

[[2026-05-22]] Fri 01:47
QA fix complete. Replaced different-billing-cycle messages in add-to-cart validation and cart/block validation with exact string: These subscription plans use different billing cycles. Syntax checks passed. Browser verified with allow_different_cycles=0: Standard Weekly -> Basic Monthly blocked with exact error and only Standard Weekly remains; reverse order blocked with exact error and only Basic Monthly remains; Standard Weekly + Trial Weekly allowed; Coffee Plan Daily -> Standard Weekly blocked with exact error and only Coffee Plan Daily remains. Restored allow_different_cycles=1 and verified Standard Weekly + Basic Monthly both allowed, total 9.98. debug.log remains 0 bytes.
