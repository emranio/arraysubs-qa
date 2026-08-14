---
title: Zero-result subscription filters show first-product onboarding despite an existing catalog
date: 2026-08-09
watch_day: D07
task_id: 100
task_key: SLT-ADM-01
stage: D07
plan_path: qa/subscription-lifecycle-test/kanban/tasks/100-subscriptions-list-status-tabs-search-gateway.md
status: fixed
severity: low
---

## Task / date / plan

- Date found: `2026-08-09`
- Watch day: `D07`
- Originating task: card `#100` / `SLT-ADM-01`
- Plan file: `qa/subscription-lifecycle-test/kanban/tasks/100-subscriptions-list-status-tabs-search-gateway.md`

## Affected IDs

- Subscription ID(s): `N/A` — the wrong empty-state component is list-state-wide. Representative
  records proving that subscriptions exist are `12760` and `12639`.
- Order ID(s): `N/A`.
- Product ID(s): `N/A` for the rendering failure. Representative products proving that the
  catalog exists are `11927` (`SLT Daily Core`) and `12112` (`SLT Paddle Daily`).

## Affected WordPress user / customer

- WordPress user/customer ID(s): `N/A` — the defect is on the administrator's global list UI,
  not one customer's record.
- Browser login / email: `admin` / `N/A` (email was not queried).
- Role: `administrator`.

## Gateway, checkout, and settings context

- Gateway: gateway-independent. The simplest reproduction is `All Gateways` plus the empty
  `Trial (0)` tab; the same component also appears with the Paddle and PayPal zero-result filters.
- Checkout type: `N/A` — no storefront, cart, or checkout was used.
- Task-specific non-default settings: none. No settings bracket was opened and the frozen suite
  baseline remained unchanged.

## Exact routes and browser context

- Route: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions`
- Browser/user context: WordPress administrator in isolated session
  `agent-browser --session admin-SLT-ADM-01`
- Simplest failing state: blank search, `All Gateways`, `Trial (0)` selected.
- Additional failing states: exact search `SLT Daily Core`; Gateway `Paddle`; Gateway `PayPal`;
  Gateway `Stripe` plus exact excluded subscription ID `12639`.

## Reproduction steps

1. Sign in as a WordPress administrator and open the ArraySubs subscriptions list.
2. Confirm the normal list reports `All (379)` and renders existing subscription products.
3. Leave the gateway at `All Gateways` and the search blank.
4. Click the authored `Trial (0)` status tab.
5. Observe the heading, badge, explanatory copy, and action displayed in place of a normal empty
   filtered-result state.

## Expected result

A valid filter with zero matching subscriptions should show a neutral filtered empty state, such
as no subscriptions matching the current status/filter, while preserving the filtering controls.
It must not tell an administrator to create their first subscription product when products and
subscriptions already exist.

## Actual result

The zero-result state displays the badge `GET STARTED`, heading
`Create a subscription product first`, and copy stating that a subscription product must be
created before subscriptions can be managed. It also starts onboarding with
`Open Products -> Add New.` This is factually false while the same page reports `All (379)` and
existing products are visible in non-empty tabs.

## Concrete proof

- Simplest static reproduction, `Trial (0)` with `All (379)` still visible in the status bar:
  `/home/server-manager/slt-evidence/SLT-ADM-01-02-tab-trial.png`
- Same wrong component after an exact product search returns zero:
  `/home/server-manager/slt-evidence/SLT-ADM-01-04c-search-product.png`
- Same wrong component after selecting Paddle:
  `/home/server-manager/slt-evidence/SLT-ADM-01-05-gateway-paddle.png`
- Same component in the disabled-site-wide PayPal exploratory state:
  `/home/server-manager/slt-evidence/SLT-ADM-01-05-gateway-paypal.png`
- Existing-catalog counterexample: `/home/server-manager/slt-evidence/SLT-ADM-01-02-tab-active.png`
  renders subscription rows and product links such as `SLT Daily Core`, `SLT Paddle Daily`, and
  `SLT Plan Basic`.
- Supplied facts counterexamples: subscription `12760` uses product `11927`; subscription `12639`
  uses product `12112`. Source:
  `automation/logs/D07-2026-08-09-early-morning-facts.txt`.
- Network proof: the product-search and Paddle-filter zero states followed successful HTTP `200`
  list/count requests. No failed response explains the onboarding fallback.
- Console/errors: agent-browser recorded no console error and its page-errors output was empty.
- Failed network responses: none; the complete inspected task history had no HTTP `4xx`, `5xx`,
  `net::ERR`, or failed request.
- Mailpit: baseline and ending latest ID were both `5yGiRnu4Kb079ncz43EDFT`; the complete delta
  was empty. No task-attributable Mailpit message ID or subject exists.
- WP-CLI / DB / Action Scheduler: no command or mutation was needed. No scheduled action was
  executed or changed; the read-only D07 facts snapshot provides the record/product existence
  proof.
- Full execution record: `/home/server-manager/slt-evidence/SLT-ADM-01-D07-read.txt`.

## Scope notes and counterexamples

- Non-empty status tabs render the subscription table normally; the issue is the component chosen
  for zero-result states.
- The `Trial (0)` reproduction does not depend on the separate product-search or Paddle-filter
  defects, so this copy/rendering defect stands independently.
- The exact product-title search failure is tracked in
  `issues/light-plugin-SLT-ADM-01-product-title-search-returns-zero.md`.
- The Paddle filter failure is tracked in
  `issues/light-plugin-SLT-ADM-01-paddle-gateway-filter-returns-zero.md`.
- No claim is made about the correct appearance of a genuinely empty first-run installation; this
  finding is limited to a populated site with `379` subscriptions and existing products.

## Resolution — 2026-08-14

### Investigation and root cause

The current staging site still had 403 subscriptions and the unfiltered list rendered normal rows,
but both a deliberately unmatched search and the empty `Trial (0)` status rendered the first-run
product onboarding. The finding remained reproducible and was not a false positive.

`DataList` already supplied the custom renderer with the active search, status, and select-filter
values. `SubscriptionsList` ignored that context and always returned the onboarding component for
every zero-row response.

### Fix and safety review

- Updated only the core `SubscriptionsList` empty-state renderer. Any non-empty search, non-`all`
  status, or non-default gateway now renders the neutral, translatable message
  `No subscriptions match your filters` with guidance to adjust the controls.
- The existing onboarding remains intact for the genuinely unfiltered `All` state with zero rows.
  No REST contract, permission, data mutation, link target, or Pro behavior was changed.
- The implementation relies on context already passed by the shared `DataList`; it does not create
  a second state store or infer catalog existence from stale counts.

### Regression verification

- Production assets rebuilt successfully with `npm run build` (webpack exit `0`). No lint or PHPCS
  command was run, per the issue-fix workflow.
- Live authenticated browser checks proved an impossible search and blank-search `Trial (0)` both
  retained the list controls and rendered `No subscriptions match your filters`, with no onboarding
  CTA and no page error.
- Selecting `All (403)` restored the populated table and visible `SLT Daily Core` rows.
- Evidence:
  `/home/server-manager/slt-evidence/FIX-LOW-SLT-ADM-01-search-after.png` and
  `/home/server-manager/slt-evidence/FIX-LOW-SLT-ADM-01-trial-after.png`.
- Verification was read-only and produced no record, setting, scheduler, or email mutation.
