---
id: 1
title: 'stage-0: Payment gateway preflight baseline'
status: closed
priority: critical
created: 2026-08-15T09:45:26.523193456+02:00
updated: 2026-08-15T10:01:10.948828132+02:00
tags:
    - stage-00
    - payments
    - preflight
    - qa
class: standard
---

References: ../stages/00-preflight/01-fresh-install-check.md, ../stages/00-preflight/03-cron-and-action-scheduler.md, ../stages/00-preflight/04-test-accounts-and-mail.md

Verify active plugin versions, Stripe test and Paddle sandbox readiness, checkout/cart health, Action Scheduler, Mailpit, debug log, and secret-safe baseline counts. Evidence: ../artifacts/payment-migration-regression-20260815/preflight/.

[[2026-08-15]] Sat 10:01
PASS. Baseline captured and temporary checkout window restored through authenticated browser. Final arraysubs_settings and Member Access rule SHA-256 fingerprints match the original values exactly; exclusions are [], renewal sync=true, first charge=full, and Stripe/Paddle option fingerprints are unchanged. Browser errors empty. Evidence: ../artifacts/payment-migration-regression-20260815/preflight/report.md.
