---
id: 117
title: Activity Audits Changes control has an oversized misaligned icon
status: closed
priority: low
created: 2026-10-25T21:32:33.051286+06:00
updated: 2026-10-25T21:32:33.051286+06:00
tags:
    - bug
    - admin-ui
class: standard
---

Active QA task ID and scheduled day: N/A (direct user-reported UI fix, 2026-10-25 Asia/Dhaka).
Plan markdown path: N/A; no active QA cycle. Scoped fix plan: inspect the activity-row control and modal, replace the navigation link with an extra-small native button, size and align the comparison icon, build assets, then verify real mouse/keyboard interactions.
Affected subscription IDs: 8220 (existing audit row used for read-only verification). Order IDs: N/A.
Affected WordPress users/customers: browser actor login admin, administrator; actor numeric ID/email N/A. Subscription row references Member #195; login/email/role N/A.
Exact URL: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/audits/activity-audits.
Browser context: isolated agent-browser session audit-changes-admin, Chromium, 1440x1000 and 1024x900; local Pro available during this verification.
Reproduction: open Activity Audits and inspect Changes at the far right of activity rows. The default-size GitCompare icon sits above the unframed label; the control is a href=# link for opening an in-page modal. Evidence: evidence/activity-changes-button/before.png.
Expected: an extra-small button with aligned label and icon that opens the selected entry's change details without navigation.
Actual before fix: icon and label lack flex alignment, and the icon has the default 24px size. This record concerns presentation and action semantics; a separate data-loading failure was not reproduced.
Resolution: replace the link with type=button and aria-haspopup=dialog, retain the existing selectedLog/modal handler, use a 12px decorative icon, and style the button at 22px high with 11px text, 6px horizontal padding, no wrapping, and the WordPress admin accent. Scoped specificity overrides the native button-small sizing. Preserve the user's existing comparison icon addition and all unrelated edits.
Verification: npm run build succeeded. Real local page loaded the rebuilt bundle; 19 controls across 30 rows measured 22px high with 12px icons at both viewport widths. Mouse opened a settings change modal; Enter opened modules.audits with previous No and changed Yes. Escape and the Close modal control close it; focus returns to the originating button. Space on the Subscription #8220 row opened its Status change details. The #/audits/activity-audits hash stayed unchanged throughout. Browser JavaScript errors: none. Evidence: after.png, dialog.png, and 1024.png in evidence/activity-changes-button/.
Scope/counterexamples: only rows with structured changes show the button, as before; email rows without changes show no button. Core owns the UI and changelog entry; Pro REST and audit payloads are unchanged. No subscriptions, orders, customers, settings, or log data were changed. No deployment, PHPCS, lint, or dependency installation.
