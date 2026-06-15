---
id: 106
title: 'stage-15: Manage Members profile email is not a mailto link'
status: closed
priority: medium
created: 2026-05-23T12:26:28.226314275+02:00
updated: 2026-05-24T16:39:28.06891427+02:00
started: 2026-05-24T16:32:13.138574943+02:00
completed: 2026-05-24T16:39:28.068913298+02:00
tags:
    - qa
    - stage-15
    - manage-members
    - member-profile
    - a11y
claimed_by: shell-quartz
claimed_at: 2026-05-24T16:39:28.068914169+02:00
class: standard
---

Stage 15 task 02 Customer One profile: expected email displayed as clickable mailto link. Browser accessibility tree shows Customer One profile text includes customer1@arraysubs.test, but only action links are Login as Customer, Edit User, Clear, and quick links; no mailto link exists for the email. JSX renders profile.email inside a span instead of an anchor.

[[2026-05-24]] Sun 16:32
Fix pass started 2026-05-24 by shell-quartz. Moving into Stage 15; reading QA stage docs, issue details, and Manage Members profile implementation.

[[2026-05-24]] Sun 16:39
Fixed Manage Members profile email rendering: profile.email is now an anchor with href mailto:{email}. Verified Customer One user #32 via agent-browser links extraction: customer1@arraysubs.test -> mailto:customer1@arraysubs.test. agent-browser confirmed link role href=mailto:customer1@arraysubs.test. Screenshot: qa/artifacts/issue-106-mailto-link.png. Build passed: npm run build in arraysubs.
