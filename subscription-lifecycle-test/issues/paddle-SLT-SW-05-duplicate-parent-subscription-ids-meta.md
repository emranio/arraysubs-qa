# Paddle checkout duplicated the parent order `_subscription_ids` HPOS meta row

- Severity: medium
- Date found: 2026-08-13
- Watch day: D11
- Originating test task: `SLT-SW-05` (kanban task `#96`)
- Plan file: `kanban/tasks/096-upgrade-a-paddle-billed-ladder-subscription-and.md`

## Affected records

- Subscription: `20114`
- Parent order: `20113`
- Later plan-switch order: `20124` (context only; its creation did not cause the duplicated parent relationship)
- Products: `12608` / `SLT Plan Basic` at checkout; subscription later switched to `12611` / `SLT Plan Pro`
- WordPress user: `352`, `slt-paddle`, `slt-paddle@example.test`, role `customer`
- Gateway: Paddle sandbox
- Checkout type: block checkout (page `8`), followed by the `SLT-SW-05` plan-switch regression flow
- Non-default settings: no watcher-side setting change or bracket. The concurrent fixture owner's exact temporary settings bounds were not yet published when this finding was captured; Paddle was in sandbox/test mode.

## Route and context

- Checkout route: `https://mirror-help.arrayhash.com/checkout/`
- Order verification route: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-orders&action=edit&id=20113`
- Customer context: the authored `cust-SLT-SW-05` flow as `slt-paddle`; the concurrent card owner had already closed its creation session before the D11 watcher read
- Verification context: read-only WP-CLI/SQL from the WordPress root and isolated browser session `admin-SLT-SW-05-D11-WATCH`

## Reproduction steps

1. With `slt-paddle` and an empty persistent cart, buy one `SLT Plan Basic` subscription through the block checkout using Paddle sandbox.
2. Wait for the Paddle `transaction.completed` path to complete the parent order and activate the relationship-resolved subscription. This occurrence produced parent order `20113` and sole subscription `20114`.
3. Open order `20113` in WooCommerce admin and confirm the UI shows one `SLT Plan Basic` line and one linked subscription, `#20114`.
4. Query the authoritative HPOS metadata:

   ```sql
   SELECT id, order_id, meta_key, meta_value
   FROM wp_wc_orders_meta
   WHERE order_id = 20113 AND meta_key = '_subscription_ids'
   ORDER BY id;
   ```

5. Read all values through the WooCommerce order API:

   ```php
   $order = wc_get_order( 20113 );
   $order->get_meta( '_subscription_ids', false );
   ```

6. Compare the compatibility-table relationship and other recent fresh parent orders.

## Expected result

The parent order should have exactly one authoritative `_subscription_ids` HPOS row containing the sole relationship `[20114]`. Both single-value and all-value reads should describe one relationship record.

## Actual result

`wp_wc_orders_meta` contains two distinct rows with identical values:

```text
id     order_id  meta_key           meta_value
18026  20113     _subscription_ids  a:1:{i:0;i:20114;}
18027  20113     _subscription_ids  a:1:{i:0;i:20114;}
```

`WC_Order::get_meta('_subscription_ids', true)` masks the duplication by returning `[20114]`, but `get_meta(..., false)` returns both metadata objects, IDs `18026` and `18027`. A caller that requests or iterates all relationship values can therefore process the same subscription twice.

## Concrete proof

- The compatibility `wp_postmeta` copy has exactly one row: meta ID `156639`, value `a:1:{i:0;i:20114;}`.
- Parent order `20113` is `wc-completed`, customer `352`, Paddle sandbox, USD `5.00`, created `2026-08-13 14:10:39Z`; its only line is product `12608`, quantity `1`, total `$5.00`.
- Mailpit IDs `7AfcWK6i8BkGFmmsrjeQCq` and `3KSW2jOkVH1WpjQJ7xcQe8` are the customer/admin order mails for `20113`; `2oZHJawAETBn3hKC0mJSbM` and `10Ni7USFRgUtYJ5KvYzG3E` are the customer/admin activation mails for subscription `20114`.
- Admin screenshot: `/home/server-manager/slt-evidence/SLT-SW-05-D11-order-20113-duplicate-relationship.png` shows one product line and one visible `#20114` relationship. Browser errors were empty; console output contained only JQMIGRATE.
- D11 night evidence: `/home/server-manager/slt-evidence/D11-2026-08-13-night-reconciliation.txt`.

## Scope and counterexamples

- A site-wide HPOS query over `_subscription_ids` rows on orders `>=15800` found only order `20113` with `COUNT(*) > 1`; the two rows have one distinct value. This is a fresh isolated occurrence, not a blanket claim about every parent order.
- Recent composite Paddle parent orders `17441`, `17501`, `17955`, `18648`, `19170`, and `19384` each had one compatibility-table relationship row containing their two distinct subscription IDs; no duplicate HPOS relationship row survived for them.
- The order UI and the single-value WooCommerce API read both show one subscription, so the defect is currently hidden on the normal admin surface.
- Subscription `20114` passed the separate duplicate-gateway-meta standing check: each of `_payment_gateway`, `_gateway_status`, `_gateway_customer_id`, and `_payment_method_last4` has exactly one row. This finding is specifically about the parent order relationship meta.
- The fixture belongs to concurrent card `126` regression work and is outside the published D11 lifecycle tail/cancellation cohorts. It does not alter the D11 watch verdict for subscription `12564`.
