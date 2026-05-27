---
id: 141
title: 'stage-15: 03 Member Commerce Overview'
status: closed
priority: medium
created: 2026-05-19T22:56:18.977993418+02:00
updated: 2026-05-24T17:34:35.873606091+02:00
started: 2026-05-23T08:06:53.453284742+02:00
completed: 2026-05-23T12:38:57.442175772+02:00
tags:
    - qa
    - stage-15
class: standard
---

Source: stages/15-manage-members/03-member-commerce-overview.md

[[2026-05-23]] Sat 12:38
QA 2026-05-23: Seeded Customer One #32 commerce fixture: subscriptions #1287 active Basic Monthly, #1290 cancelled Pro Monthly, #1293 trial Trial Weekly; Standard Tee orders #1296/#1298 plus refunded order #1300/#1302; store credit reset to 5 via +0 and - admin adjustments; billing/shipping addresses set. Browser: profile stats show 0 spent, 3 orders, 1 active subscription, 3 total subscriptions, 5 store credit, 5 refunds. Subscriptions section expanded by default with expected 8 columns and View links. #1287 click opened subscription detail; Go Back returned to Manage Members. Purchased Products expands with Standard Tee, simple, qty 2, 0. Customer 2 #33 fixture with only subscription shows 'No non-subscription products purchased.' Addresses section shows billing email/phone/address and shipping phone/address read-only. Store Credit quick link failed to preload scoped customer page; issue #110. Backend credit REST confirms balance 5 and recent admin debit/credit history.

[[2026-05-24]] Sun 17:34
Issue #110 fixed: Store Credit quick link and direct /store-credit/:userId routes now scope to route customer and reload on route swaps. Verified Customer One #32 balance/history from Manage Members quick link; route swap #33 -> #32 also correct. Screenshot qa/artifacts/issue-110/store-credit-customer-one-scoped.png.
