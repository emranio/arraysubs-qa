---
id: 54
title: Include linked guest orders after subscription owner assignment
status: closed
priority: high
created: 2026-10-18T17:26:36.835595+06:00
updated: 2026-10-18T17:28:23.674382+06:00
started: 2026-10-18T17:26:38.658381+06:00
completed: 2026-10-18T17:28:20.369803+06:00
tags:
    - bug
    - developer-api
    - regression
class: standard
---

Active QA task: Task 7. Scheduled day: 2026-10-18 (Asia/Dhaka).
Plan: qa/developer-support-2026-10-18/plan.md.
Site/route: http://localhost:10013/. Trusted developer PHP Orders API after wp_loaded via WP-CLI; no browser/REST route. Actor: admin user 1; HPOS enabled, both plugins active.
Affected subscription/order: read-only reproduction used subscription 16316 and guest order 12543 with temporary metadata filters only; original records unchanged. Disposable regression identities will be recorded after testing.
Affected customer: user 1 (administrator) was the injected registered subscription owner; guest order customer ID 0 with its existing billing email. No login/email was changed or exposed.
Reproduction:
1. Resolve an existing guest order with a valid billing email and an existing subscription.
2. Temporarily filter that subscription metadata to customer ID 1, the order billing email, and explicit parent/history references to the guest order.
3. Compare arraysubs_subscription_matches_order_customer(order, subscription) with Orders::query(subscription_id, include=[order], output=ids).
Expected: the canonically linked guest order appears; two different registered IDs must still never bridge by email, and explicit customer_id remains an AND selector.
Actual/proof: canonical identity=true, query_items=[]. Orders.php implicitly narrows to customer_id=1 before its per-candidate verifier can accept the guest order. The filter was removed in finally; no database writes occurred.
Known scope: existing guest+guest and registered+registered tests pass. Mixed registered subscription / guest order (including retained guest history after subscription assignment) is missing. Affects order_references projections too.
Fix plan: reuse WooCommerce's existing datastore-portable customer selector, whose flat list ORs numeric customer IDs and emails in both CPT and HPOS. Only when no explicit customer_id was supplied, discover candidates by subscription ID OR sanitized stored email, then retain existing per-candidate identity and relationship checks, bounded batches and ID order. Keep explicit customer_id unchanged. Add dedicated real disposable fixtures regression for mixed identities, duplicate-free ascending/descending pages, include/AND filtering, cross-customer isolation, guest-only and identity projections; run with Pro and core-only. This is a trusted PHP query path with no browser interaction to retest.

[[2026-10-18]] Sun 17:28
Verified fixed 2026-10-18. Orders::query now uses the existing WooCommerce customer ID/email OR selector only for implicit subscription discovery; explicit customer_id still narrows by AND. Canonical per-candidate identity/relationship checks, 100-record batches and ID ordering remain unchanged. Focused real-fixture regression failed on the linked guest-history assertion before the source patch, then passed 28 assertions with Pro and 28 core-only on HPOS. Coverage: mixed guest/registered retained history and initial_order_id projection, same-email different registered customer refusal, unrelated guest refusal, explicit customer selector, registered owner changed/missing email, guest unidentified failure, duplicate-free ascending/descending pages across 104 linked orders and the 100-candidate boundary, reverse relationship consistency. The existing WooCommerce CPT compiler was also checked to generate the same OR identity selector (not a claim of a full CPT datastore execution).
Final Pro fixtures: subscription 17344, initial guest order 17352, customer IDs 356/358, logins guest-history-d6532535-{owner,other}. Core-only: subscription 17343, initial guest order 17351, customer IDs 355/357, logins guest-history-23d9d8a4-{owner,other}. Disposable fixtures are cleaned in finally; unrelated records unchanged. Generated example.test fixture emails only, roles customer. Sources: arraysubs/src/Developer/Orders.php; QA script guest-order-history-regression.php; evidence guest-order-history-pro-results.txt and guest-order-history-core-results.txt under qa/developer-support-2026-10-18/. Developer subscriptions/orders guide updated with exact mixed-identity and explicit-selector contract. No browser path exists for this PHP-only query; real WP/Woo fixtures execute the actual history reader.
