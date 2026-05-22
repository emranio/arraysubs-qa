---
id: 63
title: 'stage-09: Role mapping does not remove Pro Member role on cancellation'
status: closed
priority: critical
created: 2026-05-20T15:24:31.137350395+02:00
updated: 2026-05-21T23:07:47.881523173+02:00
started: 2026-05-21T22:57:37.554384663+02:00
completed: 2026-05-21T23:07:47.881522191+02:00
tags:
    - qa
    - stage-09
    - members-access
    - role-mapping
claimed_by: mold-glade
claimed_at: 2026-05-21T23:07:47.881523073+02:00
class: standard
---

Task 01 seeded pro_member role, role mapping rule for Pro Plan, member1@example.com active Pro Plan subscription #697. Role sync assigns customer,pro_member. After changing #697 from arraysubs-active to arraysubs-cancelled and invoking RoleManager status-change handler, member1 roles remain customer,pro_member. Expected pro_member removed, fallback subscriber only if no remaining roles.

[[2026-05-21]] Thu 23:00
Verified reproduction: with #697 active and member1@example.com roles customer,pro_member, manually changing #697 to arraysubs-cancelled then invoking RoleManager::handleStatusChange left roles customer,pro_member. Fixture restored to active/pro_member. Plan: patch RoleManager so non-active status transitions still evaluate role mappings that currently grant a user's roles, remove add_roles on cancelled/expired unless another active/trial subscription still grants them, preserve on-hold keep/remove behavior, add fallback role when removals leave no roles, and log role add/remove/fallback audit notes. Then verify by WP-CLI handler test and admin browser flow.

[[2026-05-21]] Thu 23:07
Fixed and verified. Code: RoleManager now merges currently-granted role mapping rules for non-active transitions, so cancellation/expiration/on-hold remove paths still run after the post status already changed. Role mutations now no-op unless value changes, add fallback role if removals leave no roles, and write member audit notes for role assigned/removed/fallback. Targeted WP-CLI: cancelling #697 removed pro_member, status arraysubs-cancelled, roles customer, audit note created. Browser QA: admin Edit Subscription #697 -> Change Status -> Cancelled -> Confirm showed success; user profile #25 showed Customer selected and Pro Member not selected. Fixture restored: #697 arraysubs-active, member1 roles customer,pro_member, cancellation/end meta cleared. Lint/phpcs skipped per instruction.
