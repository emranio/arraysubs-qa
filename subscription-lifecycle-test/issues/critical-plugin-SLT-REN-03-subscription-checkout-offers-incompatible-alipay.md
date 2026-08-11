# Subscription checkout offers Alipay even though the future-use intent rejects it

- Severity: high
- Date found: 2026-08-03
- Watch day: D01
- Originating task: `SLT-REN-03` (progress task ID `24`)
- Plan file: `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test/kanban/tasks/024-renewal-invoice-leg-pending-order-and-invoice.md`

## Task / stage / plan

- QA progress task: `#24` / `SLT-REN-03`
- Stage: `D01`
- Plan path: `qa/subscription-lifecycle-test/kanban/tasks/024-renewal-invoice-leg-pending-order-and-invoice.md`

## Affected records

- Subscription ID: `12147`
- Order ID: `12131`
- Product ID: `11927` (`SLT Daily Core`)
- WP user: ID `358`, login `slt.invoice`, email `slt-invoice@example.test`, role `customer`
- Gateway: WooCommerce Stripe test mode
- Payment-method type on the failed attempt: Alipay
- Recovery control: the same order and subscription later succeeded with a Visa test fixture ending `4242`

## Affected IDs

- Subscription ID(s): `12147`
- Order ID(s): `12131`
- Product ID(s): `11927` (`SLT Daily Core`)

## Affected user / customer context

- WordPress user ID(s): `358`
- Login / email: `slt.invoice` / `slt-invoice@example.test`
- Role(s): `customer`

## Exact routes / browser context

- Checkout URL: `https://mirror-help.arrayhash.com/checkout/`
- Browser session: logged-out-to-new-customer `guest-SLT-REN-03`
- Checkout type: WooCommerce block checkout
- Initial attempt: 2026-08-03 12:46 site time (UTC+6), inside the task's 12:30-13:00 authored gate
- Recovery: the existing order's WooCommerce order-pay screen; its keyed URL is intentionally omitted

## Reproduction

1. As a logged-out visitor, add virtual subscription product `11927` to an empty cart and open block checkout.
2. Enter the task's new-customer billing identity and keep WooCommerce Stripe selected.
3. In Stripe's Payment Element, select Alipay, which is offered alongside Card and other methods for this recurring subscription checkout.
4. Submit the order.
5. Inspect the resulting order, subscription, email delta, order notes, and WooCommerce Stripe log.

## Expected result

- A recurring subscription checkout exposes only payment methods compatible with saving/using the method for future off-session renewals, or processes an offered method without an incompatible intent parameter.
- An unsupported method cannot leave behind a failed parent order plus an on-hold subscription and lifecycle email.

## Actual result

- The checkout submitted an Alipay PaymentMethod while the PaymentIntent also included `setup_future_usage`.
- Stripe rejected the request with: `You do not have permission to provide the setup_future_usage parameter when using a PaymentIntent with a PaymentMethod of type alipay.`
- Order `12131` changed from pending payment to `wc-failed` at `2026-08-03 06:46:47Z` and emitted both customer and admin failed-order messages.
- Subscription `12147` was nevertheless created and moved to on-hold, and the customer received an on-hold subscription message.
- Paying the same order from its order-pay page with the Card option succeeded at `2026-08-03 07:02:18Z`; order `12131` became `wc-completed`, subscription `12147` became `arraysubs-active`, and its saved payment-method type became `card`. This recovery proves the product, customer, amount, and Stripe account work when a compatible method is selected.

## Concrete proof

- `/home/server-manager/slt-evidence/SLT-REN-03-00-cart-empty-before.png` records the clean precondition.
- `/home/server-manager/slt-evidence/SLT-REN-03-01-checkout.png` records the real block-checkout fixture, USD 10.00 total, and 2026-08-04 next charge.
- `/home/server-manager/slt-evidence/SLT-REN-03-01-order-received.png` records the successful same-order recovery and active related subscription `12147`.
- `/home/server-manager/slt-evidence/SLT-REN-03-01b-cart-empty-after.png` records the empty browser cart after recovery.
- Order notes `1118`-`1120` record the failed payment and failed-order mail; notes `1121`-`1125` record the later Stripe payment, completed/new-order mail, and completed charge on the same order.
- Stripe log `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/uploads/wc-logs/woocommerce-gateway-stripe-2026-08-03-30a685942b0f2aad4d53693d2ee0b326.log` contains the exact invalid-request error at `2026-08-03T06:46:47+00:00`.
- Failed-attempt Mailpit IDs: customer failed order `6FDQuJWl7DAD16ejKqWXUj`, admin failed order `4HQwGlFob5BHDNNL3J7G5m`, and customer subscription-on-hold `0BqTmfXz4IFLKOffu6v1LT`.

## Known scope / counterexamples

- The Card option is a direct counterexample: it paid the same order and activated the same subscription without creating a duplicate record.
- The failure is specific to offering/processing an incompatible recurring-payment method, not a general Stripe outage or invalid product/customer fixture.
- The customer account mail was sent once when the original checkout created user `358`; the recovery correctly reused that account.
- No product source was inspected or changed. This issue records observed browser, WordPress runtime, database/meta, mail, and gateway-log evidence only.
