---
id: 11
title: 'stage-2: Secondary Pause and Resume setting and REST verification'
status: closed
priority: critical
created: 2026-08-16T13:01:31.932674+06:00
updated: 2026-08-16T13:11:50.218028+06:00
started: 2026-08-16T13:11:50.218028+06:00
completed: 2026-08-16T13:11:50.218028+06:00
tags:
    - stage-02
    - settings
    - rest
    - pause-resume
    - qa
claimed_by: alum-perry
claimed_at: 2026-08-16T13:11:50.218028+06:00
class: standard
---

Verify the migrated pause_subscription.customer_can_resume setting, absence of legacy customer_actions controls, persistence, and authenticated REST permission behavior on the local site. Plan: qa/stages/02-settings/01-general-settings-each-section.md and qa/stages/07-customer-portal/07-pause-and-resume.md.

[[2026-08-16]] Sun 13:11
PASS 2026-08-16 on http://localhost:10013. Admin user ID 1 (admin, administrator) verified /wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general and #/settings/skip-pause. The General screen contains no suspension/reactivation switches; its Skip & Pause settings link navigates correctly. GET /wp-json/arraysubs/v1/settings/data returned HTTP 200 with pause_subscription.customer_can_resume and no customer_actions.allow_suspension or allow_reactivation. Tested resume false/true persistence, customer pause false/true, and master Pause false/true conditional rendering. Restored baseline: enabled=true, max_duration_days=30, max_pauses_per_subscription=2, min_days_between_pauses=30, customer_can_pause=false, customer_can_resume=true, require_reason=false. Evidence: /tmp/arraysubs-secondary-qa-20260816/screenshots/admin-general-baseline.png, admin-allow-resume-off.png, admin-pause-disabled-conditional-hidden.png, admin-skip-pause-restored.png.
