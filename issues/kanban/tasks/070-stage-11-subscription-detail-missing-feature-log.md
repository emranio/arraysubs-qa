---
id: 70
title: 'stage-11: Subscription detail missing Feature Log entry point'
status: closed
priority: medium
created: 2026-05-23T08:56:36.995998752+02:00
updated: 2026-05-24T09:08:31.976402142+02:00
started: 2026-05-24T09:04:35.808181815+02:00
completed: 2026-05-24T09:08:31.97640101+02:00
tags:
    - qa
    - stage-11
    - feature-manager
claimed_by: shell-quartz
claimed_at: 2026-05-24T09:08:31.976402022+02:00
class: standard
---

Stage 11 Task 04 Subtask 4.1. Subscription detail for cust1 Pro Plan #271 has no visible Feature Log / View Features / Entitlement Review action. Code search shows Feature Log shortcut only in Manage Members at arraysubs/src/resources/pages/MembersAccess/ManageMembers.jsx:519. Expected subscription detail entry point should open Feature Log with user_id=5. Browser verified no matching action on #/subscriptions/detail/271.

[[2026-05-24]] Sun 09:08
Fix verified 2026-05-24 by shell-quartz. Added Subscription Detail header action 'Feature Log' when Feature Manager pro class is loaded and subscription has a customer. Link targets #/subscriptions/feature-log?user_id=<customer_id>. Built arraysubs assets. Alumnium browser: #/subscriptions/detail/271 showed header actions Cancel Subscription, Edit Subscription, Feature Log, Login as Customer; customer cust1@test.local user ID 5. Clicking Feature Log opened #/subscriptions/feature-log?user_id=5; page title Feature Log, customer cust1/cust1@test.local, feature tables Enterprise Plan and Pro Plan visible.
