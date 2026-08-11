# Paddle payment-method change is treated as a zero-dollar paid renewal and advances the local schedule

- Severity: high
- Date found: 2026-08-08
- Watch day: D06
- Originating task: `SLT-MYA-03` / card `#94`
- Plan file: `kanban/tasks/094-update-the-paddle-payment-method-and-prove-the.md`

## Affected records

- Subscription: `12639` (`SLT Paddle Daily`, arraysubs-active)
- Original parent order: `12629` (completed, `$11.00`)
- Unexpected order: `13214` (completed, `$0.00`, no order-item rows)
- Later genuine renewal order: `13249` (completed, `$11.00`)
- Product: `12112` (`SLT Paddle Daily`)
- Paddle subscription: `sub_01kz8q1025tryjfgxvn5e3v4gf`
- Paddle payment-method-change transaction: `txn_01kzfj2d3mtsbasp6r8s8zmrw8`
- Paddle recurring transaction: `txn_01kzge82gz7pyqne0x1prnmf6a`
- Superseded local actions: invoice `15615`, charge `15616`
- Replacement local actions: invoice `15890`, charge `15891`

## Affected user

- WP user ID: `352`
- Login: `slt-paddle`
- Email: `slt-paddle@example.test`
- Role: Customer

## Gateway, checkout, and settings context

- Gateway: Paddle sandbox
- Checkout type: no product checkout; ArraySubs customer portal plus Paddle-hosted subscription payment-method update form
- New fixture recorded safely: Paddle-documented valid Visa debit sandbox fixture ending `5556`; the previous card ended `4242`. No full card number is retained.
- Non-default settings: none. Frozen suite baseline remained in force; no setting bracket was opened.

## Routes and browser contexts

- Local payment methods: `https://mirror-help.arrayhash.com/my-account/payment-methods/`
- Local subscription: `https://mirror-help.arrayhash.com/my-account/view-subscription/12639/`
- Paddle update route: temporary authenticated customer-portal management URL from `GET /subscriptions/sub_01kz8q1025tryjfgxvn5e3v4gf`; token and complete URL are redacted and were never stored.
- Admin detail: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/12639`
- Browser sessions: `customer-MYA-03-SLT-MYA-03` and `admin-SLT-MYA-03`

## Reproduction steps

1. Start with active Paddle sandbox subscription `12639`, remote `next_billed_at=2026-08-08T10:20:38.143985Z`, and local `_next_payment_date=2026-08-08 10:20:38` UTC.
2. Confirm its pre-update card state: local Visa ending `4242`; completed payments `3`; pending invoice/charge actions `15615`/`15616` for the current cycle.
3. Set immutable Mailpit baseline `6B2jxkahZfmlae0je2Rmah`.
4. Open the exact local subscription as `slt-paddle`; observe the Card on File row and its Update payment method control.
5. Fetch the exact Paddle subscription and use its temporary `management_urls.update_payment_method` link. Do not store the link or API credential.
6. In the Paddle-hosted form, enter the valid sandbox card fixture ending `5556` and click Update payment method once. Do not capture populated fields.
7. Observe Paddle transaction `txn_01kzfj2d3mtsbasp6r8s8zmrw8`: `origin=subscription_payment_method_change`, `status=completed`, total `0` USD, new card last4 `5556`.
8. Within 69 seconds, re-read subscription `12639`, its actions, relationship-linked orders, admin timeline/notes, and the complete Mailpit delta.

## Expected result

- The saved Paddle payment method changes without creating a paid renewal.
- No renewal order or payment-received email is created for the zero-dollar payment-method-change transaction.
- The current remote billing gate remains `2026-08-08T10:20:38.143985Z`; local `_next_payment_date` and the current-cycle action pair are not advanced merely because the payment method changed.
- The local notes may record `Paddle subscription updated — state synchronized.`, while the real `$11.00` recurring transaction later owns the renewal order, payment count, payment-success mail, and schedule advance.

## Actual result

- Paddle correctly accepted the new card and created a completed zero-dollar transaction with origin `subscription_payment_method_change`.
- ArraySubs created completed order `13214` for `$0.00`, marked it as a renewal, marked its payment processed, and recorded it in the subscription timeline as `Paddle renewal payment succeeded`.
- ArraySubs sent admin new-order mail `2AmbBF9Gr3ahDaee9jMKcH` for empty order `13214` / `$0.00` and customer mail `4E4IXtfwjCT1FJZSE9B1mk`, `Payment received for subscription #12639`, stating Amount Paid `$0.00`.
- Local `_next_payment_date` advanced immediately from `2026-08-08 10:20:38` to `2026-08-09 10:20:38` UTC, while Paddle still reports the current remote billing gate on August 8.
- Current-cycle actions `15615`/`15616` were canceled and next-day actions `15890`/`15891` were created at the same second.
- Local card metadata remained stale at Visa ending `4242`; `_payment_method_updated_at` remained absent.

## Concrete proof

- Before state: `/home/server-manager/slt-evidence/SLT-MYA-03-before.txt`
- Immediate after state: `/home/server-manager/slt-evidence/SLT-MYA-03-after.txt`
- Sanitized Paddle before/after evidence:
  - `/home/server-manager/slt-evidence/SLT-MYA-03-paddle-api-before.json`
  - `/home/server-manager/slt-evidence/SLT-MYA-03-paddle-api-after.json`
- Screenshots:
  - `/home/server-manager/slt-evidence/SLT-MYA-03-01-methods-empty.png`
  - `/home/server-manager/slt-evidence/SLT-MYA-03-02-detail-row.png`
  - `/home/server-manager/slt-evidence/SLT-MYA-03-03-update-page.png`
  - `/home/server-manager/slt-evidence/SLT-MYA-03-04-notes.png`
- Admin UI proof in `SLT-MYA-03-04-notes.png`:
  - Next Payment changed to 9 August 2026 4:20 PM UTC+6.
  - Order History shows `#13214`, completed, `$0.00`, type Renewal.
  - Payment Timeline says `Paddle renewal payment succeeded` for the payment-method-change transaction and `Payment successful — Order #13214 ($0.00)`.
  - Subscription Notes record the renewal-success email and `Paddle subscription updated — state synchronized.`
- Action Scheduler:
  - `15615` and `15616`: `action canceled` at `2026-08-08 02:10:10Z`.
  - `15890` and `15891`: `action created` at `2026-08-08 02:10:10Z` for the next day.
- Order `13214` has zero item rows and meta `_is_renewal_order=yes`, `_subscription_id=12639`, `_subscription_renewal=12639`, `_subscription_payment_processed=yes`, and `_arraysubs_renewal_payment_success_email_sent=yes`.
- Mailpit `2AmbBF9Gr3ahDaee9jMKcH` was created `2026-08-08T02:10:09Z` for `admin@mirror-help.arrayhash.com`; it announces order `13214`, contains no product row, and reports `$0.00` subtotal/total.
- Mailpit `4E4IXtfwjCT1FJZSE9B1mk` was created `2026-08-08T02:10:10Z` for `slt-paddle@example.test` and reports a `$0.00` payment.

## Scope notes and counterexamples

- This is not a failed Paddle card update: sanitized remote transaction evidence proves the new last4 `5556` was accepted. The defect is local classification and side effects for `origin=subscription_payment_method_change`.
- Paddle's prior real recurring transaction `txn_01kzdvvat5k2h8w3dg95ta3rkq` had origin `subscription_recurring`, total `1100` minor units, captured status, and produced legitimate completed `$11.00` order `13044` plus its payment-success message.
- Orders `12891` and `13044` are successful `$11.00` Paddle renewals; order `13214` is the zero-dollar counterexample with no line items.
- The D07 settlement follow-up proved that genuine recurring transaction `txn_01kzge82gz7pyqne0x1prnmf6a` captured `$11.00` once on the new Visa ending `5556` and created completed renewal order `13249`; the hosted update itself therefore succeeded.
- The genuine settlement did not heal the schedule corruption. Paddle then reported `next_billed_at=2026-08-09T10:20:38.143985Z`, while local `_next_payment_date` and actions `16005`/`16006` were already one full day ahead on August 10. The erroneous extra local advance caused by the zero-dollar event persisted.
- Order `13249` also exposed two separately recorded renewal-order defects: no `_paddle_transaction_id` or `_last_gateway_transaction_id` meta row and no order-item row despite a paid `$11.00` total. See `issues/light-plugin-SLT-MYA-03-paddle-renewal-order-omits-transaction-meta-and-line-item.md`.
- D07 proof: `/home/server-manager/slt-evidence/SLT-MYA-03-D07-followup.txt` and `/home/server-manager/slt-evidence/SLT-MYA-03-D07-order-13249.png`.
- No natural action was forced, no scheduler row was run, no local meta/order/action was hand-edited, and no non-SLT record was touched.

## D07 afternoon recurrence — 2026-08-09

The next exact remote gate independently confirmed that the erroneous extra local advance still has not healed. An immutable Mailpit/state baseline was captured at `16:15:41` site before Paddle's `16:20:38.143985` gate. Paddle then produced the sole post-gate recurring transaction `txn_01kzk0mpqxf7w1tpdcdyna8n9v`, billed at `10:21:06.173447Z`, completed/captured for `$11.00` on Visa ending `5556`. Exact transaction linkage resolved completed order `13480`, with admin/customer messages `5GSsdYtQfOf63LccnkfQaD` and `3jUrzcrxsS5qMrFvzDePXI`.

Immediately before this genuine charge, local `_next_payment_date` was already `2026-08-10 10:20:38Z` while Paddle's source-of-truth gate was the current D07 event. After the one legitimate charge, Paddle advanced to `2026-08-10T10:20:38.143985Z`, but ArraySubs advanced locally to `2026-08-11 10:20:38Z`, canceled unattempted actions `16005`/`16006`, and queued `16405`/`16406` for August 11. The one-day skew created by the zero-dollar payment-method-change misclassification therefore persists across another real recurring settlement. Full sanitized proof: `/home/server-manager/slt-evidence/D07-afternoon-paddle-12639.txt`.

## D08 afternoon recurrence — 2026-08-10

An immutable cursor `56ugkHpFaRuFCoyKaal00u` was revalidated at `16:15:47` site before the exact remote `16:20:38.143985` gate. Paddle then completed recurring transaction `txn_01kznk1e8p98v536yf9fnv41kv` once for `$11.00`, producing relationship order `13617` and exact mails `0Ku58j3hM2B97uYkb6HeOj` / `2uApI9cIbIcnehcFQhXMJA`. Paddle advanced only to D9, `2026-08-11T10:20:38.143985Z`; ArraySubs advanced locally from its already-skewed D9 value to D10, `2026-08-12 10:20:38Z`, canceled unattempted D9 actions `16405`/`16406`, and queued D10 actions `16776`/`16777`. The extra-day corruption therefore persists across a second subsequent real settlement. Full sanitized proof: `/home/server-manager/slt-evidence/D08-afternoon-paddle-12639.txt`.

## D09 afternoon recurrence — 2026-08-11

The extra-day schedule corruption persisted across a third subsequent genuine recurring settlement. Cursor `4cogsKDuQGssjfWbN3yhKp` was frozen at `16:15:47` site before Paddle's exact `16:20:38.143985` gate. Paddle transaction `txn_01kzr5e5vvgb9wgt2pw2r74e7d` then captured `$11.00` once and correctly advanced remote `next_billed_at` only to D10, `2026-08-12T10:20:38.143985Z`. ArraySubs instead advanced its already-skewed local date from D10 to D11, `2026-08-13 10:20:38Z`, cancelled unattempted D10 actions `16776/16777`, and queued D11 actions `17150/17151`. Exact order `13769` and mails `2JdFlL8eAwcYe2z6GxesKU` / `1f9kYMTz9XvdhZnbaeBQlS` prove one legitimate charge rather than duplication. Full sanitized proof: `/home/server-manager/slt-evidence/D09-afternoon-paddle-12639.txt`.
