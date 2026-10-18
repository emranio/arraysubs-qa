---
id: 48
title: Honor developer order include IDs in HPOS queries
status: closed
priority: high
created: 2026-10-18T17:14:49.36456+06:00
updated: 2026-10-18T17:15:51.717623+06:00
started: 2026-10-18T17:15:51.698139+06:00
completed: 2026-10-18T17:15:51.717623+06:00
tags:
    - bug
    - developer-api
    - regression
class: standard
---

Active QA task: Task 7. Scheduled day: 2026-10-18 (Asia/Dhaka).
Plan: qa/developer-support-2026-10-18/plan.md.
Site/route: http://localhost:10013/. Trusted developer PHP APIs via WP-CLI after wp_loaded; no browser/REST route. Actor: admin user 1. Context: HPOS enabled, verified with Pro active and --skip-plugins=arraysubspro.
Affected subscription IDs: N/A (these cases exercise order API identity/filtering).
Affected WordPress customers: N/A, disposable customer fixtures were cleaned in finally; role customer, distinct generated subscription-order-* logins and example.test emails.

Affected order IDs in the Pro failure: requested source/order-side fixture IDs 14386 and 14773; actual results additionally included 14775, 14777, 14810, 14825, 14881, 14899. All temporary fixtures cleaned.

Reproduction:
1. Create several orders for one test customer.
2. Call ArraySubs\Developer\Orders::query([customer_id=>owner, include=>[first_id,second_id], output=>ids, per_page=>100]).
3. Repeat with another customer selector to verify AND semantics.
Expected: exactly the two included owner orders in deterministic ID order; a different owner produces an empty page.
Actual: the include array is ignored on HPOS and every matching owner order is returned.
Proof: subscription-order-api-regression-results.txt and subscription-order-api-regression-core-results.txt recorded FAIL: Order include filtering is bounded, deterministic and ANDed with customer identity. Pro actual items=[14386,14773,14775,14777,14810,14825,14881,14899].
Source evidence: facade sets query[include]. Installed WooCommerce src/Internal/DataStores/Orders/OrdersTableQuery.php remaps post__in to id, but has no include query-argument mapping.
Known scope: empty includes and invalid include/projection validation pass; this affects nonempty include filtering on the tested HPOS store.
Fix plan: use the supported WooCommerce include/ID argument for each store, or filter candidate IDs before pagination. Root owns the source patch. Rerun both real Pro/core matrices before closing.

[[2026-10-18]] Sun 17:15
Verified fixed on 2026-10-18: Orders query now maps include IDs through WooCommerce post__in, supported by the installed HPOS mapping. Exact two-ID results, AND customer scoping, empty includes, invalid includes and empty-result projection validation all pass in Pro and core-only matrices. Both full matrices passed 70/70; fixtures cleaned. Evidence: qa/developer-support-2026-10-18/subscription-order-api-regression-results.txt and subscription-order-api-regression-core-results.txt.
