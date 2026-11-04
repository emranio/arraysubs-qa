# Browser search verification

Environment: real built user guide served at http://127.0.0.1:4329/; isolated agent-browser session audit-search-qa. No auth, WordPress users, subscriptions or orders involved. Browser source was the existing dist from cf03efd before changes. Browser interactions used visible controls and current snapshot refs.

## Before fix

- Reproduced the reported question three times in **Ask a question** mode: “How do I choose which activity events appear in the audit log?” ranked **Review who changed what in the activity audit trail** first. Its link was `/audit-logs/review-the-activity-audit-trail/#search-the-activity-text`; clicking it navigated there. Expected guide `/audit-logs/choose-what-activity-is-logged/` was second.
- The same full question in **Keywords** mode already ranked **Turn product, coupon, email or settings logging on or off** first (`/audit-logs/choose-what-activity-is-logged/`).
- Four neighboring Ask questions had the correct first guide: who changed a subscription → activity trail; turn off email logging → logging settings; investigate subscription problems → Audit Logs explained; scheduled job successful → job runs.
- No uncaught browser errors. Screenshots were visually inspected. `before-ask-viewport.png` and `before-keywords-viewport.png` provide readable modal comparisons; snapshots include complete result lists. `before-wrong-destination.png` records the clicked incorrect guide.

## After fix

Passed against the rebuilt production output after semantic-index completion.

| Mode / viewport | Query or interaction | Result |
| --- | --- | --- |
| Ask / desktop 1280×577 | Original reported question | Logging settings guide first; clicked result opens `/audit-logs/choose-what-activity-is-logged/#turn-all-four-on-or-off-with-the-setup-wizard` |
| Ask / desktop | Which activity events can I turn off in the audit log? | Logging settings guide first |
| Ask / desktop | How do I change which events the activity log records? | Logging settings guide first |
| Ask / desktop | How do I find who changed a subscription in the activity audit trail? | Activity trail guide first |
| Ask / desktop | Where can I filter the activity log by author? | Activity trail guide first |
| Ask / desktop | How do I turn off email activity logging? | Logging settings guide first |
| Ask / desktop | Which audit logs can I use to investigate subscription problems? | Audit Logs explained first |
| Ask / desktop | How do I check whether a scheduled subscription job ran successfully? | Job runs guide first |
| Keywords / desktop | Original reported question | Logging settings guide first |
| Keywords / desktop | activity audit trail filters | Activity trail guide first |
| Ask / mobile 390×844 | Original question and click first result | Correct settings guide first; navigated to its setup-wizard anchor; guide text/steps readable |

All 11 browser checks passed. `after-ask-desktop.png` and `after-ask-mobile.png` show the correct first result. Desktop and mobile destination screenshots confirm navigation and readable guide content. Screenshot files were visually inspected. The 390-pixel viewport had document and body widths of exactly 390 pixels (no horizontal overflow). No uncaught browser errors were recorded (`after-browser-errors.txt` is empty).

## Cleanup

Closed only the run-owned `audit-search-qa` session. Stopped only Python PID 87198, verified to own the test server on 127.0.0.1:4329. No listener remains on that port. No source, authentication state, production data or user-owned browser tab was modified by this browser lane.
