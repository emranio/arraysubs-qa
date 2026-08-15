# Paddle renewals leave the subscription's last gateway transaction stuck on its initial checkout

- Severity: medium
- Date found: 2026-08-09
- Watch day: D07
- Originating test task: `SLT-SW-05` / card `#96`
- Plan file: `kanban/tasks/096-upgrade-a-paddle-billed-ladder-subscription-and.md`

## Affected records

- Primary subscription: `13344` (`SLT Plan Basic`, active)
- Primary parent order: `13343`
- Primary renewal order: `13462`
- Primary product: `12608` (`SLT Plan Basic`)
- Primary Paddle subscription: `sub_01kzgwg0b05gx4ryprhxa32fns`
- Primary renewal transaction: `txn_01kzkeybgsey4fh7r53v2efhag`
- Corroborating subscription: `12639` (`SLT Paddle Daily`, active)
- Corroborating parent order: `12629`
- D07 corroborating renewal order: `13480`
- Corroborating product: `12112` (`SLT Paddle Daily`)
- D07 corroborating transaction: `txn_01kzk0mpqxf7w1tpdcdyna8n9v`

## Affected user

- WordPress user ID: `352`
- Login: `slt-paddle`
- Email: `slt-paddle@example.test`
- Role: customer

## Gateway, checkout, and settings context

- Gateway: Paddle sandbox
- Checkout type: initial subscriptions were bought through block checkout; the failing observations are natural off-session `subscription_recurring` renewals.
- Primary payment: captured on Paddle sandbox Visa ending `4242`; no full card number is retained.
- Non-default settings: none. No settings bracket was opened.
- The unrelated failed `$9.90` plan-switch order `13354` remained pending and did not participate in the renewal.

## Routes and browser context

- Primary HPOS order: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-orders&action=edit&id=13462`
- Primary ArraySubs subscription: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/13344`
- D07 corroborating HPOS order: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-orders&action=edit&id=13480`
- Browser context: administrator, isolated session `admin-SLT-SW-05-D07-watch`

## Reproduction steps

1. Start with active Paddle subscription `13344`. Before renewal, `_last_gateway_transaction_id` is its initial checkout transaction `txn_01kzgv8fc6kg5hcwkm42ds4a4c`; remote `next_billed_at` is `2026-08-09T14:30:08.245157Z`.
2. Set an immutable Mailpit/local-state boundary and allow Paddle to settle the renewal naturally. Do not run Action Scheduler.
3. Verify Paddle creates one completed/captured `subscription_recurring` transaction for the exact remote subscription and price.
4. Resolve the local order by exact transaction ID plus customer `352` and gateway `arraysubs_paddle`; require the reverse subscription metas rather than selecting by recency.
5. Verify order `13462` is completed for `$5.00`, its HPOS transaction column contains the new transaction, payment count advances, and the remote/local next-payment date advances once.
6. Re-read subscription `13344`'s `_last_gateway_transaction_id` after webhook processing and after the order has completed.
7. Repeat the comparison on independent Paddle subscription `12639`, then compare with same-watch Stripe renewal controls.

## Expected result

After each successful renewal, the subscription's `_last_gateway_transaction_id` should identify that newest successful gateway transaction. The field name and the Stripe renewal behavior both imply a rolling audit link, not an immutable parent-checkout link.

## Actual result

- Paddle completed/captured primary renewal transaction `txn_01kzkeybgsey4fh7r53v2efhag` for `$5.00`; webhook events were processed and exact order `13462` completed with that transaction in its HPOS `transaction_id` column.
- Subscription `13344` nevertheless still stores initial checkout transaction `txn_01kzgv8fc6kg5hcwkm42ds4a4c` in `_last_gateway_transaction_id`.
- Independent Paddle subscription `12639` still stores its initial checkout transaction `txn_01kz8pz26xhc4cyhm6m3m6dw9x`, despite later completed renewal orders including `13480` / `txn_01kzk0mpqxf7w1tpdcdyna8n9v`.
- Same-watch Stripe controls do update the subscription field: `12684` stores newest order `13459` transaction `ch_3U2WZBJG5OzSNVs21urZlLSa`, and `12221` stores newest order `13456` transaction `ch_3U2W1IJG5OzSNVs21lldELbm`.

## Concrete proof

- Complete sanitized transaction/order/action/webhook/Mailpit reconciliation: `/home/server-manager/slt-evidence/SLT-SW-05-D07-natural-basic-renewal.txt`
- Exact admin screenshot: `/home/server-manager/slt-evidence/SLT-SW-05-D07-order-13462.png`
- The screenshot visibly shows order `13462` Completed, Payment via Paddle with transaction `txn_01kzkeybgsey4fh7r53v2efhag`, linked subscription `13344`, one Basic line at quantity `1`, and `$5.00` paid.
- Remote transaction proof: completed / `subscription_recurring`, billed `2026-08-09T14:31:02.425186Z`, captured `14:31:04.732244Z`, `500 USD`, Visa ending `4242`.
- Webhook proof: `subscription.updated` row `565` and `transaction.completed` row `569` were processed before the order completed.
- Mail proof: admin New order `7VcgBrWvc2S4c0q9LiEodq` and customer payment-success `5DrgO7Y660rDiXmD8dMojT`; the latter reports Basic, `$5.00`, and the correct D8 next-payment date.
- Structured comparison captured at `2026-08-09 14:32:48Z`:

  ```text
  12639  _last_gateway_transaction_id=txn_01kz8pz26xhc4cyhm6m3m6dw9x
  13344  _last_gateway_transaction_id=txn_01kzgv8fc6kg5hcwkm42ds4a4c
  12684  _last_gateway_transaction_id=ch_3U2WZBJG5OzSNVs21urZlLSa
  12221  _last_gateway_transaction_id=ch_3U2W1IJG5OzSNVs21lldELbm
  ```

## Scope notes and counterexamples

- The money, cycle count, next-payment date, exact order, webhook processing, and exact two-message mail delta are all correct. This is stale subscription audit metadata, not a missing or duplicate charge.
- It reproduces on two distinct live Paddle subscriptions and across multiple recurring transactions on `12639`, so it is not specific to the failed SW-05 plan-switch attempt.
- The correct HPOS transaction on order `13462` proves the new transaction is available locally; only the subscription-level rolling field remains stale.
- Stripe controls on the same watch update the equivalent subscription field, providing a gateway-specific counterexample.
- Missing renewal-order transaction metas and missing order-item subscription metas are tracked separately; they are not duplicated in this finding.
- No scheduler row was forced, no order/meta was edited, no setting changed, and no non-SLT object was touched.

## D08 corroborating recurrence — 2026-08-10

Paddle Daily subscription `12639` completed another exact recurring payment: transaction `txn_01kznk1e8p98v536yf9fnv41kv`, order `13617`, `$11.00`, and customer success mail `2uApI9cIbIcnehcFQhXMJA`. After webhook completion, the subscription still stores its initial checkout transaction `txn_01kz8pz26xhc4cyhm6m3m6dw9x` in `_last_gateway_transaction_id`. The current transaction is available in HPOS `wp_wc_orders.transaction_id`, so this is a third renewal-era corroboration of the stale subscription audit link. Evidence: `/home/server-manager/slt-evidence/D08-afternoon-paddle-12639.txt`.

## D08 night recurrence on the primary Basic fixture — 2026-08-10

Paddle subscription `13344` completed its next natural recurring payment as transaction
`txn_01kzp1b2e3s640gyyvh6b0p7sb`, captured once for `$5.00` and persisted on exact
relationship order `13605`. The relationship advanced to three completed payments and
remote/local next billing `2026-08-11 14:30:08Z`, but subscription
`_last_gateway_transaction_id` still equals the initial checkout transaction
`txn_01kzgv8fc6kg5hcwkm42ds4a4c`. Exact mails were admin
`4AFMsf8E5ClzQHmRvSDaSj` and customer `2RnPPAiQXw2bZPA2LP1ORF`; local action `16483`
was cancelled without an attempt, ruling out a duplicate local charge. Full sanitized
proof: `/home/server-manager/slt-evidence/D08-2026-08-10-night-natural-gates.txt`.

## D09 corroborating recurrence — 2026-08-11

Paddle Daily subscription `12639` completed another exact recurring payment: transaction `txn_01kzr5e5vvgb9wgt2pw2r74e7d`, relationship order `13769`, `$11.00`, and customer success mail `1f9kYMTz9XvdhZnbaeBQlS`. After webhook completion, the subscription still stores its initial checkout transaction `txn_01kz8pz26xhc4cyhm6m3m6dw9x` in `_last_gateway_transaction_id`, while HPOS order `13769` correctly stores the current transaction. Evidence: `/home/server-manager/slt-evidence/D09-afternoon-paddle-12639.txt`.

## D09 evening recurrence on the primary Basic fixture — 2026-08-11

Paddle subscription `13344` completed another natural `$5.00` recurring payment as
transaction `txn_01kzrkqs84sb3xcx73mzq8b4gc`, persisted on exact relationship order
`13758`. Payments advanced `3→4`, the local and remote next-payment values advanced to
`2026-08-12 14:30:08Z`, and local action `16852` was cancelled with zero attempts. The
subscription nevertheless still stores initial checkout transaction
`txn_01kzgv8fc6kg5hcwkm42ds4a4c` in `_last_gateway_transaction_id`, while HPOS order
`13758` correctly stores the new transaction. Exact mails were admin
`0wGCSXruhErqpOmosc8apZ` and customer `2OKnFAqlMWU4Msr4uETRvj`. Proof:
`/home/server-manager/slt-evidence/SLT-SW-05-D09-natural-basic-renewal.txt`,
`/home/server-manager/slt-evidence/SLT-SW-05-D09-subscription-13344.png`, and
`/home/server-manager/slt-evidence/SLT-SW-05-D09-order-13758.png`.

## Resolution — 2026-08-14

- Confirmed as a true Paddle-only defect. The provider-confirmed renewal path
  persisted the transaction on the renewal order but never advanced the
  subscription's `_last_gateway_transaction_id`.
- Paddle renewal finalization now persists the authenticated transaction and a
  nanosecond provider-event watermark after exact paid-order/provider binding
  validation. An older delivery cannot rewind the rolling audit link, an exact
  replay is idempotent, and a same-time different-transaction conflict fails
  closed for retry.
- The new watermark is managed by core's canonical `GatewayMetaStore`, including
  one-row verification and detach cleanup. No generic metadata writer or
  provider-evidence bypass was added.
- A signed replay of captured current transaction
  `txn_01m00d1vp61jcvdsbg8rr87d0p` completed exact pending order `20500`, advanced
  subscription `7809` from 39 to 40 payments, updated both order and subscription
  transaction audit fields, and preserved the Paddle-owned next billing date.
  Exact duplicate, same-time replay, and older replay all returned HTTP 200
  without another payment increment, note, or mail.
- Historical affected subscriptions were repaired only after full Paddle/local
  reconciliation: `13344` now points to order `13758` transaction
  `txn_01kzrkqs84sb3xcx73mzq8b4gc`; `12639` now points to order `13769`
  transaction `txn_01kzr5e5vvgb9wgt2pw2r74e7d`. Both transaction and watermark
  keys have exactly one canonical row.
- Live admin and owner-browser checks show the new last transaction, completed
  order `20500`, 40 completed payments, active status, correct next payment,
  Paddle card display, and no browser console/network errors.
- After-state screenshots:
  `/home/server-manager/slt-evidence/FIX-PADDLE-SLT-SW-05-last-transaction-admin.png`,
  `/home/server-manager/slt-evidence/FIX-PADDLE-SLT-SW-05-last-transaction-order-20500.png`,
  `/home/server-manager/slt-evidence/FIX-PADDLE-SLT-SW-05-last-transaction-owner.png`,
  and `/home/server-manager/slt-evidence/FIX-PADDLE-SLT-SW-05-affected-13344-after.png`.
