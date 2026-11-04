---
id: 158
title: Audit-log settings question ranks overview above settings guide
status: open
priority: low
created: 2026-11-05T01:10:40.271022+06:00
updated: 2026-11-05T01:10:40.271022+06:00
class: standard
---

Active QA task #5, scheduled 2026-11-05 Asia/Dhaka; plan qa/offboarding-2026-11-05/plan.md. Subscription/order IDs N/A. WordPress/customer IDs and roles N/A. Browser context N/A: guide search verifier against generated local site. Target guides /audit-logs/choose-what-activity-is-logged/ and /audit-logs/review-the-activity-audit-trail/. Reproduction: run npm run check inside user-guide at cf03efd; query How do I choose which activity events appear in the audit log? from tests/search-quality.json:2075. Expected top result audit-logs/choose-what-activity-is-logged. Actual top result audit-logs/review-the-activity-audit-trail. Proof: qa/offboarding-2026-11-05/evidence/user-guide-check.log; guide build308 pages and 55 unit tests pass, verification reports one mismatch among600 ranking checks. Scope: offboarding search queries pass; no proof established that this predates current guide update. This audit-log ranking issue is separate from the offboarding feature; no search-source changes made in this run.
