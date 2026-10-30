---
id: 115
title: Portal Action Failures splits HTTP status code digits across lines
status: closed
priority: low
created: 2026-10-25T20:50:50.843023+06:00
updated: 2026-10-31T02:20:58.259661+06:00
tags:
    - bug
    - admin-ui
class: standard
---

Active QA task ID and scheduled day: N/A (user-reported targeted fix, 2026-10-25 Asia/Dhaka).
Plan markdown path: N/A; no active QA cycle. Scoped fix plan: trace shared badge styles, give HTTP codes a single-line modifier and an explicitly sized column, build core assets, then compare real browser rendering with the compiled styles.
Affected subscription IDs: 27614 and 1808 (existing queue rows only; no changes). Affected order IDs: N/A.
Affected WordPress user/customer IDs, login/email and roles: browser actor login admin, administrator; numeric ID/email N/A. Displayed customers Stripe SCA Fix QA (stripe-sca-fix-20260815@example.test) and Cust One (cust1@example.com); numeric IDs/roles N/A.
Exact URL: https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/audits/portal-action-failures.
Browser context: isolated agent-browser Chromium session http-column-live; viewports 1440x1000 and 1024x900.
Reproduction: sign in as administrator, open Portal Action Failures at 1440px, inspect the HTTP column for the five existing 500/409 rows.
Expected: the warning icon and all three status digits fit together on one line.
Actual before fix: every status label occupies two text rectangles; 500 renders as 50 then 0 and 409 as 40 then 9. Badge height 43px. Screenshot evidence/http-status-column/before.png.
Cause: HTTP codes reuse the shared job-status badge whose labels allow breaking anywhere for long failure reasons.
Resolution: add arraysubs-job-status--http to this badge, prevent code wrapping and icon shrinking, and set the HTTP header/cells to a 72px no-wrap column. Core presentation owns the changes; Pro REST payloads are unchanged. Long renewal/job failure labels retain their existing wrapping behavior.
Verification: npm run build succeeded with the installed dependencies. Real queue browser preview applied the exact four rules extracted from the compiled mainadmin.css and the class names added by the JSX change. All five 500/409 labels use one text rectangle and 23.5px badge height at 1440px and remain one line at 1024px; no browser JavaScript errors. At 1024px cells are 78px wide, badges 57.16-57.52px wide, and document width equals viewport width. Evidence: evidence/http-status-column/after.png and after-1024.png.
Scope/limitations: the local installation has Pro inactive, so it cannot load this queue. Visual verification used a temporary browser preview on the actual live test queue, not a deployment of the local bundle. No subscription, customer, order, setting, plugin activation, license, or queue resolution was changed. The preview ends when the browser session closes. No PHPCS/lint or dependency installation.


Release QA2026-10-31 observed deployed recurrence (prior closed status preserved). Task#2; day2026-10-31; planqa/release-2026-10-31-0208/plan.md. URLhttps://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/audits/portal-action-failures, admin1 administrator, rqa0208-read desktop1280. Subscriptions27614/1808, ordersN/A; displayed synthetic customers/logins as above, IDsnotresolved(read-onlyqueue). Load queue and inspect HTTPcolumn:500renders50/0and409renders40/9 overtwo lines. Expected one line. Proofqa/release-2026-10-31-0208/evidence/audits-portal-action-failures.png and.txt, imageinspected. No queueentrieschanged. Localfixnotproven deployed; deploymentblocker#151 applies.
