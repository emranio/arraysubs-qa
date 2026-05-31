---
id: 131
title: 'stage-17: Subscriptions list lacks gateway filter'
status: closed
priority: medium
created: 2026-05-23T16:03:55.845721705+02:00
updated: 2026-05-24T20:35:41.032756445+02:00
started: 2026-05-24T20:15:41.618446591+02:00
completed: 2026-05-24T20:35:41.032754541+02:00
tags:
    - qa
    - stage-17
    - gateway-health
    - subscriptions
claimed_by: shell-quartz
claimed_at: 2026-05-24T20:35:41.032756335+02:00
class: standard
---

Task: stages/17-audits-and-logs/04-gateway-health-dashboard.md\n\nSub-Task 04.2 asks to cross-check the Stripe card subscription count by going to ArraySubs -> Subscriptions and filtering by gateway = Stripe.\n\nObserved in browser:\n- Subscriptions page exposes bulk action dropdown, customer search, status tabs, and pagination.\n- No visible gateway filter exists.\n- I cross-checked via DB instead: active/on-hold/trial/pending subscriptions with _payment_gateway=stripe = 2, matching the Stripe card.\n\nExpected: Subscriptions list should provide the gateway filter required by the QA plan, or the QA plan should point to a supported cross-check path.

[[2026-05-24]] Sun 20:20
Plan: add reusable DataList select filters, wire Subscriptions to a gateway=Stripe filter, add REST meta query for _payment_gateway, rebuild assets, verify REST + browser screenshot.

[[2026-05-24]] Sun 20:22
Implemented: DataList select filters, Subscriptions gateway dropdown, REST gateway meta filter. Running build/syntax now.

[[2026-05-24]] Sun 20:32
Found visible count mismatch during screenshot: status tabs stayed global after gateway filter. Added filtered status counts for gateway too.

[[2026-05-24]] Sun 20:35
Fixed and verified. Code: added reusable DataList select filters, Subscriptions Gateway dropdown, REST gateway filter, filtered status-count endpoint support. Verification: php -l OK; arraysubs npm build OK; REST gateway=stripe live statuses returned 6 rows all _payment_gateway=stripe; status counts with gateway=stripe returned All 13, Active 5, On Hold 1, Pending/Trial 0; browser screenshot qa/artifacts/issue-131/subscriptions-gateway-filter.png shows Stripe selected and filtered counts.
