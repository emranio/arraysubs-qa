---
id: 158
title: Audit-log settings question ranks overview above settings guide
status: closed
priority: low
created: 2026-11-05T01:10:40.271022+06:00
updated: 2026-11-05T01:49:52.666661+06:00
started: 2026-11-05T01:40:44.460495+06:00
completed: 2026-11-05T01:49:52.666661+06:00
claimed_by: oscine-urman
claimed_at: 2026-11-05T01:49:52.666661+06:00
class: standard
---

Active QA task #5, scheduled 2026-11-05 Asia/Dhaka; plan qa/offboarding-2026-11-05/plan.md. Subscription/order IDs N/A. WordPress/customer IDs and roles N/A. Browser context N/A: guide search verifier against generated local site. Target guides /audit-logs/choose-what-activity-is-logged/ and /audit-logs/review-the-activity-audit-trail/. Reproduction: run npm run check inside user-guide at cf03efd; query How do I choose which activity events appear in the audit log? from tests/search-quality.json:2075. Expected top result audit-logs/choose-what-activity-is-logged. Actual top result audit-logs/review-the-activity-audit-trail. Proof: qa/offboarding-2026-11-05/evidence/user-guide-check.log; guide build308 pages and 55 unit tests pass, verification reports one mismatch among600 ranking checks. Scope: offboarding search queries pass; no proof established that this predates current guide update. This audit-log ranking issue is separate from the offboarding feature; no search-source changes made in this run.

Fix cycle started 2026-11-05 01:40 Asia/Dhaka. Fresh plan qa/audit-search-2026-11-05/plan.md; progress task #1 reproduces lexical/Ask results and task #2 will clarify guide intent using existing metadata, preserving the original expected result. Scope: metadata and focused regression queries unless evidence requires a shared ranking change. Browser session audit-search-qa against run-owned loopback preview; no account needed.

Resolved in follow-up plan qa/audit-search-2026-11-05/plan.md, task #3, scheduled 2026-11-05. Actual browser reproduced Ask-only reversal three times; metadata clarified with one phrase, engine and original expected result unchanged. npm run check exit 0: 308 pages/281 guides, semantic search on,55 unit tests and604 ranking checks passed. All 11 browser checks pass at http://127.0.0.1:4329/: original question in Ask/Keywords, paraphrases and neighboring intents, correct guide navigation desktop/mobile 390x844, no overflow/errors. Evidence: qa/audit-search-2026-11-05/evidence/browser-results.md, after-ask-desktop.png, after-ask-mobile.png, user-guide-check.log. Preview browser/server closed; port 4329 no listener. No WordPress users/orders/subscriptions affected.
