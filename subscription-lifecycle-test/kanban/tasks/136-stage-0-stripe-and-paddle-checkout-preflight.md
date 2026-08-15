---
id: 136
title: 'stage-0: Stripe and Paddle checkout preflight baseline'
status: done
priority: critical
created: 2026-08-15T08:57:55.729327153+02:00
updated: 2026-08-15T10:00:17.944932601+02:00
started: 2026-08-15T10:00:17.9449318+02:00
completed: 2026-08-15T10:00:17.9449318+02:00
tags:
    - preflight
    - payments
    - stripe
    - paddle
class: standard
---

Verify staging plugin/gateway readiness, checkout/cart settings, published subscription and regular products, browser/admin health, Action Scheduler, Mailpit and secret-safe order/subscription/webhook baselines before the checkout matrix. Do not change source or shared settings.

[[2026-08-15]] Sat 09:22
Preflight progress: secret-safe baseline captured. Shared checkout QA window is active under root authorization: Member Access rule rule_1784662676378_maa3te08s now excludes only product IDs 200, 197, 12112, 447; General Settings Renewal Sync is temporarily OFF while stored First Charge remains full. Reconstructed original settings/rule hashes match the captured baseline, proving no other semantic field changed. Fresh cache-busted guest product 200 shows Subscribe Now and runtime user 463 is purchasable. Do not restore until root confirms both gateway matrices complete.

[[2026-08-15]] Sat 09:31
Preflight report complete: qa/artifacts/payment-migration-regression-20260815/preflight/report.md. PASS baseline: core/pro active 1.8.12/1.1.3; Stripe test + Paddle sandbox connected; checkout/cart are Woo Blocks; mixed cart verified with subscription 200 + regular 447 at $34.99; Paddle fixture 12112 sandbox binding ready; AS has zero in-progress/stuck, zero due ArraySubs pending, zero ArraySubs failures in last 24h. Clean pre-concurrency cutoff: 692 orders, 403 subscriptions (Stripe active 13, Paddle active 1), 363 webhooks (Stripe 257/Paddle 106), Mailpit latest 4NGz5mAhfB83ELxbV6TVsp. Browser errors empty. Task intentionally remains in-progress with cape-oaken holding the claim until root requests exact browser restoration of the temporary QA window.

[[2026-08-15]] Sat 10:00
Restoration complete after both checkout matrices. Browser restored Member Access rule exclusions to [] only and re-enabled Sync Renewals to Next Billing Cycle; First Charge remains full. Final proof: arraysubs_settings SHA256 ef5e20f24ae03fcab4967dbe713bb7c1fb2fb5667a3d01600e4c38ccf166b3ae exact original; rule SHA256 48a238abb67869d4308fc3726dc5cd27237e1622b9ed6f9862057e0fac4526ac exact original. Stripe settings fb63cc191988edf4ba749d4f983e2004742823544b06101dc10f19cc7b7bfb87, Stripe extras 55647a87f8d3ce8b75cde717b4923e53f61bbcc06ae47e03f81cf3f4c93cf289, and Paddle settings bf12616dc7011f4fbe005e7b2f0e6f7af324a12ad8620a051554486d664a2a32 are unchanged. All checks true; browser errors empty. Restoration screenshots and proof appended to qa/artifacts/payment-migration-regression-20260815/preflight/report.md. Result PASS.
