---
id: 10
title: 'stage-7: Purchase subscription and verify My Account pause and resume'
status: closed
priority: critical
created: 2026-08-16T13:01:31.931185+06:00
updated: 2026-08-16T13:11:50.218028+06:00
started: 2026-08-16T13:11:50.218027+06:00
completed: 2026-08-16T13:11:50.218027+06:00
tags:
    - stage-07
    - customer-portal
    - checkout
    - pause-resume
    - qa
claimed_by: alum-perry
claimed_at: 2026-08-16T13:11:50.218028+06:00
class: standard
---

Create a fresh customer, purchase a real subscription in the browser, activate if required, and verify pause/resume controls and status transitions in My Account. Plans: qa/stages/07-customer-portal/02-view-subscription-detail.md, 07-pause-and-resume.md, and 11-action-availability-by-status.md.

[[2026-08-16]] Sun 13:11
PASS 2026-08-16. Fresh customer user ID 10, login codex-pauseqa, email codex-pause-qa-20260816@example.test, role customer purchased product subscription 1 at checkout. Order #1215 completed and subscription #1216 entered Trial. Customer My Account UI showed Pause when allowed, changed to On Hold after POST /wp-json/arraysubs/v1/my-subscriptions/1216/pause returned 200, showed Resume Now only when Allow Resume was enabled, and returned to Trial after POST /resume returned 200. With Allow Resume off, UI hid Resume and REST returned 403 resume_disabled. With customer pause off, UI hid Pause and REST returned 403 pause_disabled. Master Pause off hid all portal controls and both actions returned 403. Admin non-owner received 403 permission_denied; guest received 401 not_logged_in. Final subscription state Trial; original settings restored. Evidence: /tmp/arraysubs-secondary-qa-20260816/screenshots/order-confirmation.png, my-account-subscription-1216-trial-actions-on.png, my-account-paused-resume-enabled.png, my-account-paused-resume-disabled.png, my-account-subscription-1216-resumed.png, my-account-subscription-1216-final-restored-settings.png.
