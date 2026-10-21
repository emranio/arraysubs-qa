---
id: 79
title: 'Product decision: a plan switch at checkout charges the target plan''s signup fee (a switch from the subscription screen does not)'
status: closed
priority: low
created: 2026-10-21T01:34:12.074715223+02:00
updated: 2026-10-21T07:18:53.808741311+02:00
started: 2026-10-21T07:18:53.808740529+02:00
completed: 2026-10-21T07:18:53.808740529+02:00
tags:
    - plan-switching
    - checkout
    - signup-fee
    - product-decision
class: standard
---

Active QA task ID and scheduled day: N/A (found 2026-10-20 by the #71-#78 fix verification; fix log qa/issues-65-69-fix-log.md).
Affected subscription/order IDs: N/A (code reading; observed in rolled-back probes). Affected users: N/A.
Route: checkout with One Subscription Per Customer + Auto Migrate on. SubscriptionProducts\Services\Hooks::addSignupFeeToCart() adds the signup fee for every subscription line, checkout-migration lines included; the cart cells also show it.
Expected (to decide): either no signup fee for a line that replaces the customer's current subscription (as the switch screen does), or keep charging it and document it.
Actual: the target plan's signup fee is charged on top of the plan-change amount.
Proof: verifier note in scratchpad fix7x2/result-migration.json (NIT-migration-signup-fee, with the exact edit: skip CartSummary::isMigration() lines; the six cart cells would need the same rule).
Scope notes: deliberately not changed during release hardening because it changes what customers are charged.

[[2026-10-21]] Wed 07:18
Closed as intended (user decision 2026-10-21): a plan switch at checkout keeps charging the target plan's signup fee. No change.
