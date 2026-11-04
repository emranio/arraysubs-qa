# Audit-log search ranking fix - 2026-11-05

Issue: #158. Scope: the user guide's search result for choosing which activity events are recorded. No WordPress plugin behavior or portal data changes.

## Reproduction and cause

The real browser reproduced the reported question twice in Ask a question mode:

> How do I choose which activity events appear in the audit log?

The first result was the review guide's "Search the activity text" section. Following it opened `/audit-logs/review-the-activity-audit-trail/#search-the-activity-text`. Keyword mode already ranked `/audit-logs/choose-what-activity-is-logged/` first.

The shared hybrid scorer gave the review guide 0.733965 and the settings guide 0.731899. The review section's higher semantic similarity narrowly outweighed the settings guide's keyword advantage. The settings guide described the individual product/coupon/email/settings switches but lacked a focused search phrase for selecting recorded activity events. Details: `evidence/ranking-analysis.md` and `evidence/browser-results.md`.

## Change

- Added `choose which activity events are recorded` to the settings guide's existing `seo.keywords` and updated its content date.
- Kept the search engine, guide title/body, original failing query and expected result unchanged.
- Added four regression cases: the original question in keyword mode, two differently worded Ask questions about selecting event types, and an Ask question about filtering history by author that must still prefer the review guide.
- Confirmed the guide's scope against the core logging controls and Pro settings REST handler: product, coupon, email and settings are the four configurable types.

## Validation

`npm run check` passed with exit 0:

- Built 308 HTML pages, including 281 guides.
- Rebuilt all 8,887 search records with natural-language search enabled.
- Passed all 55 unit tests.
- Passed all 604 ranking checks, including the original reported question, the four added cases, and every existing guide scenario.
- Checked Markdown copies, links, anchors, metadata, structured data, sitemap, robots, llms.txt and search-index integrity with no problems.

Log: `evidence/user-guide-check.log`.

Browser verification uses the actual built site at `http://127.0.0.1:4329/`, an isolated `audit-search-qa` session, and no authenticated accounts or mutable WordPress data. There are no subscription/order/customer IDs for this issue. Final browser results are recorded in `evidence/browser-results.md`.

All 11 browser checks passed. The reported question ranks the settings guide first in both Ask and Keywords. Two settings paraphrases, author filtering, history review, email-logging controls, log overview and scheduled-job questions return the appropriate guides. Result clicks open the correct settings guide on desktop and at 390x844; mobile text is readable with no horizontal overflow. No uncaught browser errors were observed. Before/after screenshots were inspected.

The isolated browser was closed and the run-owned preview server stopped; port 4329 has no listener. No other sessions, accounts or services were changed. Issue #158 is resolved; all automated and browser checks for this fix passed.

Guide source and regression cases were committed and pushed to `main` in `5e172f6` (`fix: clarify audit logging settings search intent`). Generated site/search assets remain build output. The QA record and parent guide reference accompany the fix.
