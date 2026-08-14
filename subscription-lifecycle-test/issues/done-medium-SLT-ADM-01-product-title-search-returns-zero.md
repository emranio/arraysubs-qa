---
title: Subscription list product-title search returns zero for an existing product
date: 2026-08-09
watch_day: D07
task_id: 100
task_key: SLT-ADM-01
stage: D07
plan_path: qa/subscription-lifecycle-test/kanban/tasks/100-subscriptions-list-status-tabs-search-gateway.md
status: fixed
severity: medium
---

## Task / date / plan

- Date found: `2026-08-09`
- Watch day: `D07`
- Originating task: card `#100` / `SLT-ADM-01`
- Plan file: `qa/subscription-lifecycle-test/kanban/tasks/100-subscriptions-list-status-tabs-search-gateway.md`

## Affected IDs

- Subscription ID(s): `12760` is the primary exact counterexample. Other visible exact-title
  counterexamples include `13331`, `12719`, `12684`, `12332`, `12318`, `12221`, `12147`, and
  `12263`.
- Order ID(s): `N/A` — this is a read-only admin-list search.
- Product ID(s): `11927` (`SLT Daily Core`).

## Affected WordPress user / customer

- WordPress user ID: `353`
- Login: `slt-admincreated`
- Email: `slt-admincreated@example.test`
- Role: `customer`

## Gateway, checkout, and settings context

- Gateway: `All Gateways`; subscription `12760` has no gateway value. The failure is therefore
  not dependent on selecting Stripe or Paddle.
- Checkout type: `N/A` — no cart or checkout was used.
- Task-specific non-default settings: none. No settings bracket was opened; the frozen suite
  baseline remained unchanged. This is a read-only admin-list operation.

## Exact route and browser context

- Route: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions`
- Browser/user context: WordPress administrator in isolated session
  `agent-browser --session admin-SLT-ADM-01`
- List state: `All (379)`, `All Gateways`, exact search text `SLT Daily Core`, blank search before
  entry, no row selected.

## Reproduction steps

1. Sign in to WordPress as an administrator and open the ArraySubs subscriptions list route.
2. Leave the status at `All` and the gateway at `All Gateways`.
3. Verify an existing row such as subscription `12760` renders product `SLT Daily Core`.
4. Enter the exact product title `SLT Daily Core` in the search box labeled
   `Subscription ID, customer name, email, username...` and submit the search.
5. Observe the rendered result set.
6. Clear the search and submit again; observe that `All (379)` and the normal list return.

## Expected result

The exact product-title search should return subscriptions whose Product column is
`SLT Daily Core`, including subscription `12760`, and exclude rows for other products.

## Actual result

The exact search returns zero rows. Instead of the existing matching subscriptions, the screen
renders `GET STARTED` and the heading `Create a subscription product first`. Clearing the search
restores all `379` subscriptions.

## Concrete proof

- Failed-search screenshot:
  `/home/server-manager/slt-evidence/SLT-ADM-01-04c-search-product.png`
- Exact visible counterexample after clearing: subscription `12760`, customer
  `slt-admincreated@example.test`, product `SLT Daily Core`; it is also captured on
  `/home/server-manager/slt-evidence/SLT-ADM-01-02-tab-active.png`.
- Supplied D07 facts row: subscription `12760`, `arraysubs-active`, customer `353`, product
  `11927`, next payment `2026-09-05 13:03:41`; source:
  `automation/logs/D07-2026-08-09-early-morning-facts.txt`.
- Network proof: the browser sent an HTTP `200` list request with
  `customer_search=SLT+Daily+Core` and all six subscription statuses. The zero result therefore
  occurred on a successful response, not an HTTP failure.
- Console/errors: agent-browser recorded no console error and its page-errors output was empty.
- Failed network responses: none; the complete inspected task history had no HTTP `4xx`, `5xx`,
  `net::ERR`, or failed request.
- Mailpit: baseline and ending latest ID were both `5yGiRnu4Kb079ncz43EDFT`; the complete delta
  was empty, so this read-only search sent no mail. No task-attributable Mailpit message ID or
  subject exists.
- WP-CLI / DB / Action Scheduler: no command or mutation was needed for this browser-list defect.
  The supplied read-only D07 facts snapshot is the supporting subscription/product relationship;
  no Action Scheduler row was executed or changed.
- Full execution record: `/home/server-manager/slt-evidence/SLT-ADM-01-D07-read.txt`.

## Scope notes and counterexamples

- Exact customer email search works: `slt-admincreated@example.test` returns only subscription
  `12760`.
- Exact login search works: `slt-paddle` returns subscriptions `12639` and `13344`.
- Clearing every search restores the full list. The failure is therefore specific to the authored
  product-title search, not a total failure of the search box or list endpoint.
- The misleading zero-state copy is tracked separately in
  `issues/light-plugin-SLT-ADM-01-zero-result-shows-first-product-onboarding.md`.

## Resolution — 2026-08-14

### Investigation and root cause

The finding reproduced before the fix on the current live list: an authenticated HTTP `200`
request for `customer_search=SLT+Daily+Core` returned `X-WP-Total: 0` and the onboarding empty
state, while subscription `12760` still linked `_product_id=11927` and product `11927` still had
the exact title `SLT Daily Core`. The report is therefore not a stale-data or false-positive case.

`SubscriptionCPT::handleCustomerSearchQuery()` only resolved subscription IDs and matching users.
It never searched WooCommerce product or variation titles, despite the Product column being part
of the list and the QA contract explicitly exercising product-title search.

### Fix and safety review

- Extended the existing core REST search in
  `arraysubs/src/Features/Subscriptions/Services/SubscriptionCPT.php`; no Pro override or parallel
  endpoint exists or was needed.
- Product and variation title matches are resolved with `WP_Query`, capped at 100 IDs and limited
  to `post_title`. Their `_product_id` / `_variation_id` clauses are grouped with customer matches
  under a nested `OR`, so separately applied status and gateway clauses remain `AND` constraints.
- Exact numeric subscription-ID matches retain their existing union behavior. Empty and whitespace
  searches short-circuit safely, unmatched searches use `post__in => [0]`, and the registered REST
  parameter continues to use `sanitize_text_field`.
- The endpoint's existing WordPress REST post permissions remain unchanged. The implementation adds
  no raw SQL, output, nonce bypass, capability bypass, or customer-facing route.

### Regression verification

- WP-CLI REST dispatch as administrator returned HTTP `200` and `X-WP-Total: 33` for exact product
  title `SLT Daily Core`; the result contained subscription `12760`.
- Existing paths remained correct: exact email `slt-admincreated@example.test` returned 24 rows and
  contained `12760`; exact ID `12760` returned only `12760`; the unrelated term
  `no-such-subscription-qa` returned zero.
- Live browser retest at the reported admin route rendered product-matching rows (including the
  `SLT Daily Core` Product column) with the same successful REST response and no page error.
- Before/after screenshots:
  `/home/server-manager/slt-evidence/FIX-MEDIUM-SLT-ADM-01-before.png` and
  `/home/server-manager/slt-evidence/FIX-MEDIUM-SLT-ADM-01-after.png`.
- This was read-only verification: no subscription, order, product, user, setting, scheduled action,
  or mail state was changed.
