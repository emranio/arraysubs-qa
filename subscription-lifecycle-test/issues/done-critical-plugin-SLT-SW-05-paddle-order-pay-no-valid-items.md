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

## Resolution — 2026-08-14

### Confirmed root cause

The report was a true positive. Paddle checkout item construction only translated WooCommerce line items. Immediate plan-switch proration orders are intentionally fee-only audit orders, so exact order `13354` had a valid signed amount but zero line items. The checkout adapter therefore discarded the charge and returned `No valid items found for Paddle checkout.`

The investigation also found that simply translating the fee would have been unsafe. A payable switch needs a frozen, authenticated lifecycle contract so a stale order, altered amount, reused source charge, provider credit, late webhook, refund race, or concurrent lifecycle mutation cannot switch the wrong plan or create an unverified charge/refund.

### Fix plan and implementation

- Core now creates and authenticates a version-4 switch contract containing the exact customer-gross proration basis, paid source order and transaction, UTC cycle boundaries, quantity, source-credit reservation, target plan, currency, gateway, and settlement amount.
- Proration uses exact elapsed seconds and calendar-safe boundaries instead of nominal 30/365-day assumptions. Same-cycle A→B→C switches carry forward only the authenticated remaining cycle value, and equal-value switches create a signed zero-payment audit settlement instead of mutating the subscription without evidence.
- Switch creation, stale-order release, finalization, compensation, accounting-only refunds, and lifecycle conflicts use the canonical subscription/payment/refund locks and fail closed on partial lock ownership or invalid evidence.
- Ordinary WooCommerce refunds are refused for every signed plan-switch order. Only the protected core compensation/accounting paths can create a refund after exact contract, amount, transaction, ownership, and reservation verification.
- Paddle now converts one authenticated fee-only switch contract into exactly one quantity-1, sandbox/production-scoped, immutable one-time price. Its v2 price contract fixes the signed gross amount and uses `tax_mode=internal`; checkout re-verifies the remote price and transaction line before opening.
- Paddle settlement verifies canonical totals (`subtotal + tax = total`), credit-to-balance, balance, captured cash, currency, item hash, customer, origin, collection mode, custom data, and independent API/webhook copies. Customer-credit-backed or ambiguous refund states stop for durable manual review rather than being falsely marked compensated.
- After local finalization, the same remote Paddle subscription is aligned to the target recurring price with the original billing boundary. Recovery covers abandoned, completed, late-charge, response-lost, partial-finalization, and refund-race states idempotently.
- Adjacent retention actions now share lifecycle locks, reject pending switches/unresolved renewal invoices, verify the exact authoritative discounted order before recording acceptance, and fail closed when persisted automatic-provider context cannot be serviced.
- The implementation stays split by ownership: shared contracts, proration, lifecycle/refund guards, and retention behavior are in `arraysubs`; Paddle catalog, checkout, settlement, recovery, and remote alignment remain in `arraysubspro`. Core behavior was also exercised with non-Paddle gateways.

### Deterministic verification

- Contract hardening: `46/46` passed, including tax-exclusive/inclusive customer-gross values, leap/calendar boundaries, late payment, A→B→C carry-forward, equal-value settlement, stale release tombstones, and lock/refund scope.
- Paid-source settlement: `44/44` passed.
- Broad Paddle recovery matrix: `315/315` passed with eight expected protected refunds and zero remaining subscriptions, orders, refunds, notes, or scheduled actions.
- Paddle settlement: `48/48`; provider refund fence: `15/15`; adjustment pagination: `17/17` across ten requests; checkout JavaScript: `23/23`.
- All-gateway ordinary-refund guard: `32/32`; refund reservation barrier: PASS.
- Retention offer security: `37/37`; downgrade security: `17/17`; lifecycle guard: `27/27`, with clean shutdown and no fixtures.
- Targeted PHP syntax checks, JavaScript syntax, scoped `git diff --check`, and both production webpack builds passed. PHPCS was intentionally skipped per the QA issue-fix workflow.
- Independent Paddle, contract, refund, retention, and recovery reviews found no remaining high/critical blocker. A stale recovery fixture initially omitted the stricter paid-cycle, price, settlement, and adjustment evidence; after updating only that test payload to the current production contract, its complete matrix passed without any production relaxation.

### Fresh live staging proof

Browser session: `cust-SLT-SW-05-FINAL`; evidence: `/home/server-manager/slt-evidence/SW05-final/`.

1. A narrowly bracketed settings change excluded only products `12608` and `12611` from the active private-store rule and temporarily set renewal billing-cycle sync off so the authored Paddle checkout was eligible. The complete ArraySubs settings hash before/after was `7f1af9d9c8ee89b3f4dcba0d49eda75b59d8efe426f9fea0977add9f4aa8052a`; the Paddle settings hash remained `70f099aba7fcd98135d83cdd491563b8a8956432375f8a73384c43e26e3870cc`.
2. The real block checkout completed parent order `26057` for USD `5.00` and activated subscription `26058` on Basic. Paddle transaction `txn_01kzywjk4gvsa8v61m63cd07ge` settled `417 + 83 tax = 500`, captured `500`, and created active remote subscription `sub_01kzywp8289np8dq8hg89v629t` at recurring price `500` with `next_billed_at=2026-08-15T01:02:54.626538Z`.
3. The customer selected Pro in the real portal. Fee-only switch order `26068` had zero line items, one non-taxable proration fee, total USD `9.97`, and an authenticated version-4 contract: source order `26057`, source gross `5.00`, remaining credit `4.99`, target-cycle charge `14.96`, net `9.97`, quantity `1`, customer-gross basis, and target recurring price `15.00`.
4. The order-pay page displayed the historical zero subtotal plus the valid USD `9.97` proration fee. Submitting it opened the hosted Paddle overlay with a real item—no `No valid items` error and no native browser dialog. Empty-field screenshots were captured before payment; populated hosted payment fields are absent from the evidence files.
5. Paddle transaction `txn_01kzywzxsghkrb7khjjwmxd1w0` completed for the exact one-time v2 price `pri_01kzywwvshandc68qbm6hy0w7r`: `831 + 166 tax = 997`, grand total/captured cash `997`, credit `0`, balance `0`, quantity `1`, `tax_mode=internal`.
6. Order `26068` completed once with its processed contract. Subscription `26058` changed to Pro at USD `15.00/day`, retained the exact local billing boundary, cleared pending-switch state, and remained linked to the same Paddle subscription. The remote subscription changed to target recurring price `pri_01kzxqr2ve3gms7dtz0wxsy200` at `1500`, quantity `1`, while preserving `next_billed_at=2026-08-15T01:02:54.626538Z`.
7. Mailpit showed only the expected WooCommerce customer/admin order pair for switch order `26068`; no lifecycle/plan-switch mail was emitted. Browser errors were empty. The only console diagnostic was WooCommerce's pre-existing dependency warning, with no checkout exception.

### Teardown

- The portal cancellation flow was exercised and local subscription `26058` reached Cancelled. Its exact Paddle subscription was then confirmed `canceled` with no scheduled change before local deletion.
- Exact disposable orders `26057`/`26068`, subscription `26058`, their notes, and all exact Action Scheduler rows were deleted. User `352` again has zero subscriptions and an empty browser/persistent cart.
- Products and their valid environment-scoped Paddle catalogue caches were retained. No unrelated user, product, order, subscription, provider object, or setting was changed.
