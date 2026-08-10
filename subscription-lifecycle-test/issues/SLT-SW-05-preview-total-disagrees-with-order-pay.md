# Paddle plan-switch preview quotes USD 10.00 but creates a USD 9.90 order-pay charge

- Severity: medium
- Date found: 2026-08-08
- Watch day: D06
- Originating task: `SLT-SW-05` (kanban task ID `96`)
- Plan file: `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test/kanban/tasks/096-upgrade-a-paddle-billed-ladder-subscription-and.md`

## Affected records

- Subscription: `13344`, active on `SLT Plan Basic` (`12608`) at USD 5.00/day
- Parent order: `13343`, completed, USD 5.00, Paddle transaction `txn_01kzgv8fc6kg5hcwkm42ds4a4c`
- Plan-switch proration order: `13354`, pending, USD 9.90, no transaction ID
- Target product: `12611`, `SLT Plan Pro`, USD 15.00/day
- Paddle subscription: `sub_01kzgwg0b05gx4ryprhxa32fns`
- WP user: ID `352`, login `slt-paddle`, email `slt-paddle@example.test`, role `Customer`
- Gateway: Paddle sandbox (`arraysubs_paddle`)
- Checkout type: customer portal preview followed by WooCommerce order-pay
- Non-default settings: Paddle test mode; `renewals.sync_to_billing_cycle` was OFF; neither ladder rung used flex-sync settings. No temporary settings bracket was opened.
- Action Scheduler action IDs: N/A; no scheduled action participates in the quoted switch total.

## Routes and browser context

- Preview route: `https://mirror-help.arrayhash.com/my-account/view-subscription/13344/`
- Resulting order-pay route: `https://mirror-help.arrayhash.com/checkout/order-pay/13354/`
- Browser context: authenticated customer session `cust-SLT-SW-05`
- Preview observation time T1: 2026-08-08 20:38:03 site-local (UTC+6)

## Reproduction

1. Start with active daily Paddle subscription `13344` for exact Basic product `12608` at USD 5.00.
2. At T1, open `/my-account/view-subscription/13344/` as `slt-paddle`.
3. Choose **Change Plan** → **Upgrade/Downgrade** and select exact Pro product `12611` at USD 15.00/day.
4. Record the displayed switch summary before confirming: credit USD 5.00, charge USD 15.00, and amount due USD 10.00.
5. Confirm the plan change.
6. Inspect the newly created exact proration order `13354` and its order-pay page.

## Expected result

- The confirmed order and order-pay page charge the exact amount shown in the immediately preceding preview.
- If the implementation recalculates the daily-cycle remainder at confirmation time, the preview and confirmation must use a consistent pricing instant or visibly disclose the changed amount before creating the order.

## Actual result

- At T1, the portal preview displayed credit USD 5.00, charge USD 15.00, and amount due USD 10.00.
- Confirming that preview created proration order `13354` for USD 9.90, a USD 0.10 reduction from the amount the customer had just confirmed.
- The order-pay page displayed `Plan Upgrade to SLT Plan Pro – Proration: $9.90` and `Total: $9.90`.
- The discrepancy is independently observable before the separate Paddle checkout-setup failure. Whether or not the USD 9.90 recalculation is mathematically intended, it does not match the customer-confirmed quote.

## Proof

- `/home/server-manager/slt-evidence/SLT-SW-05-02-preview.png` records the selected Pro upgrade preview state at T1; the contemporaneous browser snapshot recorded exact rows `Credit $5.00`, `Charge $15.00`, and `Due now $10.00`.
- `/home/server-manager/slt-evidence/SLT-SW-05-03-order-pay.png` shows the resulting order-pay charge of USD 9.90.
- `/home/server-manager/slt-evidence/SLT-SW-05-03a-order-pay-paddle-error.png` independently shows the USD 9.90 proration row and total after submission.
- A safe order read showed exact order `13354` with `status=pending`, `total=9.90`, `currency=USD`, `gateway=arraysubs_paddle`, customer `352`, no transaction ID, and the note `Plan switch proration order for subscription #13344 (upgrade)`.
- Parent order `13343` was completed at USD 5.00 under transaction `txn_01kzgv8fc6kg5hcwkm42ds4a4c`; subscription `13344` retained its USD 5.00 recurring amount because the proration payment did not complete.
- `SW05_PAY_PRE=6FvTzIMPjYmvIokCrDGCkQ`; Mailpit latest remained unchanged. No email introduced a corrected quote.
- No full card number, order key, Paddle credential, or management URL was retained.

## Scope and counterexamples

- The mismatch is USD 0.10 for this Basic-to-Pro daily upgrade observed roughly one minute between preview and order creation. It should not be generalized to all intervals or switch directions without further tests.
- The preview's face-value arithmetic is internally consistent: USD 15.00 charge minus USD 5.00 credit equals USD 10.00 due.
- The created order's USD 9.90 total may reflect elapsed-time proration between preview and confirmation, but the customer was not shown or asked to approve that changed total before order creation.
- The later `No valid items found for Paddle checkout` error is filed separately in `issues/SLT-SW-05-paddle-order-pay-no-valid-items.md`; it does not explain away the earlier quoted-total disagreement.
- The existing catalogue-sync issue `issues/SLT-SETUP-05-paddle-product-sync-metas-not-created.md` may contribute to the Paddle setup failure, but it does not account for the preview/order amount mismatch.
- No unrelated record was touched and no order or subscription state was manually forced.
