---
title: Active renewal subscription exposes no Prorated Refund control or preview
date: 2026-08-11
watch_day: D09
task_id: 114
task_key: SLT-ADM-08
stage: D09
plan_path: qa/subscription-lifecycle-test/kanban/tasks/114-refund-a-renewal-order-gateway-refund-subscription.md
status: fixed
severity: medium
---

## Task / date / plan

- Date found: `2026-08-11`
- Watch day: `D09`
- Originating task: card `#114` / `SLT-ADM-08`
- Plan file: `qa/subscription-lifecycle-test/kanban/tasks/114-refund-a-renewal-order-gateway-refund-subscription.md`

## Affected IDs

- Subscription ID: `12234` (`SLT Renewal Price Step`, S5)
- Latest completed renewal order ID: `13593`
- Parent order ID: `12233`
- Product ID: `12087`
- Stripe transaction on renewal `13593`: `ch_3U2rceJG5OzSNVs20MQZvhWo`

## Affected WordPress user / customer

- WordPress user ID: `347`
- Login / email: `slt-core` / `slt-core@example.test`
- Role: `customer`

## Gateway, checkout, and settings context

- Gateway: Stripe test
- Checkout type: `N/A` for this admin preview; S5 is an existing paid subscription
- Relevant settings: `allow_prorated_refunds=true`, `auto_gateway_refund=true`, minimum refund amount `0`
- No setting was changed and no refund was attempted on S5.

## Exact route and browser context

- Route: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/12234`
- Browser/user context: WordPress administrator in isolated session `admin-SLT-ADM-08`
- Observation time: D09 early-morning, after the S_FEE refund legs and before S5's next natural renewal

## Reproduction steps

1. Confirm `allow_prorated_refunds` is enabled.
2. Open the ArraySubs detail route for active subscription `12234`.
3. Confirm its order history lists completed renewal order `13593` for `$20.00` and its earlier paid cycles.
4. Inspect the action area for **Prorated Refund** and search the rendered page for `Prorated` or `Refund` controls.
5. Re-read renewal order `13593`'s relationship metadata.

## Expected result

The active paid subscription should expose **Prorated Refund**. Clicking it should open the authored preview showing refund amount, unused days, and cycle days, which can then be closed without processing.

## Actual result

No **Prorated Refund** control or preview exists anywhere on the rendered detail page. The only destructive subscription action is **Cancel Subscription**. A rendered-DOM check returned `hasProratedText=false` and no matching button/link. No click or refund could be attempted.

## Concrete proof

- Full detail-page screenshot: `/home/server-manager/slt-evidence/SLT-ADM-08-06-preview.png`
- Live S5 state remained unchanged: `arraysubs-active`, `_completed_payments=7`, `_next_payment_date=2026-08-11 07:50:13`, `_last_payment_date=2026-08-10 11:44:06`, empty `_refund_history`, `_end_date`, and `_cancelled_date`.
- Renewal order `13593` is completed for `$20.00`, belongs to customer `347`, and has correct order-level `_subscription_id=12234` and `_subscription_renewal=12234`.
- The compatibility relationship used by the refund lookup is absent: `_arraysubs_subscription_id` is empty on order `13593`.
- Agent-browser console showed only normal debug/migration logs; page-errors output was empty.
- The same underlying renewal-linkage gap was first recorded in `issues/critical-plugin-SLT-ADM-06-renewal-orders-missing-arraysubs-subscription-id.md`; D09 proves its customer-visible consequence on the authored refund-preview task.

## Scope notes and counterexamples

- This is proved on active Stripe subscription `12234`, which has seven completed payments and several visible completed renewal orders; it is not an absent-history or inactive-subscription case.
- Counterexample within the same record: order-level renewal links are present and the detail page correctly renders order `13593`; the missing compatibility key/control is narrower than the whole relationship display.
- Counterexample from the task's other leg: direct WooCommerce gateway refunds on renewal order `13590` succeeded for `$4.00` and `$5.00`; this finding concerns the ArraySubs prorated-refund entry point only.
- S5 was left unchanged as required.

## Resolution — 2026-08-14

### Investigation and root cause

The original finding was valid: at capture time the refund service searched renewal orders only by
the plan-switch compatibility key `_arraysubs_subscription_id`, while order `13593` carried the
canonical renewal links `_subscription_id=12234` and `_subscription_renewal=12234`. That mismatch
made the computed React action unavailable even though the paid renewal was visible in Order History.

The current implementation has since removed that dependency as part of the related canonical
order-link/refund repair. `RefundProcessor::canRefund()` now resolves the latest settled charge with
`arraysubs_get_latest_settled_subscription_order()`, and the shared resolver recognizes parent,
renewal, line-item, migration, and reverse subscription links while checking customer identity.
No extra compatibility-meta write or backfill is required.

### Current-data qualification

Subscription `12234` is now `arraysubs-cancelled`, so the exact action is correctly unavailable on
that record under the live-status guard. Its historical order `13593` still has an empty
`_arraysubs_subscription_id`, but `arraysubs_get_subscription_ids_for_order(13593)` now resolves
`[12234]`; the repaired contract therefore works without mutating the historical order.

For the required active-state browser retest, SLT subscription `13277` was selected read-only. It
was `arraysubs-active`, had latest settled renewal order `13576`, and returned `can_refund=true`,
maximum `$30.00`, plus a successful `$25.25` proration preview at test time.

### Regression verification

- The live detail route for `13277` rendered the **Prorated Refund** action.
- Opening it rendered the shared modal with refund amount `$25.25`, reason input, unused-time
  calculation, `Cancel after refund`, and **Process Refund**.
- The modal was dismissed with Escape; **Process Refund** was never clicked. A fresh read confirmed
  `13277` remained `arraysubs-active` and had no `_refund_history` mutation.
- No console/page error occurred. Evidence:
  `/home/server-manager/slt-evidence/FIX-MEDIUM-SLT-ADM-08-proration-action.png` and
  `/home/server-manager/slt-evidence/FIX-MEDIUM-SLT-ADM-08-proration-preview.png`.
- Core owns the repaired relationship/refund contract; Pro consumes it and required no duplicate
  change. No subscription, order, gateway, setting, scheduler, or mail data was changed.
