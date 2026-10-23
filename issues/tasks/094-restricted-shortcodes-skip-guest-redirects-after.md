---
id: 94
title: Restricted shortcodes skip guest redirects after login prerequisite check
status: closed
priority: medium
created: 2026-10-24T03:12:23.56856+06:00
updated: 2026-10-24T03:28:41.5989+06:00
started: 2026-10-24T03:14:29.075545+06:00
completed: 2026-10-24T03:14:29.075545+06:00
tags:
    - restriction
    - regression
class: standard
---

Active QA task: 1; scheduled day: 2026-10-24. Plan: qa/unified-content-restrictions-2026-10-24/plan.md.
Affected subscription/order IDs: N/A.
Affected user: logged-out guest (user 0); administrator 1 authors the page.
Route: a published page with [arraysubs_restrict roles="qa_nonexistent_role" redirect="/my-account/"]protected[/arraysubs_restrict].
Reproduction: visit the page logged out after login prerequisite refactor.
Expected: deny content and honor the configured redirect.
Actual: code-review reproduction found the early guest-denial return in arraysubs/src/Features/MembersAccess/Services/Hooks.php before redirect handling, producing a message instead.
Proof: pre-fix deny block returned getAccessDeniedMessage before the redirect branch. Browser before/after verification recorded under qa/unified-content-restrictions-2026-10-24/evidence/shortcode-redirect-*.
Scope: editor container integrations deliberately pass empty redirect; this affected standalone shortcodes with redirects.
Fix plan: compute guest denial without returning, and let all denied requests use existing redirect handling. Verify guest redirect and rerun the runtime matrix.

Verified guest navigation reaches http://localhost:10013/qa-unified-redirect-20261024/ from disposable shortcode page20028. Evidence: qa/unified-content-restrictions-2026-10-24/evidence/shortcode-redirect-url.txt and shortcode-redirect-guest-final.png. Shared denial flow now honors redirects after the prerequisite check.
