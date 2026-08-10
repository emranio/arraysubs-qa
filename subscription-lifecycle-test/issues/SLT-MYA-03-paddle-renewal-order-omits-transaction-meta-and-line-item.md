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
