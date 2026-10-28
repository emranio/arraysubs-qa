---
id: 137
title: Quick product wizard modal stays mounted across SPA route changes and blocks the next page
status: open
priority: low
created: 2026-10-28T06:07:59.916195+06:00
updated: 2026-10-28T06:07:59.916195+06:00
tags:
    - bug
    - plans
    - admin-ui
class: standard
---

Found during the per-field translate icon QA (2026-10-28); pre-existing, unrelated to that change.

- Active QA task ID / day / plan: N/A
- Affected subscription ID(s) / order ID(s): N/A
- Affected user: admin (ID 1, administrator)
- URL: http://localhost:10013/wp-admin/admin.php?page=arraysubs-mainadmin#/plans → "Add new plan", then navigate by hash to #/profile-builder/profile-form

Steps: open the Create subscription plan wizard, type a title/description, then change the route through a sidebar link / hash change.

Expected: leaving the Plans route closes or unmounts its wizard, or the route change is guarded.

Actual (built-in browser): the "Create subscription plan" modal with the typed draft stayed mounted and visible over the Profile Fields page; its backdrop intercepted the "Add Field" click (no field added) and a programmatic click on the modal's Cancel did not close it; a full reload cleared it. JS evidence: {modalPresent:true, modalVisible:true, backdropPresent:true, modalTitle:'Create subscription plan'} on #/profile-builder/profile-form. Not reproduced in agent-browser (its daemon hung before the retry).
