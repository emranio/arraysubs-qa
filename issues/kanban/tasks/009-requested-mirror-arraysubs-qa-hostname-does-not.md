---
id: 9
title: Requested mirror-arraysubs QA hostname does not resolve
status: archived
priority: high
created: 2026-08-15T23:36:27.943142206+02:00
updated: 2026-08-16T08:11:20.03926743+02:00
started: 2026-08-16T08:11:20.039266127+02:00
completed: 2026-08-16T08:11:20.039266127+02:00
tags:
    - qa
    - environment
    - dns
class: standard
---

QA progress: #8 (stage-16) and #9 (stage-03).
QA plans: qa/stages/16-analytics/06-ai-reports-pro.md; qa/stages/03-products/17-subscription-box-free.md.

Affected subscription IDs: N/A.
Affected order IDs: N/A.
Affected WordPress users/customers: administrator user ID 1 (admin) for browser access; customer ID/login/email/role N/A.
Affected product: existing Subscription Box product ID 12600 was used only on the configured fallback site.

Exact URL and context: https://mirror-arraysubs.arrayhash.com/wp-admin in an isolated agent-browser admin session.

Reproduction:
1. Open https://mirror-arraysubs.arrayhash.com/wp-admin with agent-browser.
2. Observe navigation before any WordPress response.
3. Resolve mirror-arraysubs.arrayhash.com from the server.

Expected: the requested QA hostname resolves and serves the WordPress admin for browser verification.
Actual: Chromium returns net::ERR_NAME_NOT_RESOLVED and the hostname has no server-side DNS result.

Proof: agent-browser reported Navigation failed: net::ERR_NAME_NOT_RESOLVED. WordPress home and siteurl both resolve to https://mirror-help.arrayhash.com.

Known scope/counterexample: https://mirror-help.arrayhash.com resolves, is the current installation home/siteurl, and passed the AI Reports and Subscription Box browser checks. This is a DNS/environment blocker for the requested hostname, not a reproduced plugin failure.
