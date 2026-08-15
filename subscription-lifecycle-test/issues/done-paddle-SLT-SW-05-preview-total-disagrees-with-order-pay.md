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
- The later `No valid items found for Paddle checkout` error is filed separately in `issues/critical-plugin-SLT-SW-05-paddle-order-pay-no-valid-items.md`; it does not explain away the earlier quoted-total disagreement.
- The existing catalogue-sync issue `issues/critical-plugin-SLT-SETUP-05-paddle-product-sync-metas-not-created.md` may contribute to the Paddle setup failure, but it does not account for the preview/order amount mismatch.
- No unrelated record was touched and no order or subscription state was manually forced.

## Resolution — 2026-08-15

The report was reproduced against the current pre-fix runtime and is a true positive. An isolated active daily subscription used the same USD 5.00 Basic-to-USD 15.00 Pro calculation as the reported Paddle record. Its REST-equivalent preview froze a USD 0.02 net at `2026-08-14 22:36:54` UTC; five seconds later the server calculation was USD 0.01, and `PlanManager::createProrationOrder()` silently recalculated and created exact order `26896` for USD 0.01. This proves the discrepancy was caused by independent wall-clock calculations, not by the later Paddle checkout failure.

The shared core switch flow now issues an opaque HMAC-authenticated quote with each preview. The 15-minute quote is bound to the current WordPress user and authenticates the subscription, current and target products, switch/proration modes, currency, quantity, price precision, current cycle boundary, both plans' prices and billing terms, credit, charge, net, refund, switch fee, and renewal price. The execute endpoint requires the token, recomputes every value server-side, and uses the echoed quote only for exact consent comparison; no client amount, source order, timestamp, or settlement value is trusted. Invalid, expired, cross-user, cross-target, or changed quotes return HTTP 409 with a fresh signed proration and create no order.

`PlanManager` performs the same exact term comparison again after acquiring the canonical source-refund and subscription locks. A cent boundary, renewal, refund, catalogue edit, or state race between REST validation and locked order creation therefore returns `plan_switch_quote_changed` before any order is created. The customer portal replaces the displayed summary with that fresh quote, restores the shared loading button, shows a translatable information toast, and requires the customer to confirm again. The existing signed version-4 order contract and provider-owned Paddle preparation remain downstream and unchanged.

Regression proof passed:

- The Pro-active deterministic matrix passed 40 assertions: authentic quote, HMAC tamper rejection, cross-user rejection, cross-target rejection, no-proration stability, apply-at-renewal terms, controlled cent-boundary 409, fresh-quote response, zero stale/tampered/missing-quote orders and claims, inside-lock stale-term rejection, exact fresh order total, signed proration equality, and duplicate-submit blocking.
- The same full order/REST matrix passed 35 assertions with `arraysubspro` skipped request-locally, proving core plan switching remains functional without Pro active.
- Live browser fixture subscription `26919`, owned by `slt-paddle` and carrying Paddle billing authorization, displayed credit USD 0.04, charge USD 0.12, and amount due USD 0.08. After the next exact cent boundary, confirmation returned `POST /wp-json/arraysubs/v1/subscriptions/26919/switch` HTTP 409, refreshed the modal to credit USD 0.04, charge USD 0.11, and amount due USD 0.07, and displayed `The price or plan details changed. Review the updated quote and confirm again.` No order-pay redirect occurred.
- Post-browser reads proved the subscription remained active on product `12608`, its pending switch-order pointer remained zero, its exact related-order set remained only source order `26922`, and it had zero plan-switch orders, zero source credit reservations, zero Paddle subscription identifier, no mail delta, and no browser errors. Network evidence recorded preview 200 and stale execute 409.
- Browser evidence: `/home/server-manager/slt-evidence/FIX-PADDLE-SLT-SW-05-quote-before.png` and `/home/server-manager/slt-evidence/FIX-PADDLE-SLT-SW-05-quote-refreshed.png`.
- The production customer-portal build and whitespace/error diff check passed. Every disposable subscription, WooCommerce order, note, scheduler action, metadata row, temporary script, and browser session was identity-checked and removed; final residue counts are all zero.

Core/Pro review found no Pro caller that constructs the core switch REST payload independently. Paddle still consumes only the later server-signed order contract, so stale quote rejection has no remote side effect and valid confirmed orders retain the existing exact Paddle settlement and recurring-plan synchronization path.
