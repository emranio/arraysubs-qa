# SLT-SYN-14: prorated initial recurring meta stores the line total at quantity 3

- Status: **resolved 2026-08-11 — retracted QA expectation; line-total contract retained**
- Severity: medium
- Date found: 2026-08-05
- Watch day: D03
- Originating task: `SLT-SYN-14`
- Plan: `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test/kanban/tasks/062-quantity-3-on-a-segment-2-prorated-first-charge.md`
- Affected subscription: `12749`
- Affected order: `12748`
- Affected product: `12737` (`SLT Flex Qty Week`)
- Affected user: `365`, `slt-qty` / `slt-qty@example.test`, role `customer`
- Gateway / checkout: Stripe test, classic checkout
- Non-default configuration: product-level Flexible Renewal Sync enabled with segments `1 / 2-6 / 7`; week/1, USD `9.99`, quantity `3`. No temporary global-settings bracket was open.
- Route / context: customer `slt-qty` at `https://mirror-help.arrayhash.com/slt-classic-checkout` and the order-received route; admin/CLI inspection of subscription `12749`.

## Task / stage / plan

- QA progress task: `#62` / `SLT-SYN-14`
- Stage: `D03`
- Plan path: `qa/subscription-lifecycle-test/kanban/tasks/062-quantity-3-on-a-segment-2-prorated-first-charge.md`

## Affected IDs

- Subscription ID(s): `12749`
- Order ID(s): `12748`
- Product ID(s): `12737` (`SLT Flex Qty Week`)

## Affected user / customer context

- WordPress user ID(s): `365`
- Login / email: `slt-qty` / `slt-qty@example.test`
- Role(s): `customer`

## Exact routes / browser context

- Browser / user context: customer `slt-qty` at `https://mirror-help.arrayhash.com/slt-classic-checkout` and the order-received route; admin/CLI inspection of subscription `12749`

## Reproduction

1. Publish an isolated simple virtual week/1 subscription product at USD `9.99` with Flexible Renewal Sync enabled and segment ends `1` and `6`.
2. On 2026-08-05 (cycle day 5), add quantity `3` through the classic cart. Confirm the unit-first prorated figures: USD `2.85` for quantity 1 and USD `8.55` for quantity 3, recurring USD `29.97` per week.
3. Complete Stripe test checkout as an isolated SLT customer.
4. Resolve the sole subscription through the parent order's `_subscription_ids`, then cross-check `_parent_order_id`, `_customer_id`, and `_product_id`.
5. Read `_quantity`, `_renewal_sync_initial_recurring_amount`, `_recurring_amount`, and `_next_payment_date` from that exact subscription.

## Expected result

The order line totals USD `8.55`, and `_renewal_sync_initial_recurring_amount` stores that purchased checkout-line figure USD `8.55`; quantity remains `3` and the full recurring unit amount remains USD `9.99`. The unit-first rounding proof is the visible USD `2.85` at quantity 1 and USD `8.55` at quantity 3, rather than the line-first USD `8.56` value.

## Actual result

The order arithmetic is correct at USD `8.55`, and subscription `12749` stores `_renewal_sync_initial_recurring_amount=8.55`, matching the corrected task expectation. No product mismatch remains.

## Proof

- Safe receipt: `/home/server-manager/slt-evidence/SLT-SYN-14-04-order-received.png` shows order `12748`, quantity `3`, total USD `8.55`, and related subscription `12749`.
- Cart evidence: `/home/server-manager/slt-evidence/SLT-SYN-14-02-cart-qty1.png` and `SLT-SYN-14-03-cart-qty3.png` show USD `2.85` and USD `8.55` respectively.
- Exact HPOS line query: order item `674`, product `12737`, quantity `3`, subtotal and total `8.55`.
- Exact subscription meta: `_quantity=3`, `_recurring_amount=9.99`, `_subscription_price=9.99`, `_renewal_sync_first_charge_mode=prorate`, `_renewal_sync_initial_recurring_amount=8.55`, `_next_payment_date=2026-08-07 18:00:00`.
- Relationship proof: order `12748` has `_subscription_ids=[12749]`; subscription `12749` has parent `12748`, customer `365`, and product `12737`.
- Schedule proof: `/home/server-manager/slt-evidence/SLT-SYN-14-05-schedule.png`; pending invoice `14916` and charge `14917` both have exact args `[12749]`.
- Checkout mail IDs: `3SgL2bAHjt0pQ3v9gng1FA`, `3yuJrlL6p55Yy7rdwvyONd`, `1iiwOdU03lYDSaDlV4sLVi`, and `2NWHnHe5PRDFyk9da17atS`.

## Scope and counterexamples

The visible proration and charged order total use the intended unit-first rounding (`2.85 x 3 = 8.55`, not line-first `8.56`). The earlier issue interpretation came from an incorrect QA expectation that the subscription meta was unit-scoped; the authored task was corrected to its runtime line-scope contract. This file is retained only as an audit trail and must not be reported as an open product issue. The D6 full-renewal result remains pending.

## Resolution and verification

- Task #62 now asserts `_renewal_sync_initial_recurring_amount=8.55` as the quantity-three checkout-line
  total while retaining `_recurring_amount=9.99` as the full recurring unit amount.
- The relationship, order-line, schedule, UI, and Mailpit proof above verifies the corrected QA contract;
  no plugin change is warranted.
