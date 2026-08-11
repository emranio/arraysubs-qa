---
title: SLT-ADM-06 renewal orders lack _arraysubs_subscription_id item meta used by refund lookups
date: 2026-08-05
task_id: 48
task_key: SLT-ADM-06
stage: D03
plan_path: qa/subscription-lifecycle-test/kanban/tasks/048-renewal-orders-are-correctly-typed-and-linked-to.md
status: open
severity: high
---

## Task / stage / plan

- QA progress task: `#48` / `SLT-ADM-06`
- Stage: `D03`
- Plan path: `qa/subscription-lifecycle-test/kanban/tasks/048-renewal-orders-are-correctly-typed-and-linked-to.md`

## Affected IDs

- Subscription ID(s): `11959`
- Order ID(s): `12276`, `12426`
- Related parent / pending order ID(s): `11949`, `12604`
- Product ID(s): `11927` (`SLT Daily Core`)

## Affected user / customer context

- WordPress user ID(s): `347`
- Login / email: `slt-core` / `slt-core@example.test`
- Role(s): `customer`

## Gateway, checkout, and settings context

- Gateway: `stripe`
- Checkout type: prior storefront purchase control path; this audit itself is read-only
- Task-specific temporary settings: none
- Window-wide non-default settings in play: none relevant to this read-only audit

## Exact routes / browser context

- Browser context: `agent-browser --session admin-SLT-ADM-06`
- Subscription detail route: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/11959`
- Orders list route: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-orders&_customer_user=347`
- Renewal order routes:
  - `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-orders&action=edit&id=12276`
  - `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-orders&action=edit&id=12426`
- Parent order route:
  - `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-orders&action=edit&id=11949`

## Reproduction steps

1. Open the real subscription detail route for `#11959` and confirm the order relationship block lists the completed renewal orders `#12276` and `#12426`.
2. Open the HPOS order edit pages for `#12276` and `#12426` and confirm both orders belong to customer `SLT Core (#347)` and are linked to ArraySubs subscription `#11959`.
3. From WP root, read the order-level renewal linkage metas from HPOS:
   - `_is_renewal_order=yes`
   - `_subscription_id=11959`
   - `_subscription_renewal=11959`
4. From WP root, inspect each renewal order's line items:

   ```bash
   wp eval 'foreach ([12276,12426] as $order_id) { $order = wc_get_order($order_id); echo "ORDER=$order_id\n"; foreach ($order->get_items() as $item_id => $item) { $legacy = $item->get_meta("_subscription_id", true); $compat = $item->get_meta("_arraysubs_subscription_id", true); echo "item=$item_id legacy=" . ($legacy === "" ? "<empty>" : $legacy) . " compat=" . ($compat === "" ? "<empty>" : $compat) . "\n"; } }' --allow-root
   ```

5. Compare that result with the authored task note that refund lookup helpers search renewal orders by `_arraysubs_subscription_id`.

## Expected result

- If refund lookup helpers depend on `_arraysubs_subscription_id`, completed renewal-order line items should carry that compatibility key with the real subscription ID.
- At minimum, the data written on renewal orders and the lookup strategy should agree.

## Actual result

- Both completed Stripe renewal orders are correctly linked at the order/meta level, but neither order item carries `_arraysubs_subscription_id`.
- The direct line-item inspection for both completed renewals returned empty values for both the legacy `_subscription_id` item meta and the compatibility `_arraysubs_subscription_id` item meta:

  ```text
  ORDER=12276
  item=638 legacy=<empty> compat=<empty>
  ORDER=12426
  item=651 legacy=<empty> compat=<empty>
  ```

## Concrete proof

- Browser proof:
  - `/home/server-manager/slt-evidence/SLT-ADM-06-04-related-orders.png`
  - `/home/server-manager/slt-evidence/SLT-ADM-06-01-hpos-list.png`
  - `/home/server-manager/slt-evidence/SLT-ADM-06-02-renewal-R1.png`
  - `/home/server-manager/slt-evidence/SLT-ADM-06-02a-renewal-R2.png`
  - `/home/server-manager/slt-evidence/SLT-ADM-06-03-parent-P1.png`
- HPOS order/meta proof is preserved in `/home/server-manager/slt-evidence/SLT-ADM-06-meta.txt`, including:
  - `12276 _is_renewal_order=yes`
  - `12276 _subscription_id=11959`
  - `12276 _subscription_renewal=11959`
  - `12426 _is_renewal_order=yes`
  - `12426 _subscription_id=11959`
  - `12426 _subscription_renewal=11959`
- Read-only mail boundary proof:
  - baseline `ADM06_CORE_PRE=65pK2vT5zGVU7UHiyphXFF`
  - latest Mailpit ID after the task is unchanged: `65pK2vT5zGVU7UHiyphXFF`
  - zero task-attributable mail

## Known scope / counterexamples

- This issue is proved on the completed Stripe renewal orders `12276` and `12426` for subscription `11959`.
- Counterexample on the same data set: order-level renewal linkage is present and correct in HPOS and the admin UI relationship block also renders the linkage correctly.
- The authored Paddle counterexample branch is not available yet on 2026-08-05; it becomes testable only after the first real `SLT Paddle Daily` renewal settles on the D4/D5 handoff path.

## D07 Paddle counterexample — 2026-08-09

The missing item linkage is not Stripe-specific. Paddle invoice action `16096` pre-created renewal order `13462` for subscription `13344`; the natural remote transaction later completed it for `$5.00`. Its sole item `730` is visibly `SLT Plan Basic`, quantity `1`, `$5.00`, while both item metas remain absent: `_subscription_id=<empty>` and `_arraysubs_subscription_id=<empty>`. Order-level `_subscription_id=13344` and `_subscription_renewal=13344` remain correct. Full structured and browser proof: `/home/server-manager/slt-evidence/SLT-SW-05-D07-natural-basic-renewal.txt` and `/home/server-manager/slt-evidence/SLT-SW-05-D07-order-13462.png`.

## D08 Paddle recurrence — 2026-08-10

The next pre-created renewal order for the same independent Paddle subscription reproduces
the item-linkage gap. Order `13605` is completed for `$5.00`, order-level reverse links are
both `13344`, and item `744` is the sole `SLT Plan Basic` / product `12608` / quantity-one
line. Item `744` nevertheless has neither `_subscription_id` nor
`_arraysubs_subscription_id`. Evidence:
`/home/server-manager/slt-evidence/D08-2026-08-10-night-natural-gates.txt`.

## D09 Paddle recurrence — 2026-08-11

The next pre-created renewal order reproduces the same item-linkage gap. Order `13758` is
completed for `$5.00`, and order-level `_subscription_id` / `_subscription_renewal` both
equal `13344`. Sole line item `759` is `SLT Plan Basic`, product `12608`, quantity `1`, and
line total `$5.00`, but it has neither `_subscription_id` nor
`_arraysubs_subscription_id`. Proof:
`/home/server-manager/slt-evidence/SLT-SW-05-D09-natural-basic-renewal.txt` and
`/home/server-manager/slt-evidence/SLT-SW-05-D09-order-13758.png`.
