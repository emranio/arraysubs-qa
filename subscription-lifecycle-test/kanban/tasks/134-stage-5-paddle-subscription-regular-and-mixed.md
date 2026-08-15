---
id: 134
title: 'stage-5: Paddle subscription, regular, and mixed checkout QA'
status: done
priority: critical
created: 2026-08-15T08:57:02.165969677+02:00
updated: 2026-08-15T10:07:13.500729856+02:00
started: 2026-08-15T09:54:14.289954653+02:00
completed: 2026-08-15T09:54:14.289954653+02:00
tags:
    - checkout
    - paddle
    - payments
    - regression
class: standard
---

Real staging browser checkout matrix after automatic-payment migration: Paddle sandbox subscription-only, regular-product-only, mixed cart, overlay/cancel negative checks, pending-to-webhook-paid behavior, order/subscription/remote binding, console/network, webhook and Mailpit evidence. Use disposable identities and record exact IDs.

[[2026-08-15]] Sat 09:05
Baseline complete. Paddle sandbox credentials and SLT Paddle Daily remote binding are configured (secrets redacted); webhook baseline 106 Paddle events (max row 1115), Mailpit marker captured. Disposable customer IDs 459-462 created. Checkout is temporarily waiting for the root-controlled window that will exclude test products from the sitewide member-only purchase rule and disable renewal sync; no global setting was changed by this task.

[[2026-08-15]] Sat 09:54
PASS. Real Paddle sandbox browser matrix completed on staging with isolated customers: regular user #460 -> paid/processing order #27275 ($15, product #447), remote transaction completed, zero subscriptions; subscription user #459 -> paid/completed order #27307 and exactly one active subscription #27309 ($11/day), durable Paddle binding sub_01m025c6cjbrmq4n30g6c8g2en, one pending invoice/renewal pair, portal and emails verified; mixed user #461 -> paid/processing order #27339 ($26, products #12112 + #447) and exactly one active subscription #27341, binding sub_01m025q44zfj9v7nzh7c0jeqc3, one pending invoice/renewal pair, portal and emails verified; return user #462 -> order #27381 and sub #27383 remain pending/unpaid, remote transaction draft, no remote subscription/action/email, checkout restored with cart retained. Paddle webhook delta 106 -> 142 (+36): 2 subscription.created, 4 transaction.created, 21 transaction.updated, 3 each transaction.ready/paid/completed. CDP `errors` briefly recorded a gateway-agnostic browser ViewTransition AbortError with null URL/no stack; it never appeared in `console`, reproduced on Stripe and BACS selection without impact, and is not attributable to ArraySubs/Paddle, so no issue filed. Root was notified to restore its shared temporary settings. Retain fixtures for audit; cancel remote sandbox subscriptions for local #27309 and #27341 before cleanup. Full evidence: qa/artifacts/payment-migration-regression-20260815/paddle/report.md and screenshots/.

Post-audit cleanup PASS. Using a fresh isolated admin browser session, local Paddle subscriptions #27309 and #27341 were each cancelled through the real UI with Cancel Immediately selected. Both REST requests returned 200; both local posts are arraysubs-cancelled with gateway status cancelled and immediate cancellation metadata. Secret-safe Paddle API re-fetch confirms exact remote IDs sub_01m025c6cjbrmq4n30g6c8g2en and sub_01m025q44zfj9v7nzh7c0jeqc3 are canceled, with no next bill or scheduled change. The final pending action pair for each fixture is canceled and zero pending target actions remain. Webhooks moved 142/max1205 to 146/max1215 with exact updated+canceled pairs #1212-1215. Four expected cancellation emails arrived (customer/admin for each). The 407 non-target subscription-status records retained identical SHA-256 801bc12a2e83b671dd15c8eb0a7b91c66545f10f2d69477c6e0faf70f4d87775. No local deletion performed. Cleanup evidence/screenshots appended to qa/artifacts/payment-migration-regression-20260815/paddle/report.md. Browser session closed.
