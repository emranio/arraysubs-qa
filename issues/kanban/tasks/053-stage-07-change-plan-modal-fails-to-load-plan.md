---
id: 53
title: 'stage-07: Change Plan modal fails to load plan options'
status: closed
priority: critical
created: 2026-05-20T14:54:24.266762177+02:00
updated: 2026-05-21T22:40:07.212988056+02:00
started: 2026-05-21T22:35:29.363856893+02:00
completed: 2026-05-21T22:40:07.212987124+02:00
tags:
    - qa
    - stage-07
    - customer-portal
    - plan-switching
claimed_by: mold-glade
claimed_at: 2026-05-21T22:40:07.212987956+02:00
class: standard
---

Stage 07 Tasks 08/09. On cust1 active Basic Monthly subscription #633, clicking Change Plan opens the modal but it displays "Failed to load plan options. Please try again." No Upgrade/Downgrade or Others tabs load, and no target products/proration preview can be selected. This reproduces the earlier Stage 06 portal Change Plan failure (#39) on the Stage 07 fixture. Expected: modal loads Upgrade/Downgrade and Others tabs with eligible plans, pricing, View Details links, and proration/deferred-switch preview.

[[2026-05-21]] Thu 22:40
Plan: reproduce on #633 with required Alumnium XHR repair; verify REST switch-options as cust1; inspect product target config and plan-switching settings; if API/UI still fail, patch route/JS; if API returns 200 and modal loads, classify original failure as stale/Alumnium XHR false positive and close with evidence. Result: REST /arraysubs/v1/subscriptions/633/switch-options as cust1 returns 200 with Pro Monthly upgrade. Browser modal now opens Change Your Plan, shows Upgrade/Downgrade tab, Pro Monthly 9.99 / 1 month, View Details, Select. Selecting Pro loads Plan Change Summary with credit 9.99, charge 9.99, amount due 0.00, Confirm Plan Change. No code patch needed for this issue; did not confirm switch or mutate subscription.
