---
id: 32
title: Removed license returns after reload because local MU helper reseeds it
status: closed
priority: high
created: 2026-09-15T15:41:26.049034+06:00
updated: 2026-09-15T15:44:04.769437+06:00
started: 2026-09-15T15:41:26.059738+06:00
completed: 2026-09-15T15:44:04.769436+06:00
tags:
    - bug
    - license
    - local-environment
class: standard
---

Active QA task ID / scheduled day / plan: N/A; targeted user-reported fix, no active QA cycle. Subscription IDs / order IDs: N/A. WordPress user/customer: user ID 1, login admin, administrator; no customer involved. Test URL: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/license. Browser context: isolated agent-browser license-admin session, authenticated via the user-provided local auto-login URL, core and Pro active. Reproduction: open License, click Remove License, confirm Remove License, observe success and the activation form, reload. Expected: license remains absent and activation form stays visible. Actual: License is activated returns with the same masked key. Proof: screenshots /tmp/arraysubs-license-before.png, /tmp/arraysubs-license-confirm.png, /tmp/arraysubs-license-removed-before-fix.png, /tmp/arraysubs-license-restored.png; recording /tmp/arraysubs-license-reproduction.webm. Scope: local site only; wp-content/mu-plugins/zz-arraysubs-dev-license.php seeds a development license on plugins_loaded priority 1 whenever arraysubs_license is absent. The shared core removal endpoint deletes the option correctly; Pro license checks then see the freshly recreated option on the next request. No remote license activation is implicated. Fix plan: disable the local seeding helper with a reversible .disabled filename, use the actual browser Remove License action again, verify reloads retain the unlicensed form and core remains available with Pro installed but unlicensed. Preserve unrelated plugin edits and QA issues.

Resolution: Disabled the local MU helper reversibly by renaming wp-content/mu-plugins/zz-arraysubs-dev-license.php to zz-arraysubs-dev-license.php.disabled (30 lines, content unchanged). Removed the existing development license through the real admin confirmation dialog. Browser verification: success message appeared; full reload showed Activate ArraySubs Pro; authenticated GET /wp-json/arraysubs/v1/license returned HTTP 200, active=false, hasLicense=false, message=No license is activated. Navigating to Subscriptions loaded the normal core list with All(412); returning to License and another full reload still showed the activation form. No browser JavaScript errors. Screenshots: /tmp/arraysubs-license-fixed.png, /tmp/arraysubs-license-fixed-subscriptions.png, /tmp/arraysubs-license-fixed-final.png. A smoke-test wait used an incorrect expected label (Add New Subscription); the actual New custom subscription link and loaded table were verified by snapshot and screenshot. Core and Pro source required no changes; the defect was the local fixture restoring the option on every request. Existing unrelated edits and QA tasks were preserved. No lint, PHPCS, build, or dependency installation was needed.
