---
id: 159
title: Offboarding dates and filters show UTC instead of UTC+6
status: closed
priority: medium
created: 2026-11-05T01:52:41.039045+06:00
updated: 2026-11-05T01:58:52.498727+06:00
started: 2026-11-05T01:58:52.473287+06:00
completed: 2026-11-05T01:58:52.473287+06:00
class: standard
---

Active QA task #1, scheduled 2026-11-05 Asia/Dhaka; plan qa/offboarding-timezone-2026-11-05/plan.md. Subscription/order/customer IDs N/A. Context: portal administrator emran@arrayhash.com, existing signed-in Edge session. Routes https://user-portal.arrayhash.com/admin/offboarding/ and /admin/offboarding/dbd20445-3a8d-4587-aa40-5bfb9ed12d5d/. Steps: view the existing offboarding event and apply receipt-date filters. Expected: UTC+6 local times and local calendar days. Actual: table/detail labels explicitly UTC and event shows 2026-11-04 19:04:54 rather than 2026-11-05 01:04:54; filters use UTC midnight and notification sent field uses raw ISO. Proof: prior evidence qa/offboarding-2026-11-05/evidence/live-offboarding-email-record-fixed.jpg and current offboarding page/query source. Scope: user-reported display inconsistency; storage/API UTC timestamps are correct and must remain unchanged. Fix plan: shared explicit timezone display plus local-day SQL bounds; test real boundary rows and deployed existing record without creating new production events/emails.

Fixed in portal main 08bfb19 and deployed successfully in run 41901618002. All 26 integration/contract tests, TypeScript, and production build passed. Live browser: six localhost records correctly appear on November 5 UTC+6, none on November 4; event dbd20445-3a8d-4587-aa40-5bfb9ed12d5d shows received and notification sent at 2026-11-05 01:04:55 UTC+6. Report and screenshots: qa/offboarding-timezone-2026-11-05/report.md and evidence/. UTC storage/API values and frozen historical notification payloads preserved. No new production events or emails.
