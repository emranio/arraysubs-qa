---
id: 46
title: Block cancellation undo on parent-billed developer API children
status: closed
priority: high
created: 2026-10-18T17:13:13.276435+06:00
updated: 2026-10-18T17:15:51.665779+06:00
started: 2026-10-18T17:13:15.926187+06:00
completed: 2026-10-18T17:15:51.665779+06:00
tags:
    - bug
    - developer-api
    - regression
class: standard
---

Active QA task: Task 7. Scheduled day: 2026-10-18 (Asia/Dhaka).
Plan: qa/developer-support-2026-10-18/plan.md.

Affected subscription IDs: box child 14287; billing owner 14284 (temporary fixtures, already cleaned).
Affected order IDs: parent order 14293 (temporary fixture, already cleaned).
Affected customer: WordPress user 317, login subscription-order-66982ed1-a, email subscription-order-66982ed1-a@example.test, role customer (temporary fixture, already cleaned).
Actor: WordPress admin user 1, trusted PHP API via WP-CLI after wp_loaded.
Site/route: http://localhost:10013/. No REST/admin route: ArraySubs\Developer\Subscriptions::undoCancellation(). Browser context: N/A, server-side API regression with Pro active and HPOS enabled.

Reproduction:
1. Create a same-customer box parent/child with canonical reciprocal relationship metadata.
2. Set the child's stored _waiting_cancellation flag to yes as a recovery-state fixture.
3. Resolve the child with Subscriptions::fromId(child_id) and call undoCancellation().

Expected: a parent_billed_subscription error; the child's flag and billing state remain unchanged until the billing owner is updated.
Actual: returns a full success snapshot, clears the child's cancellation flag, and acts independently of its billing owner.
Proof: subscription-order-api-regression-results.txt recorded FAIL: Child cancellation undo cannot create an independent billing lifecycle. Returned family.kind=box, family.parent_id=14284, family.billing_owner_id=14284, cancellation.scheduled=false, schedule.next_payment_at=1793185927.

Known scope: child quantity, renewal-date and suspend operations already reject independent mutation. The missing guard is specific to undoCancellation().
Fix plan: reuse the facade's existing child/billing-owner guard before the canonical undo helper. Root owns the source patch. Retest the exact regression and the adjacent lifecycle matrix; no phpcs.

[[2026-10-18]] Sun 17:15
Verified fixed on 2026-10-18: Subscriptions::undoCancellation now rejects parent-billed children before calling the canonical helper. The exact child-undo regression passes with Pro active and with --skip-plugins=arraysubspro. Full subscription/order API matrices passed 70/70 checks in each mode with HPOS enabled. Fixtures verified cleaned. Evidence: qa/developer-support-2026-10-18/subscription-order-api-regression-results.txt and subscription-order-api-regression-core-results.txt. No browser surface exists for this trusted PHP operation; adjacent product UI verification is tracked in Task 7.
