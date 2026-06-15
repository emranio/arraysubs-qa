---
id: 176
title: 'stage-20: 04 Concurrent Actions (Race Conditions)'
status: closed
priority: high
created: 2026-05-19T22:56:24.756032704+02:00
updated: 2026-05-25T00:07:33.446583211+02:00
started: 2026-05-23T08:06:53.492194155+02:00
completed: 2026-05-23T21:22:46.79732627+02:00
tags:
    - qa
    - stage-20
class: standard
---

Source: stages/20-edge-and-regression/04-concurrent-actions.md

[[2026-05-23]] Sat 21:14
Using dedicated Sub-X #1808 for race tests: cust1@example.com, Pro Plan #233 active, parent order #1806, next=2026-06-22 19:14:17 UTC, billing address line 1 baseline 'Race Base'. Target switch plan: Basic Plan #231 downgrade.

[[2026-05-23]] Sat 21:22
Result: QA complete with defects #165 and #166. Customer race used Sub-X #1808 (cust1, Pro Plan -> target Basic Plan #231). Browser verified controls before race. Near-simultaneous REST race: cancel returned 200 scheduled cancellation; switch returned 200 downgrade completed. Final DB/browser state combined both: Active Basic Plan, pending cancellation banner, _waiting_cancellation=1; no proration order, no duplicate subscription/order, but cancellation action was canceled while pending-cancel meta remained. Logged #165 critical. Reset pending cancel for admin race. Admin race used admin #1 setting Billing Address line 1=100 First St and qa-admin-b #57 setting 200 Second Ave concurrently. Both saves returned 200; final stored address exactly 200 Second Ave; forced browser reload confirmed. Notes duplicated winning diff and omitted admin actor/first write, logged #166. No arraysubs lock transients found. Debug log had no 19:xx PHP errors. WooCommerce Orders loaded normally after tests.

[[2026-05-25]] Mon 00:01
Issue #165 fixed: cancel and plan-switch now share a per-subscription mutation lock. Verified by WP-CLI REST proof and browser-context agent-browser proof on Sub-X #1808; concurrent locked calls return 409 and state remains Active Basic with no pending cancel/switch meta. Screenshots: qa/artifacts/issue-165/subscription-1808-browser-before-lock.png and qa/artifacts/issue-165/subscription-1808-browser-after-conflict.png.

[[2026-05-25]] Mon 00:07
Issue #166 fixed: admin subscription update audit now logs each request's sanitized intended changes and current admin actor rather than a post-write DB snapshot. Browser race on Sub-X #1808 with admin #1 and QA Admin B #57 returned 200/200, final address stayed one complete value, and notes #3128/#3129 preserve both actors and address changes. Screenshots: qa/artifacts/issue-166/admin-a-edit-before-race.png, qa/artifacts/issue-166/admin-b-edit-before-race.png, qa/artifacts/issue-166/admin-detail-notes-after-race.png.
