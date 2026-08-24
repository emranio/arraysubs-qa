---
id: 7
title: slt2-flex3 fixture lacks the required billing profile for its bound segment handoff
status: closed
priority: high
created: 2026-08-23T12:52:19.781971715+02:00
updated: 2026-08-23T12:52:19.855226068+02:00
started: 2026-08-23T12:52:19.855225317+02:00
completed: 2026-08-23T12:52:19.855225317+02:00
tags:
    - cycle-2
    - fixtures
    - renewal-sync
    - setup
class: standard
---

## QA linkage

- Lifecycle task: `#14` / `SLT-SYN-05`, with fixture owner task `#12` / `SLT-SETUP-03`.
- Blocked plan: `qa/kanban/tasks/014-segment-1-full-prove-the-full-recurring-charge-now.md`.
- Fixture plan: `qa/kanban/tasks/012-slt-setup-03-create-the-slt-account-matrix-7-slt.md`.

## Affected records

- User `482`, `slt2-flex3` / `slt2-flex3@example.test`, role `customer`.
- Paired control `481`, `slt2-flex2` / `slt2-flex2@example.test`, role `customer`.
- Subscription/order IDs: `N/A`; neither user owned one at discovery.

## Reproduction

1. Before the segment handoff, re-query both users' roles and billing metas without editing either fixture.
2. Verify both users have no owned order/subscription.

## Expected

Both dedicated segment buyers have the customer role and the complete billing profile created by task `#12`.

## Actual at discovery

User `481` was complete, while the equivalent read for user `482` returned no first-name or billing-profile rows. The no-order/no-subscription condition passed.

## Resolution rerun

The owner-path rerun on 2026-08-23 confirmed user `482` now persisted the complete expected name, address, city, postcode, country, phone, and billing email. All nine users `474`-`482` passed the same exact query; reserved guests remained absent. The browser user editor showed the same data, browser errors were empty, Mailpit did not move, and no user/product/order/subscription/action/provider/setting mutation occurred.

## Proof and scope

- Browser: `/home/server-manager/slt-evidence/SLT-WATCH-D00-AFTERNOON-USER-482-BILLING.png`.
- Cohort: `/home/server-manager/slt-evidence/SLT-WATCH-D00-AFTERNOON-USERS-SLT2.png`.
- The original empty read was not reproducible; usermeta has no timestamps, so the rerun does not claim how it changed. This fixture issue is closed, and no Stripe/Paddle behavior is implicated.
