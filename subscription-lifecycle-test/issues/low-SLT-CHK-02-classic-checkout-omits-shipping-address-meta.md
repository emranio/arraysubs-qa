# Classic subscription checkout omits the subscription shipping-address snapshot

- **Severity:** low
- **Status:** open; documented, non-blocking
- **QA progress task / stage:** board task `#2`, `SLT-CHK-02`, D0
- **QA plan file:** `qa/subscription-lifecycle-test/kanban/tasks/002-classic-checkout-parity-same-slt-daily-core.md`

## Affected objects and user

| Field | Value |
|---|---|
| Subscription ID | `11991` |
| Order ID | `11990` |
| Product ID | `11927` (`SLT Daily Core`, virtual) |
| WordPress user | ID `357`; `slt-core2`; `slt-core2@example.test`; role `customer` |
| Browser context | agent-browser session `cust-SLT-CHK-02` |
| Route | `https://mirror-help.arrayhash.com/slt-classic-checkout/` |

## Reproduction

1. Create a customer whose billing and shipping profiles both contain the same complete Bangladesh address.
2. Log in as that customer and add `SLT Daily Core` to the cart.
3. Complete the classic `[woocommerce_checkout]` surface using Stripe `4242`.
4. Dump all postmeta for the new subscription.

## Expected result

The classic and block checkout surfaces persist the same subscription address snapshots for equivalent customer data, including `_shipping_address`.

## Actual result and proof

Subscription `11991` contains the complete `_billing_address` snapshot but no `_shipping_address` row. The
control block-checkout subscription `11959` contains both snapshots. The customer's live billing and shipping
profiles both had `SLT Core`, `1 SLT Way`, `Dhaka`, `BD-13`, `1207`, `BD` before checkout.

Evidence:

- `/home/server-manager/slt-evidence/SLT-CHK-01-sub-meta.txt`
- `/home/server-manager/slt-evidence/SLT-CHK-02-sub-meta.txt`
- `/home/server-manager/slt-evidence/SLT-CHK-02-diff.txt`
- `/home/server-manager/slt-evidence/SLT-CHK-02-03-received.png`

## Scope notes and counterexample

- Block checkout subscription `11959` is the direct counterexample: it persists `_shipping_address` for the same virtual product and equivalent address data.
- The product is virtual, so this omission did not affect the current charge, order completion, renewal schedule, billing snapshot, or portal behavior.
- All 25 normalized subscription billing, product, gateway and scheduling invariant values matched between subscriptions `11959` and `11991`.
