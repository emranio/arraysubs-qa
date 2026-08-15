# Paddle payment-method update leaves the local card display stale

- Severity: medium
- Date found: 2026-08-08; confirmed 2026-08-09
- Status: resolved 2026-08-14
- Watch day: D06 with D07 settlement follow-up
- Originating task: `SLT-MYA-03` / card `#94`
- Plan file: `kanban/tasks/094-update-the-paddle-payment-method-and-prove-the.md`

## Affected records

- Subscription: `12639` (`SLT Paddle Daily`, arraysubs-active)
- Product: `12112` (`SLT Paddle Daily`)
- Parent order: `12629`
- Payment-method-change order: `13214`
- Later genuine renewal order: `13249`
- Paddle subscription: `sub_01kz8q1025tryjfgxvn5e3v4gf`
- Paddle payment-method-change transaction: `txn_01kzfj2d3mtsbasp6r8s8zmrw8`
- Paddle recurring transaction: `txn_01kzge82gz7pyqne0x1prnmf6a`

## Affected user

- WP user ID: `352`
- Login: `slt-paddle`
- Email: `slt-paddle@example.test`
- Role: Customer

## Gateway, checkout, and settings context

- Gateway: Paddle sandbox
- Checkout type: N/A; customer account and Paddle-hosted payment-method management flow
- Card fixtures recorded safely: previous Visa ending `4242`; replacement Paddle sandbox Visa debit fixture ending `5556`. No full card number is retained.
- Non-default settings: none. The frozen suite baseline remained in force and no settings bracket was opened.

## Routes and browser contexts

- Payment methods: `https://mirror-help.arrayhash.com/my-account/payment-methods/`
- Add payment method: `https://mirror-help.arrayhash.com/my-account/add-payment-method/`
- Subscription detail: `https://mirror-help.arrayhash.com/my-account/view-subscription/12639/`
- Paddle update route: temporary authenticated management URL obtained for the exact Paddle subscription; the token and full URL were not retained.
- Admin subscription detail: `https://mirror-help.arrayhash.com/wp-admin/admin.php?page=arraysubs-mainadmin#/subscriptions/detail/12639`
- Browser sessions: `customer-MYA-03-SLT-MYA-03` and `admin-SLT-MYA-03`

## Reproduction steps

1. Log in as `slt-paddle` and open `/my-account/payment-methods/`; observe that no saved Paddle payment method is listed.
2. Open `/my-account/add-payment-method/`; observe that the available form is not a Paddle saved-method/update surface.
3. Open `/my-account/view-subscription/12639/`; observe the Card on File row showing Visa ending `4242` and use its Update payment method control.
4. On the temporary Paddle-hosted management page, submit the documented sandbox replacement card ending `5556` and wait for the successful return state. Do not capture populated hosted fields.
5. Re-read the exact Paddle subscription/transaction and the local subscription card metas within five minutes.
6. After the next natural recurring settlement, re-read both sides again to rule out delayed reconciliation.

## Expected result

After Paddle accepts the new payment method, the local customer surface should either show the current Paddle card safely or make clear that the displayed value is not current. A subsequent successful recurring charge on the new card should reconcile any delayed local display state.

## Actual result

- Paddle accepted the new Visa ending `5556`, and the later natural `$11.00` recurring charge used that card successfully.
- The generic payment-methods page continued to expose no saved Paddle method.
- The exact subscription continued to display locally stored Visa ending `4242`.
- `_payment_method_brand=visa`, `_payment_method_last4=4242`, and `_payment_method_title=Paddle` remained unchanged; `_payment_method_updated_at` remained absent through the D07 settlement follow-up.
- The customer therefore sees stale card data even though Paddle's source of truth and the actual captured renewal use ending `5556`.

## Concrete proof

- Before/after local meta: `/home/server-manager/slt-evidence/SLT-MYA-03-before.txt` and `/home/server-manager/slt-evidence/SLT-MYA-03-after.txt`
- Sanitized Paddle state: `/home/server-manager/slt-evidence/SLT-MYA-03-paddle-api-before.json` and `/home/server-manager/slt-evidence/SLT-MYA-03-paddle-api-after.json`
- Customer UI screenshots:
  - `/home/server-manager/slt-evidence/SLT-MYA-03-01-methods-empty.png`
  - `/home/server-manager/slt-evidence/SLT-MYA-03-02-detail-row.png`
  - `/home/server-manager/slt-evidence/SLT-MYA-03-03-update-page.png`
- Admin note screenshot: `/home/server-manager/slt-evidence/SLT-MYA-03-04-notes.png`
- D07 natural-settlement evidence: `/home/server-manager/slt-evidence/SLT-MYA-03-D07-followup.txt`
- Sanitized recurring transaction proof records `status=completed`, `origin=subscription_recurring`, total `1100` USD minor units, captured payment, and Visa last4 `5556`.
- The D07 local read still records Paddle / Visa / `4242` with no `_payment_method_updated_at`.

## Exact gates and Mailpit correlation

- Hosted update submitted: `2026-08-08 02:10:02Z` / `08:10:02` site.
- Natural remote billing gate: `2026-08-08T10:20:38.143985Z` / `16:20:38` site.
- D07 settlement observation: `2026-08-09 00:16–00:23Z` / `06:16–06:23` site.
- Update-event admin message `2AmbBF9Gr3ahDaee9jMKcH`: `[mirror-help.arrayhash.com]: You've got a new order: #13214` to `admin@mirror-help.arrayhash.com`.
- Update-event customer message `4E4IXtfwjCT1FJZSE9B1mk`: `[mirror-help.arrayhash.com] Payment received for subscription #12639` to `slt-paddle@example.test`.
- Natural-renewal admin message `3k11t5ipr2GSH5xTNFE483`: `[mirror-help.arrayhash.com]: You've got a new order: #13249` to `admin@mirror-help.arrayhash.com`.
- Natural-renewal customer message `57lxgHohFqevl55xhVeR3P`: `[mirror-help.arrayhash.com] Payment received for subscription #12639` to `slt-paddle@example.test`.

## Scope notes and counterexamples

- This is not a failed hosted update: Paddle transaction `txn_01kzfj2d3mtsbasp6r8s8zmrw8` records the change, and transaction `txn_01kzge82gz7pyqne0x1prnmf6a` proves the new card was actually charged.
- The subscription detail does provide a route into Paddle's hosted update flow; the defect is the absent generic Paddle saved-method representation and the stale local card display after return and settlement, not total inability to update the remote card.
- No account, card meta, order, subscription, action, or setting was hand-edited. No non-SLT object was touched.
- The resolved unexpected zero-dollar renewal side effects are tracked separately in `issues/done-critical-plugin-SLT-MYA-03-payment-method-change-treated-as-zero-dollar-renewal.md`.

## D07 afternoon recurrence — 2026-08-09

The next natural Paddle recurring transaction, `txn_01kzk0mpqxf7w1tpdcdyna8n9v`, again captured `$11.00` using Visa ending `5556`. It produced relationship-exact completed order `13480` and payment-success mail `3jUrzcrxsS5qMrFvzDePXI`. After settlement, local subscription `12639` still stores `_payment_method_brand=visa` and `_payment_method_last4=4242`, with no `_payment_method_updated_at` row. This second real charge rules out a one-cycle delayed refresh: Paddle and the actual payment remain on `5556`, while the local customer/admin surfaces remain backed by stale `4242` metadata. Full sanitized proof: `/home/server-manager/slt-evidence/D07-afternoon-paddle-12639.txt`.

## D08 afternoon recurrence — 2026-08-10

Paddle recurring transaction `txn_01kznk1e8p98v536yf9fnv41kv` captured another `$11.00` payment on Visa ending `5556`. After exact order `13617` completed, the live customer route `/my-account/view-subscription/12639/` still rendered `Visa ending in 4242`, matching unchanged local `_payment_method_last4=4242`. Screenshot: `/home/server-manager/slt-evidence/D08-Paddle-12639-subscription-after.png`; sanitized transaction/state proof: `/home/server-manager/slt-evidence/D08-afternoon-paddle-12639.txt`.

## D09 afternoon recurrence — 2026-08-11

The fourth observed post-update recurring settlement again used the remote Visa ending `5556`: exact Paddle transaction `txn_01kzr5e5vvgb9wgt2pw2r74e7d` captured `$11.00` once and completed relationship order `13769`. After settlement, local `_payment_method_last4` remained `4242`, and the authenticated customer route `/my-account/view-subscription/12639/` visibly rendered `Visa ending in 4242`. Screenshot: `/home/server-manager/slt-evidence/D09-Paddle-12639-subscription-after.png`; complete sanitized gate/order/mail proof: `/home/server-manager/slt-evidence/D09-afternoon-paddle-12639.txt`.

## Resolution — 2026-08-14

The report was confirmed against the original task, current local metadata, the exact Paddle subscription/customer binding, and both the payment-method-change and later recurring transactions. It was not a false positive. Paddle's immutable completed payment-method-change transaction contains Visa ending `5556`, while the local subscription still stored `4242` because `transaction.completed` was normalized correctly but the Paddle handler deliberately acknowledged that origin without persisting its safe card details.

The Paddle handler now processes only a signed, completed `subscription_payment_method_change` transaction whose transaction ID is well formed and whose Paddle subscription and customer IDs exactly match the locally bound subscription. It validates a card-only display schema, persists only type/brand/last-four/expiry/source/update time through the canonical `GatewayMetaStore`, and records a nanosecond Paddle event watermark under the shared mutation lock so delayed or reordered events cannot rewind the display. Detach protection, retry-state reset, the existing payment-method-updated hook, and a private safe note remain aligned with the other automatic gateways. The watermark was added to the core allowlist, detach cleanup, and detached-write protection.

Live verification used Paddle transaction `txn_01kzfj2d3mtsbasp6r8s8zmrw8` without exposing credentials or full card data. A correctly signed REST replay returned `200`; its exact duplicate returned `200` with `duplicate=true`; a unique older event returned `200` without changing `5556`; and mismatched-customer plus malformed-last-four controls returned `500` with their claims released and no metadata change. The subscription stayed `arraysubs-cancelled`, its eight linked orders and transaction IDs were byte-for-byte unchanged, `_completed_payments=7`, `_next_payment_date` and `_pending_renewal_order_id` stayed empty, the two historical Action Scheduler rows were unchanged, and Mailpit stayed at message ID `1zPxE6FmuLNdLZQPE1aist`.

After a fresh authenticated navigation, `/my-account/view-subscription/12639/` renders `Visa ending in 5556`; browser errors were empty and console output contained only the existing jQuery Migrate informational message. Evidence: `/home/server-manager/slt-evidence/FIX-PADDLE-SLT-MYA-03-before.png` and `/home/server-manager/slt-evidence/FIX-PADDLE-SLT-MYA-03-after.png`. The generic WooCommerce payment-methods page remains intentionally token-based; no fake WooCommerce token is created for Paddle's subscription-scoped provider method.
