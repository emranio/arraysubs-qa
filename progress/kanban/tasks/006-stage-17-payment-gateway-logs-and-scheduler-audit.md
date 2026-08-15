---
id: 6
title: 'stage-17: Payment gateway logs and scheduler audit'
status: closed
priority: critical
created: 2026-08-15T09:45:27.828772348+02:00
updated: 2026-08-15T10:30:34.590406113+02:00
started: 2026-08-15T10:20:34.408034094+02:00
completed: 2026-08-15T10:20:34.408034094+02:00
tags:
    - stage-17
    - audits
    - gateway-health
    - scheduler
    - qa
class: standard
---

References: ../stages/17-audits-and-logs/03-scheduled-job-logs.md, ../stages/17-audits-and-logs/04-gateway-health-dashboard.md

Verify Stripe/Paddle gateway health, processed webhook records, scheduled-action integrity, zero new product runtime PHP failures, and capture final cross-gateway evidence before fixture cleanup.

[[2026-08-15]] Sat 10:20
Final 2026-08-15 audit: both gateways stayed connected in test mode; expected purchase and Paddle cancellation webhooks processed; fixture schedules were exact; pending Action Scheduler count returned to baseline 335; zero due ArraySubs actions and zero recent ArraySubs failures. All 31 replacement actions for 11 preexisting scheduled Stripe subscriptions match backup semantics exactly with no missing, overdue, or duplicate rows. Open high finding 2 covers phantom connected Paddle authorization on an abandoned unpaid checkout. Full report: qa/artifacts/payment-migration-regression-20260815/final/report.md

[[2026-08-15]] Sat 10:25
Post-teardown fresh browser smoke: Subscriptions rendered exact 403 total / 19 active / 14 pending / 355 cancelled / 15 expired. Gateway Logs rendered the final Stripe SCA and Paddle cancellation event chains; page errors were empty and the cleared console contained only JQMIGRATE. Screenshot: qa/artifacts/payment-migration-regression-20260815/final/post-teardown-gateway-logs.png

[[2026-08-15]] Sat 10:30
Legacy Paddle compatibility: sole active Paddle subscription 7809 matches the pre-migration backup on every post field and every business/gateway meta value. Its only two changed values are scheduler pointer IDs; both current actions match the backup exactly and share semantic hash a4c9721986f2da25b48551d5212c32af65745737b7087db0d678a8e7c5a2234c, with no missing/due/duplicate pending action. Isolated comparison DB dropped and verified absent.
