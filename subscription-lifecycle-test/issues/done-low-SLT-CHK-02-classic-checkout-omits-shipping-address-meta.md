# Classic subscription checkout omits the subscription shipping-address snapshot

- **Severity:** low
- **Status:** closed; fixed and verified on 2026-08-14
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

## Investigation and root cause

- The report is genuine. Historical classic order `11990` has `created_via=checkout`,
  `needs_shipping_address=false`, `has_shipping_address=false`, and no shipping
  field values. Its authenticated owner still has the complete saved shipping
  profile documented above.
- Block-checkout control order `11949` has `created_via=store-api` and retained
  that profile on the order despite using the same virtual product. Subscription
  `11959` therefore received `_shipping_address`, while classic subscription
  `11991` did not.
- Both checkout surfaces call the shared
  `SubscriptionCreationTrait::storeSubscriptionAddresses()` method. It persisted
  shipping only when `WC_Order::has_shipping_address()` was true, so it could not
  bridge WooCommerce's surface-specific virtual-order behavior.
- Renewal-order creation consumes the subscription snapshot; the pro shipping
  module separately decides whether a product needs delivery. Neither needs a
  contract change for this consistency fix.

## Fix

`arraysubs/src/Features/SubscriptionCheckout/Services/Traits/SubscriptionCreationTrait.php`
now keeps the checkout order as the authoritative shipping source. If and only
if that order has no deliverable shipping address and belongs to an authenticated
customer, it reads the customer's saved WooCommerce shipping profile. A snapshot
is written only when `address_1` or `address_2` is non-empty.

This deliberately does not merge fields, synthesize shipping from billing, or
write name-only/country-only data. Existing physical/order-level shipping wins
unchanged. Values continue to come from WooCommerce getters and are serialized
with `wp_json_encode()`; no endpoint, permission, nonce, capability, or customer
ownership contract changed. The implementation is shared core behavior, while
ArraySubs Pro continues to consume the same snapshot and shipping contracts.

## Verification

- Pre-fix admin browser verification of historical subscription `11991` showed
  its full billing address and `No shipping address on file`.
- A real authenticated classic shortcode checkout was run for virtual product
  `11927` using BACS. A later members-only QA rule blocks normal customers from
  repurchasing the historical product, so the staging administrator's separate
  frontend session exercised the privileged-user bypass already defined by that
  rule; no access setting or product was changed.
- The browser created exact order `26774` and linked subscription `26775`.
  Order facts were `created_via=checkout`, customer `1`, product `11927`, status
  `on-hold`, total `10.00`, `needs_shipping_address=false`,
  `has_shipping_address=false`, and every order shipping field empty.
- Subscription `26775` contained exactly one physical `_shipping_address`
  postmeta row. Its decoded value exactly matched the authenticated customer's
  saved shipping profile: `Admin Test`, `Hakirmor`, `Bogura`, `UA07`, `58001`,
  `UA`.
- The live admin detail screen rendered that complete shipping address. Browser
  errors were empty. Existing block control `11959` retained its original full
  Bangladesh shipping snapshot.
- Pending BACS subscription `26775` had no scheduler actions. The checkout emitted
  only the expected WooCommerce admin-new-order mail
  `2kEdlqXxat2KuzCgH0Tg8n` and customer-on-hold mail
  `1zPxE6FmuLNdLZQPE1aist` after baseline `6olOhEc9Xc4Kc1F9JvtoSA`.
- Cleanup was exact-linkage guarded. Subscription `26775` and order `26774` were
  permanently deleted; both are absent, matching scheduler actions remain `0`,
  and aggregate counts returned from `404`/`729` to `403` subscriptions and
  `728` orders. The rejected disposable customer `457` was deleted before any
  checkout and has no order or subscription.
- No frontend build was required for this PHP-only change. Lint and PHPCS were
  intentionally skipped per the issue-fix instructions.

Fix evidence:

- `/home/server-manager/slt-evidence/FIX-LOW-SLT-CHK-02-before.png`
- `/home/server-manager/slt-evidence/FIX-LOW-SLT-CHK-02-classic-ready.png`
- `/home/server-manager/slt-evidence/FIX-LOW-SLT-CHK-02-order-received.png`
- `/home/server-manager/slt-evidence/FIX-LOW-SLT-CHK-02-after.png`
