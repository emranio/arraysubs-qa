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
- Corroborating latest renewal order: `13480`
- Corroborating product: `12112` (`SLT Paddle Daily`)
- Corroborating latest transaction: `txn_01kzk0mpqxf7w1tpdcdyna8n9v`

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
- Corroborating HPOS order: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-orders&action=edit&id=13480`
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
