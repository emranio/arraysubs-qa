---
title: Paddle gateway filter returns zero despite existing Paddle subscriptions
date: 2026-08-09
watch_day: D07
task_id: 100
task_key: SLT-ADM-01
stage: D07
plan_path: qa/subscription-lifecycle-test/kanban/tasks/100-subscriptions-list-status-tabs-search-gateway.md
status: closed
severity: medium
resolved_at: 2026-08-14
---

## Task / date / plan

- Date found: `2026-08-09`
- Watch day: `D07`
- Originating task: card `#100` / `SLT-ADM-01`
- Plan file: `qa/subscription-lifecycle-test/kanban/tasks/100-subscriptions-list-status-tabs-search-gateway.md`

## Affected IDs

- Subscription ID(s): `12639` and `13344`, both active Paddle subscriptions visible before the
  gateway filter is applied.
- Order ID(s): `N/A` — this is a read-only admin-list filter.
- Product ID(s): `12112` (`SLT Paddle Daily`) for subscription `12639`; `12608`
  (`SLT Plan Basic`) for subscription `13344`.

## Affected WordPress user / customer

- WordPress user ID: `352`
- Login: `slt-paddle`
- Email: `slt-paddle@example.test`
- Role: `customer`

## Gateway, checkout, and settings context

- Gateway: Paddle sandbox; supplied facts store gateway `arraysubs_paddle` on subscriptions
  `12639` and `13344`.
- Checkout type: `N/A` — no checkout was performed during this admin filter test.
- Task-specific non-default settings: none. No settings bracket was opened and the frozen suite
  baseline was not changed.

## Exact route and browser context

- Route: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions`
- Browser/user context: WordPress administrator in isolated session
  `agent-browser --session admin-SLT-ADM-01`
- Failing state: blank search, Gateway `Paddle`, all statuses selected, no row selected.
- Control states: blank search with `All Gateways`; then Gateway `Stripe`; finally reset to
  `All Gateways`.

## Reproduction steps

1. Sign in as a WordPress administrator and open the ArraySubs subscriptions list.
2. With Gateway set to `All Gateways`, enter exact login `slt-paddle` and submit.
3. Observe active subscriptions `12639` (`SLT Paddle Daily`) and `13344` (`SLT Plan Basic`).
4. Clear the search and verify the full `All (379)` list returns.
5. Select `Paddle` in the Gateway filter while leaving the search blank.
6. Observe every status count and the rendered result set.
7. Select `Stripe` as a control, then search exact subscription ID `12639`.
8. Observe that Stripe correctly excludes `12639`; reset the gateway to `All Gateways`.

## Expected result

The Paddle filter should surface Paddle subscriptions, including exact `SLT Paddle Daily`
subscription `12639` and Paddle subscription `13344`, while excluding Stripe-only rows.

## Actual result

Selecting Paddle changes every status count to zero: `All (0)`, `Active (0)`, `Pending (0)`,
`On Hold (0)`, `Cancelled (0)`, `Expired (0)`, and `Trial (0)`. No Paddle subscription is
rendered, even though both exact records appeared in the preceding login search.

## Concrete proof

- Failing Paddle state:
  `/home/server-manager/slt-evidence/SLT-ADM-01-05-gateway-paddle.png`
- Exact-login precondition proving both records exist:
  `/home/server-manager/slt-evidence/SLT-ADM-01-04a-search-login.png`
- Stripe control and exact exclusion of `12639`:
  `/home/server-manager/slt-evidence/SLT-ADM-01-05-gateway-stripe.png`
  and
  `/home/server-manager/slt-evidence/SLT-ADM-01-05-gateway-stripe-excludes-12639.png`
- Supplied D07 facts rows:
  - `12639`, `arraysubs-active`, customer `352`, product `12112`, gateway
    `arraysubs_paddle`, next payment `2026-08-10 10:20:38`.
  - `13344`, `arraysubs-active`, customer `352`, product `12608`, gateway
    `arraysubs_paddle`, next payment `2026-08-09 14:30:08`.
  Source: `automation/logs/D07-2026-08-09-early-morning-facts.txt`.
- Network proof: both
  `/wp-json/wp/v2/arraysubs_data?...&gateway=paddle&...` and
  `/wp-json/arraysubs/v1/status-counts/arraysubs_data?gateway=paddle` returned HTTP `200`, but
  the UI rendered zero rows and zero counts.
- Console/errors: agent-browser recorded no console error and its page-errors output was empty.
- Failed network responses: none; the failure is the successful `200` Paddle-filter result, not a
  transport failure. The complete inspected task history had no HTTP `4xx` or `5xx`.
- Mailpit: baseline and ending latest ID were both `5yGiRnu4Kb079ncz43EDFT`; the complete delta
  was empty. No task-attributable Mailpit message ID or subject exists.
- WP-CLI / DB / Action Scheduler: no command or mutation was needed. The supplied read-only D07
  facts snapshot proves the exact gateway relationships; no scheduled action was executed or
  changed.
- Full execution record: `/home/server-manager/slt-evidence/SLT-ADM-01-D07-read.txt`.

## Scope notes and counterexamples

- The same list and exact-login search surface both affected subscriptions when Gateway is
  `All Gateways`; this is not an absent-fixture case.
- Stripe filtering is non-empty (`All 44`) and excludes exact Paddle subscription `12639`, so the
  gateway control itself can apply a filter and the Stripe branch behaves coherently.
- PayPal returned zero during the exploratory control but is disabled site-wide; no PayPal defect
  is asserted.
- The misleading empty-state copy shown after the zero result is tracked separately in
  `issues/light-plugin-SLT-ADM-01-zero-result-shows-first-product-onboarding.md`.

## Investigation and root cause

The defect remained reproducible on 2026-08-14. Before the fix, selecting Paddle sent
`gateway=paddle` to both REST endpoints and the live browser again rendered `All(0)` while the
requests returned HTTP `200`. A direct database inventory showed the actual gateway distribution:
`stripe` (`44` subscriptions), `bacs` (`37`), `arraysubs_paddle` (`11`), `cheque` (`1`), and
`slt_test_gateway` (`1`). Both affected subscriptions still existed and stored
`_payment_gateway=arraysubs_paddle`; their lifecycle status had since advanced to cancelled.

The mismatch existed in three aligned places:

1. `SubscriptionsList.jsx` used the display shorthand `paddle` as the request value although the
   Paddle WooCommerce gateway's real ID is `arraysubs_paddle`.
2. `SubscriptionCPT::handleGatewayQuery()` stripped an `arraysubs_` prefix and therefore could
   never exactly match the canonical Paddle value even if a caller supplied it.
3. `CustomEndpoints::get_status_count_meta_query()` repeated the same prefix stripping, explaining
   why both the list and every status count failed together.

Stripe was the valid counterexample because its actual stored gateway ID is `stripe`; it only
appeared to prove that the shorthand conversion was safe for every gateway.

## Fix

- The admin filter now submits the exact registered IDs `arraysubs_paddle` and
  `arraysubs_paypal`; Stripe remains `stripe`.
- The collection and status-count handlers retain the sanitized request value and compare it
  exactly with `_payment_gateway`. The two endpoints therefore use the same gateway contract.
- The existing production admin bundle was rebuilt successfully. No new gateway registry,
  database migration, or Pro dependency was introduced into core.

Exact matching is intentional: it prevents a filter for one gateway from leaking rows owned by a
different gateway with a similar suffix. WordPress sanitizes the parameter with `sanitize_key`,
`WP_Query` performs the metadata comparison, and the custom count endpoint continues to require
`manage_woocommerce` or `manage_options`.

## Regression verification

Verification completed on the live staging site on 2026-08-14:

1. An authenticated REST request for `gateway=arraysubs_paddle` returned HTTP `200`, total `11`,
   and IDs `20114`, `13344`, `12639`, `7872`, `7868`, `7852`, `7834`, `7809`, `7804`, `7755`, and
   `7737`.
2. Paddle status counts returned `All 11 = Active 1 + Cancelled 10`; all other Paddle statuses were
   zero. The independently queried Stripe control remained `All 44 = Active 13 + Cancelled 30 +
   Expired 1`.
3. An unauthenticated request to the custom status-count endpoint returned HTTP `401` with
   `rest_forbidden`, confirming that the fix did not weaken its capability boundary.
4. In isolated browser session `admin-paddle-adm01`, selecting Paddle rendered all `11` Paddle
   records. The visible rows included both reported IDs `13344` and `12639`, and the network showed
   HTTP `200` for both canonical `gateway=arraysubs_paddle` requests.
5. Selecting Stripe immediately returned `All(44)` and issued the unchanged
   `gateway=stripe` requests, proving the dependent control still works.
6. Browser errors were empty. Console output contained only JQMIGRATE and expected DataList total
   diagnostics (`403`, `11`, and `44`); no request failed.
7. Mailpit's latest ID remained `1zPxE6FmuLNdLZQPE1aist`, so this read-only regression emitted no
   mail. No relevant PHP warning, fatal, or parse error was added.

Evidence:

- Before: `/home/server-manager/slt-evidence/FIX-PADDLE-SLT-ADM-01-before.png`
- Fixed Paddle filter: `/home/server-manager/slt-evidence/FIX-PADDLE-SLT-ADM-01-after.png`
- Stripe control: `/home/server-manager/slt-evidence/FIX-PADDLE-SLT-ADM-01-stripe-control.png`

Core/Pro review: the reusable REST filtering and shared admin UI remain in ArraySubs; Paddle's
premium gateway implementation and its `arraysubs_paddle` identity remain unchanged in
ArraySubsPro. Core does not instantiate or call Pro code, so it continues to load without the
addon; when Pro is present, the filter now honors its real gateway contract.
