---
id: 166
title: 'stage-20: Concurrent admin edit notes lose actor and first write'
status: closed
priority: medium
created: 2026-05-23T21:20:13.81911801+02:00
updated: 2026-05-25T00:07:45.071146624+02:00
started: 2026-05-25T00:01:43.672541408+02:00
completed: 2026-05-25T00:07:38.774810006+02:00
tags:
    - qa
    - stage-20
    - concurrency
    - admin-subscriptions
    - audit
class: standard
---

Stage 20 Task 04 admin race. Admin-A user #1 updated Sub-X #1808 billing address line 1 to '100 First St' while Admin-B user #57 updated same field to '200 Second Ave' nearly simultaneously. Both REST saves returned 200, final stored address was exactly '200 Second Ave' (acceptable last-write-wins). However subscription notes #1817/#1818 are duplicate winning diffs only: both say Billing Address line 1 changed from 'Race Base' to '200 Second Ave' and neither records which admin/user performed the edit. Expected notes record both attempts or at least the winning attempt with actor identity, so support can audit concurrent admin edits.

[[2026-05-25]] Mon 00:07
Fix plan: keep last-write-wins for admin edit races, but make audit notes request-scoped and actor-aware so notes cannot be rewritten by another concurrent save before logging. Implementation: SubscriptionController now builds a sanitized intended audit snapshot from the submitted payload before writing, logs against that intended snapshot rather than a post-write DB snapshot, and passes the current admin user ID into arraysubs_add_subscription_note(); note content includes display name and user ID. Verification: php -l passed for SubscriptionController.php. Browser race used two Playwright admin sessions on Sub-X #1808: admin user #1 and QA Admin B user #57 loaded the edit screen, then POSTed address_1 changes nearly simultaneously. Both saves returned 200. Final DB billing_address_1 was exactly '100 First St #166'. New notes #3128/#3129 record both attempts with added_by 57 and 1, author_role=admin, event_type=subscription_details_updated, and distinct audit_changes for 200 Second Ave #166 and 100 First St #166. No transient locks remained. Screenshots: qa/artifacts/issue-166/admin-a-edit-before-race.png, qa/artifacts/issue-166/admin-b-edit-before-race.png, qa/artifacts/issue-166/admin-detail-notes-after-race.png.
