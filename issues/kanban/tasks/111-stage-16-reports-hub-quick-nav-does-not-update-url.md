---
id: 111
title: 'stage-16: Reports Hub quick-nav does not update URL hash'
status: closed
priority: medium
created: 2026-05-23T12:55:56.045575134+02:00
updated: 2026-05-24T17:41:40.494129545+02:00
started: 2026-05-24T17:34:47.604967453+02:00
completed: 2026-05-24T17:41:40.494128613+02:00
tags:
    - qa
    - stage-16
    - bug
claimed_by: shell-quartz
claimed_at: 2026-05-24T17:41:40.494129444+02:00
class: standard
---

Original task: stage-16 task 01 Reports Hub, Sub-Task 1.4.\n\nObserved: On Reports Hub at https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/reports, clicking the Retention Analytics quick-nav pill scrolls/focuses section but browser URL remains https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/reports.\n\nExpected: Clicking each quick-nav pill updates browser URL hash so section links are shareable/bookmarkable.\n\nEvidence: agent-browser clicked Retention Analytics quick-nav; post-click URL unchanged. Code in arraysubs/src/resources/pages/Reports.jsx prevents default and calls scrollIntoView without updating window.history/location.

[[2026-05-24]] Sun 17:41
Fixed Reports Hub quick-nav hashes: pills now link to #/reports#section-id, push that URL on click, scroll to the section, and direct/bookmarked report section hashes auto-scroll on load/hashchange. Verification: agent-browser clicked Retention Analytics from #/reports; URL changed to #/reports#retention-analytics and section came into view. Direct #/reports#store-credit loaded with Store Credit section visible. Screenshot qa/artifacts/issue-111/reports-retention-hash.png. npm run build passed. agent-browser login attempt timed out, so agent-browser fallback used per AGENTS.md.
