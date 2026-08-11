# Paddle plan-switch order-pay cannot start checkout because it finds no valid items

- Severity: high
- Date found: 2026-08-08
- Watch day: D06
- Originating task: `SLT-SW-05` (kanban task ID `96`)
- Plan file: `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test/kanban/tasks/096-upgrade-a-paddle-billed-ladder-subscription-and.md`

## Affected records

- Subscription: `13344`, `arraysubs-active`, initially and still `SLT Plan Basic` (`12608`) at USD 5.00/day
- Parent order: `13343`, completed, USD 5.00, Paddle transaction `txn_01kzgv8fc6kg5hcwkm42ds4a4c`
- Plan-switch proration order: `13354`, pending, USD 9.90, no transaction ID
- Products: `12608` (`SLT Plan Basic`) and `12611` (`SLT Plan Pro`)
- Paddle subscription: `sub_01kzgwg0b05gx4ryprhxa32fns`
- WP user: ID `352`, login `slt-paddle`, email `slt-paddle@example.test`, role `Customer`
- Gateway: Paddle sandbox (`arraysubs_paddle`)
- Checkout type: block checkout for the initial purchase; WooCommerce order-pay for the switch proration
- Non-default settings: Paddle test mode; `renewals.sync_to_billing_cycle` was OFF; neither ladder rung used flex-sync settings. No temporary settings bracket was opened.
- Action Scheduler action IDs: N/A; this checkout path did not authorize or run a scheduled action.

## Routes and browser context

- Initial checkout: `https://mirror-help.arrayhash.com/checkout/`
- Subscription portal: `https://mirror-help.arrayhash.com/my-account/view-subscription/13344/`
- Failing payment route: `https://mirror-help.arrayhash.com/checkout/order-pay/13354/`
- Browser context: authenticated customer session `cust-SLT-SW-05`

## Reproduction

1. Log in as `slt-paddle` and buy exact product `12608`, `SLT Plan Basic`, through the block checkout with Paddle sandbox.
2. Wait for Paddle's webhook to complete parent order `13343` and activate its sole linked subscription `13344`.
3. Verify that subscription `13344` is linked back to parent order `13343`, customer `352`, product `12608`, and Paddle subscription `sub_01kzgwg0b05gx4ryprhxa32fns`.
4. Open `/my-account/view-subscription/13344/`, choose **Change Plan**, choose **Upgrade/Downgrade**, select exact product `12611`, `SLT Plan Pro`, and confirm the change.
5. Follow the generated payment route for exact proration order `13354`.
6. Select Paddle and submit payment once. Observe the inline error.
7. Reload the same order-pay route and retry once. Inspect the browser request log, order state, subscription state, Paddle API state, and Mailpit delta.

## Expected result

- The Paddle order-pay page creates a valid sandbox transaction for proration order `13354` and opens the hosted Paddle checkout.
- Successful settlement completes order `13354`, processes the upgrade, and changes subscription `13344` locally to `SLT Plan Pro` at USD 15.00/day.
- The switch branch can then compare the updated local subscription to the remote Paddle subscription as authored.

## Actual result

- Both the first submit and the reload/retry POST returned HTTP 200, but neither produced a Paddle transaction-checkout request.
- The browser instead rendered the exact alert: `Paddle checkout setup failed: No valid items found for Paddle checkout.`
- The order-pay table contained a USD 9.90 `Plan Upgrade to SLT Plan Pro – Proration` charge, but its product subtotal was USD 0.00.
- Proration order `13354` remained `pending` with no transaction ID. Its notes remained `Proration order awaiting manual payment from customer or admin.` and `Plan switch proration order for subscription #13344 (upgrade)`.
- Subscription `13344` remained active on Basic product `12608` at USD 5.00; `_arraysubs_switch_processed` remained empty. It was not partially switched.
- The Paddle subscription remained unchanged: status `active`, unit price `500` USD, and `next_billed_at=2026-08-09T14:30:08.245157Z`.
- Product `12611` still had empty `_arraysubs_gateway_paddle_product_id`, `_arraysubs_gateway_paddle_price_id`, and `_arraysubs_gateway_paddle_synced_at` metadata. This matches the catalogue-sync failure tracked in `issues/critical-plugin-SLT-SETUP-05-paddle-product-sync-metas-not-created.md` and is a likely contributing condition; it is not proof of root cause.
- The remote-renewal comparison branch is `UNVERIFIED (switch payment unavailable)` because the authored rules prohibit manually completing the order or editing subscription/gateway metadata.

## Proof

- `/home/server-manager/slt-evidence/SLT-SW-05-02-preview.png` shows the selected Pro upgrade before confirmation.
- `/home/server-manager/slt-evidence/SLT-SW-05-03-order-pay.png` shows exact order-pay order `13354`, Paddle selected, and the USD 9.90 proration total before submission.
- `/home/server-manager/slt-evidence/SLT-SW-05-03a-order-pay-paddle-error.png` shows the exact Paddle error, zero product subtotal, USD 9.90 proration row, and pending payment surface.
- `/home/server-manager/slt-evidence/SLT-SW-05-04-cart-empty.png` shows the customer browser cart empty after the failed attempt; the serialized persistent cart was also empty.
- Safe order reads showed parent order `13343` completed at USD 5.00 with transaction `txn_01kzgv8fc6kg5hcwkm42ds4a4c`, while exact proration order `13354` remained pending at USD 9.90 without a transaction ID.
- Safe subscription/meta reads showed `post_status=arraysubs-active`, `_product_id=12608`, `_recurring_amount=5`, `_parent_order_id=13343`, `_customer_id=352`, `_gateway_paddle_subscription_id=sub_01kzgwg0b05gx4ryprhxa32fns`, `_next_payment_date=2026-08-09 14:30:08`, and empty `_arraysubs_switch_processed`.
- `SW05_PAY_PRE=6FvTzIMPjYmvIokCrDGCkQ`; Mailpit latest remained the same after both attempts, proving no task-attributable switch/order mail was sent.
- Browser error collection was empty. The captured request log showed the two HTTP 200 order-pay POST responses and no Paddle sandbox transaction-checkout request.
- The customer session `cust-SLT-SW-05` was closed. No full card number, order key, Paddle credential, or management URL was retained.

## Scope and counterexamples

- Paddle is not globally unavailable: the same session's initial block checkout settled exact product `12608`; Paddle completed order `13343`, created subscription `13344`, and returned a live remote subscription.
- Initial checkout synchronized Basic product `12608` sufficiently to populate its Paddle product/price metadata. The target Pro product `12611` remained unsynchronized, narrowing the failure to the switch/order-pay target-item path rather than authentication or customer eligibility.
- The switch preview and proration order were created, so portal selection and server-side order creation ran. Failure begins when the Paddle order-pay surface tries to translate that proration order into valid Paddle checkout items.
- The browser and persistent carts were empty before and after the test, excluding a mixed-cart contaminant.
- No unrelated product, order, subscription, coupon, or user was modified. No order or subscription state was manually forced.
