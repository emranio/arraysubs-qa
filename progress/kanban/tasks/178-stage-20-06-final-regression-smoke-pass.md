---
id: 178
title: 'stage-20: 06 Final Regression Smoke Pass'
status: closed
priority: high
created: 2026-05-19T22:56:24.976078097+02:00
updated: 2026-05-25T00:28:28.607243547+02:00
started: 2026-05-23T08:06:53.495913347+02:00
completed: 2026-05-23T22:15:53.324165357+02:00
tags:
    - qa
    - stage-20
class: standard
---

Source: stages/20-edge-and-regression/06-final-regression-smoke.md

[[2026-05-23]] Sat 21:41
Plan: (A) browser verify setup wizard route. (B) create/verify Smoke Plan + Smoke Plan Plus; use WP-CLI for deterministic product metadata then browser/public product proof. (C) browser checkout smoke@example.com with Stripe test card, then admin verify subscription/order. (D) force renewal via WP-CLI/Action Scheduler + browser/admin proof. (E-G) browser cancel/reactivate/refund/plan switch where UI available; use WP-CLI only for setup/forced scheduler state. Email inbox proof remains blocked by existing issues #40/#137, so use mail interception/audit where possible.

[[2026-05-23]] Sat 22:15
Stage 20.06 final smoke verdict: FAIL gate, QA completed. A setup wizard route loaded (Easy Setup page + Launch Setup Wizard, no PHP UI error). B Smoke Plan #2569 (0/month) and Smoke Plan Plus #2571 (0/month) created/linked; public product page verified. C Stripe checkout as smoke@example.com succeeded: order #2573 completed, subscription #2591 active, next payment 2026-06-23 19:53:30/19:54:36 UTC-ish after renewal setup, card saved. D forced renewal succeeded: renewal order #2598 completed via Stripe, completed payments=2, next payment advanced, admin detail shows active/Stripe/card/renewal order. E customer cancellation modal failed twice with 'Failed to cancel subscription. Please try again.'; issue #122 updated. Direct same-browser REST scheduled cancellation, then admin/WP-CLI undo restored active state. F full gateway refund of renewal order #2598 failed: wc_create_refund returned 'An error occurred while attempting to create the refund using the payment gateway API.' Order stayed completed/refunded=0; new issue #167. G plan switch succeeded through customer UI: Change Plan -> Smoke Plan Plus -> proration order #2605 -> Pay for order; order completed; subscription now active on Smoke Plan Plus, recurring 0/month, completed payments=2, plan switch notes present. H admin detail verified #2591 active, Smoke Plan Plus, smoke@example.com, next payment 24 June 2026, Stripe connected, total paid 0; debug.log unchanged since 18:01 UTC before smoke window; no failed process-renewal actions for #2591. Pre-existing failed action #1039 arraysubs_cleanup_webhook_events remains covered by #163. H4 email inbox proof blocked by #40/#137. Overall release verdict FAIL due #122, #167, and mailbox blocker #40/#137.

[[2026-05-25]] Mon 00:28
Issue #167 fixed and retested after Stage 20.06 F failure. Renewal order #2598 full Stripe gateway refund now succeeds as refund #3133; order is refunded; Stripe charge ch_3TaLe2JG5OzSNVs20PFZn0IB shows amount_refunded=1000; subscription #2591 auto-cancelled with reason Full refund processed. Browser evidence: ../artifacts/issue-167/order-2598-refunded.png and ../artifacts/issue-167/subscription-2591-cancelled-after-refund.png.
