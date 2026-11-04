---
id: 157
title: Separate offboarding notification status from email-log link
status: closed
priority: low
created: 2026-11-05T01:06:32.965871+06:00
updated: 2026-11-05T01:08:54.992186+06:00
started: 2026-11-05T01:08:54.992186+06:00
completed: 2026-11-05T01:08:54.992186+06:00
claimed_by: oscine-urman
claimed_at: 2026-11-05T01:08:54.992186+06:00
class: standard
---

Active QA task #6, scheduled 2026-11-05 Asia/Dhaka; plan qa/offboarding-2026-11-05/plan.md. Subscription/order IDs N/A. WordPress admin test; portal administrator emran@arrayhash.com. Route https://user-portal.arrayhash.com/admin/offboarding/dbd20445-3a8d-4587-aa40-5bfb9ed12d5d/. Reproduction: trigger Pro deactivation, view saved record with sent notification. Expected: status/timestamp and View email log are visibly separated. Actual: inline link touches closing timestamp parenthesis with no space. Proof: evidence/live-offboarding-email-record.jpg. Scope: cosmetic; email was sent and link works. Fix plan: make email-log link a block with existing top margin, retain status and metadata; verify rendered live record after deployment.

Fixed in portal 53d9f31. Production deployment 41893732391 succeeded. Browser screenshot live-offboarding-email-record-fixed.jpg confirms the log link is on its own line with spacing below the sent timestamp; record and email link are unchanged.
