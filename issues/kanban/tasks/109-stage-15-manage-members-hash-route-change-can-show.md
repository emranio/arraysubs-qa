---
id: 109
title: 'stage-15: Manage Members hash route change can show stale profile'
status: closed
priority: medium
created: 2026-05-23T12:26:28.292961645+02:00
updated: 2026-05-24T17:07:30.677680288+02:00
started: 2026-05-24T16:58:30.501108564+02:00
completed: 2026-05-24T17:07:30.677679246+02:00
tags:
    - qa
    - stage-15
    - manage-members
    - routing
claimed_by: shell-quartz
claimed_at: 2026-05-24T17:07:30.677680167+02:00
class: standard
---

Stage 15 task 02 / regression: while Manage Members had VIP Customer loaded from #/manage-members/37, navigating in the same SPA session to #/manage-members/32 changed the URL but kept VIP Customer profile content. A hard reload/cache-param navigation then loaded Customer One correctly. ManageMembers only auto-loads userId when !selectedUser, so route param changes can leave stale member data visible.

[[2026-05-24]] Sun 16:58
Fix pass started 2026-05-24 by shell-quartz. Verifying route-param load behavior and stale selectedUser guard in Manage Members.

[[2026-05-24]] Sun 17:07
Fixed Manage Members route-param reload: route is source of truth, member data clears on hash change, and latest request guard prevents stale responses from overwriting newer profile. Verified with agent-browser same-session VIP #37 -> Customer One #32 route swap; agent-browser hash-only change logged customer-one-found and vip-not-found. Screenshot qa/artifacts/issue-109-route-swap-customer-one.png. npm run build passed.
