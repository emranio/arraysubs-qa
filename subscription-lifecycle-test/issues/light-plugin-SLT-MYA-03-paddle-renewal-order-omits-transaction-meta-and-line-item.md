# Paddle renewal order omits transaction meta and its subscription line item

- Severity: medium
- Date found: 2026-08-09
- Watch day: D07
- Originating task: `SLT-MYA-03` / card `#94`
- Plan file: `kanban/tasks/094-update-the-paddle-payment-method-and-prove-the.md`

## Affected records

- Subscription: `12639` (`SLT Paddle Daily`, arraysubs-active)
- Product: `12112` (`SLT Paddle Daily`)
- Parent order: `12629`
- Renewal order: `13249`
- Paddle subscription: `sub_01kz8q1025tryjfgxvn5e3v4gf`
- Paddle transaction: `txn_01kzge82gz7pyqne0x1prnmf6a`

## Affected user

- WP user ID: `352`
- Login: `slt-paddle`
- Email: `slt-paddle@example.test`
- Role: Customer

## Gateway, checkout, and settings context

- Gateway: Paddle sandbox
- Checkout type: N/A; natural off-session recurring settlement
- Payment fixture: Paddle sandbox Visa ending `5556`; no full card number is retained.
- Non-default settings: none. The frozen suite baseline remained in force and no settings bracket was opened.

## Route and browser context

- Exact admin order route: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=wc-orders&action=edit&id=13249`
- Browser context: administrator, isolated session `admin-SLT-MYA-03-R1`

## Reproduction steps

1. Begin with active Paddle sandbox subscription `12639`, linked remotely to `sub_01kz8q1025tryjfgxvn5e3v4gf` and priced at `$11.00` daily.
2. Allow Paddle's recurring billing gate to settle naturally; do not run an Action Scheduler row.
3. Resolve the local order by the exact Paddle transaction/subscription relationship, not by recency. Transaction `txn_01kzge82gz7pyqne0x1prnmf6a` resolves uniquely to order `13249`.
4. Confirm the order is completed for `$11.00` and reverses to subscription `12639` through `_subscription_id` and `_subscription_renewal`.
5. Inspect the order's HPOS transaction column, order meta, order-item rows, exact admin UI, and admin new-order email.

## Expected result

The paid renewal order should persist the exact Paddle transaction in the authored `_paddle_transaction_id` linkage field and contain its `SLT Paddle Daily` subscription line with an `$11.00` total, so the order is independently auditable from both structured data and UI/email output.

## Actual result

- Order `13249` is completed and paid for `$11.00`; its HPOS `transaction_id` column contains `txn_01kzge82gz7pyqne0x1prnmf6a`.
- It has zero `_paddle_transaction_id` rows and zero `_last_gateway_transaction_id` rows.
- It has zero order-item rows. The admin UI and admin new-order email therefore show an empty product table / Items Subtotal `$0.00`, while Order Total and Paid are `$11.00`.
- Reverse subscription flags do exist: `_is_renewal_order=yes`, `_subscription_id=12639`, `_subscription_renewal=12639`, and `_subscription_payment_processed=yes`.

## Concrete proof

- Full sanitized reconciliation: `/home/server-manager/slt-evidence/SLT-MYA-03-D07-followup.txt`
- Exact admin screenshot: `/home/server-manager/slt-evidence/SLT-MYA-03-D07-order-13249.png`
- Paddle proof: the sanitized exact transaction is completed, `origin=subscription_recurring`, linked to the exact Paddle subscription, captured for `1100` USD minor units, and used Visa last4 `5556`.
- Local proof: the exact HPOS transaction search returns only order `13249`; its structured read records zero authored transaction-meta rows and zero item rows.
- UI proof: the screenshot shows Completed, Payment via Paddle, the exact transaction ID, Order Total `$11.00`, Paid `$11.00`, and Items Subtotal `$0.00`.
- Natural remote charge gate: `2026-08-08T10:20:38.143985Z` / `16:20:38` site. Immutable Mailpit baseline `7eIiwGyhpr2alnqiBICks7` was captured at `2026-08-08 10:16:06Z` / `16:16:05` site.
- Mail proof:
  - `3k11t5ipr2GSH5xTNFE483` — `[mirror-help.arrayhash.com]: You've got a new order: #13249` to `admin@mirror-help.arrayhash.com`; it has no product row.
  - `57lxgHohFqevl55xhVeR3P` — `[mirror-help.arrayhash.com] Payment received for subscription #12639` to `slt-paddle@example.test`; it correctly reports `$11.00` paid.
- Browser diagnostics: exact document HTTP `200`, empty browser errors, and only informational JQMIGRATE console entries.
- Visible order note: `Retroactive renewal order created for subscription #12639 (Paddle charged before scheduled renewal).`

## Scope notes and counterexamples

- The charge itself is not missing or duplicated: Paddle reports one captured `$11.00` recurring transaction, and the customer payment-success email reports the correct amount.
- The order is relationship-linked to the correct SLT subscription through other reverse meta, so this is incomplete renewal-order persistence rather than a wrong-order selection.
- The overnight Stripe renewal order `13273` for subscription `12172` contained its sole `$9.00` subscription line, providing a same-watch counterexample to the empty Paddle renewal order.
- The earlier zero-dollar payment-method-change order `13214` was also empty, but order `13249` proves the missing line is not limited to a zero-value transaction.
- No action was forced and no order, subscription, action, meta, setting, or non-SLT object was changed.

## D07 afternoon recurrence — 2026-08-09

The defect reproduced on the next exact Paddle recurring settlement. Transaction `txn_01kzk0mpqxf7w1tpdcdyna8n9v` is completed/captured for `$11.00`, and exact HPOS transaction/customer/gateway linkage resolves only order `13480`. The order is `wc-completed` for `$11.00` and carries `_is_renewal_order=yes`, `_subscription_id=12639`, `_subscription_renewal=12639`, and `_subscription_payment_processed=yes`. It again has zero `_paddle_transaction_id` rows, zero `_last_gateway_transaction_id` rows, and zero order-item rows. Admin mail `5GSsdYtQfOf63LccnkfQaD` consequently has an empty product table while customer mail `3jUrzcrxsS5qMrFvzDePXI` correctly reports `$11.00`. Full sanitized proof: `/home/server-manager/slt-evidence/D07-afternoon-paddle-12639.txt`.

## D07 evening pre-created-order counterexample — 2026-08-09

Independent Paddle subscription `13344` narrows the two symptoms. Its invoice action pre-created relationship order `13462` with one valid `SLT Plan Basic` line before the remote gate. Paddle then completed recurring transaction `txn_01kzkeybgsey4fh7r53v2efhag` for `$5.00`, and the webhook reused that exact order. The completed order retained its quantity-1 / `$5.00` line, so the zero-item symptom is specific to the retroactive renewal-order path observed on `12639`. However, order `13462` still has zero `_paddle_transaction_id` and zero `_last_gateway_transaction_id` meta rows despite its valid HPOS transaction. The missing authored order-transaction metas therefore reproduce across both pre-created and retroactive Paddle renewal orders. Proof: `/home/server-manager/slt-evidence/SLT-SW-05-D07-natural-basic-renewal.txt` and `/home/server-manager/slt-evidence/SLT-SW-05-D07-order-13462.png`.

## D08 afternoon recurrence — 2026-08-10

The next retroactive renewal reproduced every affected field. Paddle transaction `txn_01kznk1e8p98v536yf9fnv41kv` completed once for `$11.00`; exact order `13617` is paid and reverses to subscription `12639`, but has zero `_paddle_transaction_id` rows, zero `_last_gateway_transaction_id` rows, no renewal cycle/scheduled-date meta, and zero order-item rows. Its customer order view shows an empty product table, `$0.00` subtotal, and `$11.00` total; admin mail `0Ku58j3hM2B97uYkb6HeOj` has the same empty product table. Evidence: `/home/server-manager/slt-evidence/D08-afternoon-paddle-12639.txt` and `/home/server-manager/slt-evidence/D08-Paddle-12639-order-13617.png`.

## D08 night pre-created-order recurrence — 2026-08-10

Independent Paddle subscription `13344` again narrows the order-item and transaction-meta
symptoms. Invoice action `16482` pre-created order `13605` with one valid Basic line; remote
transaction `txn_01kzp1b2e3s640gyyvh6b0p7sb` then completed it for `$5.00`. The line remained
present, but the paid order still has zero `_paddle_transaction_id` and zero
`_last_gateway_transaction_id` meta rows despite the exact transaction in HPOS. This is a
second recurring cycle on the pre-created branch and reproduces the missing transaction
metas while continuing to isolate the zero-line defect to retroactive order creation.
Evidence: `/home/server-manager/slt-evidence/D08-2026-08-10-night-natural-gates.txt`.

## D09 afternoon retroactive-order recurrence — 2026-08-11

Paddle transaction `txn_01kzr5e5vvgb9wgt2pw2r74e7d` completed/captured once for `$11.00` and uniquely resolved through the exact subscription relationship to completed order `13769`. The order carries `_is_renewal_order=yes`, `_subscription_id=12639`, and `_subscription_renewal=12639`, but again has zero `_paddle_transaction_id` rows, zero `_last_gateway_transaction_id` rows, no renewal cycle/scheduled-date meta, and zero order-item rows. The authenticated order view and admin New order mail `2JdFlL8eAwcYe2z6GxesKU` therefore show an empty product table and `$0.00` subtotal beside the correct `$11.00` total. Customer success mail `1f9kYMTz9XvdhZnbaeBQlS` reports the correct product and amount. Proof: `/home/server-manager/slt-evidence/D09-afternoon-paddle-12639.txt` and `/home/server-manager/slt-evidence/D09-Paddle-12639-order-13769.png`.

## D09 evening pre-created-order recurrence — 2026-08-11

Invoice action `16851` pre-created exact renewal order `13758` for subscription `13344`.
Natural Paddle transaction `txn_01kzrkqs84sb3xcx73mzq8b4gc` then completed it once for
`$5.00`. Item `759` remained present as one quantity-one `SLT Plan Basic` line at `$5.00`,
again isolating the zero-item symptom to retroactive renewal-order creation. The paid order
still has no `_paddle_transaction_id` or `_last_gateway_transaction_id` meta row despite
the correct HPOS transaction column. Admin mail `0wGCSXruhErqpOmosc8apZ` also contains the
correct line and subtotal. Proof:
`/home/server-manager/slt-evidence/SLT-SW-05-D09-natural-basic-renewal.txt` and
`/home/server-manager/slt-evidence/SLT-SW-05-D09-order-13758.png`.
