# Audit-log search ranking fix - 2026-11-05

Scope: fix issue #158 so questions about choosing recorded activity events rank the settings guide first, while review/history questions retain the activity-trail guide. User explicitly requested a fix, proper testing, and push to main.

Schedule (Asia/Dhaka): start 01:40 on 2026-11-05; reproduce and inspect ranking, implement the smallest appropriate correction, verify targeted search and the full guide check, then browser regression and push. No recurring watcher.

Targets: current `user-guide` checkout and its generated site served on a run-owned loopback port. Use an isolated agent-browser session. No WordPress settings or portal records need mutation; subscription/order/user IDs are N/A. Preserve existing QA issue history and earlier offboarding evidence.

Plan:

1. Reproduce the incorrect result in the actual search UI and inspect lexical/semantic scores plus both guides' metadata.
2. Correct the underlying guide intent or ranking behavior using existing search mechanisms. Do not hardcode the failing query or weaken its expected result.
3. Keep regression coverage for the reported question, add distinct paraphrases where useful, and test neighboring settings versus review questions in keyword and Ask modes. Run `npm run check` (build, unit tests, all search/link/SEO checks).
4. Verify desktop and narrow-screen search/result navigation in the real browser, close issue #158 only after success, record evidence, stop run-owned processes, and commit/push the guide, QA, and parent references to main.

Progress board: `qa/audit-search-2026-11-05/progress/`. Issue: `qa/issues/tasks/158-audit-log-settings-question-ranks-overview-above.md`.
