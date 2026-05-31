---
id: 107
title: 'stage-15: Impersonation banner missing on homepage after login-as-customer'
status: closed
priority: high
created: 2026-05-23T12:26:28.250263456+02:00
updated: 2026-05-24T16:47:15.985864432+02:00
started: 2026-05-24T16:40:17.816219293+02:00
completed: 2026-05-24T16:47:15.9858633+02:00
tags:
    - qa
    - stage-15
    - login-as-user
    - impersonation
    - frontend
claimed_by: shell-quartz
claimed_at: 2026-05-24T16:47:15.985864332+02:00
class: standard
---

Stage 15 task 02: after clicking Login as Customer for Customer One from Manage Members, browser redirected to https://mirror-help.arrayhash.com/. Header changed to public site, but accessibility tree showed no 'You are logged in as Customer One' banner and header still exposed Login until navigating to ?page_id=9. On My Account pages the banner appears and switch-back works. QA plan expects the impersonation banner on every frontend page immediately after impersonation.

[[2026-05-24]] Sun 16:40
Fix pass started 2026-05-24 by shell-quartz. Verifying Stage 15 login-as-customer banner expectation and tracing frontend banner enqueue/render path.

[[2026-05-24]] Sun 16:47
Fixed frontend impersonation banner render path: FrontendBar now renders on wp_body_open plus wp_footer fallback, with per-request duplicate guard. This covers homepage/templates that do not reliably surface footer output before QA inspection. Verified via Alumnium: after Login as Customer for Customer One, / shows 'You are logged in as Customer One (customer1)' and 'Go back as admin'. Playwright verified homepage banner and switch-back to /wp-admin/admin.php?page=arraysubs-mainadmin#/manage-members/32. Screenshots: qa/artifacts/issue-107-homepage-impersonation-banner.png and qa/artifacts/issue-107-my-account-banner.png. php -l passed for FrontendBar.php.
