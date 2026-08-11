# Paying one subscription invoice updates a different subscription's saved card

- **Severity:** high
- **Date found:** 2026-08-07
- **Watch day:** D05
- **Originating task:** `SLT-EML-02` / board card 67
- **Plan file:** `/home/server-manager/www/arrayhash/mirror-help.arrayhash.com/public/wp-content/plugins/qa/subscription-lifecycle-test/kanban/tasks/067-renewal-invoice-email-content-utc-6-due-date-and-a.md`

## Affected artifacts

- Intended subscription: 11959, product 11927 (`SLT Daily Core`), parent order 11949.
- Unintentionally changed subscription: 12655, product 12577 (`SLT Signup Fee Daily`), parent order 12654.
- Control subscription: 12234, product 12087 (`SLT Renewal Price Step`), parent order 12233.
- Manual renewal order: 13063 for subscription 11959.
- Earlier target/control renewal orders: 13047 for subscription 12655; 13050 for subscription 12234.
- WP user: 347, login `slt-core`, email `slt-core@example.test`, role `customer`.

## Environment and context

- Gateway: Stripe test.
- Checkout type: block checkout order-pay endpoint.
- Browser context: authenticated customer, session `customer-SLT-EML-02-pay2`.
- Route: `https://mirror-help.arrayhash.com/checkout/order-pay/13063/?pay_for_order=true&key=<redacted-order-key>`.
- Global settings: frozen QA-window defaults; no global setting changed.
- Per-subscription exception: `_auto_renew=off` had already been restored to its exact absent baseline before payment. No meta mutation was performed during the reproducing browser payment.
- Saved methods: token 31 / Mastercard ending 4444 was account default; token 17 / Visa ending 4242 remained available but non-default.

## Reproduction steps

1. On a customer with multiple active Stripe subscriptions, add a second saved card and make it default. Confirm only subscription 12655 inherits Mastercard 4444 while subscription 11959 remains on Visa 4242.
2. Let subscription 12655 renew naturally. Confirm relationship-exact order 13047 charges the Mastercard provider source and completes.
3. Create a manual renewal invoice for different subscription 11959. Confirm order 13063 is linked only to 11959 and is payable for $10.00.
4. Log in as `slt-core`, open the exact order-pay route for 13063, explicitly select the saved Visa ending 4242, and click **Pay for order**.
5. Confirm order 13063 completes and remains linked to subscription 11959.
6. Re-read subscription 12655's payment-method metas and the account token defaults.

## Expected result

Paying order 13063 may use the selected Visa for subscription 11959, but it must not change any other subscription. Subscription 12655 should retain Mastercard 4444 and its new provider payment-method ID; token 31 should remain default.

## Actual result

Order 13063 completed against subscription 11959, but subscription 12655 simultaneously reverted from the Mastercard provider source to the old Visa source, brand `visa`, last4 `4242`. Account token 31 / Mastercard 4444 remained default. Subscription 12655's `_payment_method_updated_at` stayed at the earlier 08:28:56 setup time and no new payment-method note was created, so the unrelated rewrite was silent.

The attribution to the 19:33 manual payment is an inference from the exact before/order/after evidence: order 13047 proves subscription 12655 charged the new source at 17:20; no later action for 12655 ran; the reversion was present immediately after the 19:33 payment of order 13063.

## Concrete proof

- Morning exact diff: `/home/server-manager/slt-evidence/SLT-MYA-02-pm-after.txt` and `/home/server-manager/slt-evidence/SLT-MYA-02-pm-diff.txt` show subscription 12655 alone changed Visa 4242 to Mastercard 4444.
- Safe portal evidence: `/home/server-manager/slt-evidence/SLT-MYA-02-02-methods-default.png` and `/home/server-manager/slt-evidence/SLT-MYA-02-03-detail-row.png` show Mastercard 4444 as default/on subscription 12655.
- Target renewal UI: `/home/server-manager/slt-evidence/SLT-MYA-02-04-renewal-order-meta.png` shows completed $9.00 order 13047 linked to subscription 12655.
- Control renewal UI: `/home/server-manager/slt-evidence/SLT-MYA-02-04b-control-renewal-order.png` shows completed $20.00 order 13050 linked to subscription 12234.
- Manual pay page/receipt: `/home/server-manager/slt-evidence/SLT-EML-02-03-order-pay-page.png` and `/home/server-manager/slt-evidence/SLT-EML-02-04-order-paid.png`.
- Exact reconciliation: `/home/server-manager/slt-evidence/SLT-MYA-02-D05-evening-renewals.txt` and `/home/server-manager/slt-evidence/SLT-EML-02-D05-evening-payment.txt`.
- Order 13047: completed, $9.00, cycle 3, both subscription relationship metas 12655, Stripe source equal to token 31 / Mastercard 4444.
- Order 13050: completed, $20.00, cycle 5, both relationship metas 12234, Stripe source equal to token 17 / Visa 4242.
- Order 13063: completed, $10.00, both relationship metas 11959, selected Stripe source equal to token 17 / Visa 4242.
- Mailpit exact subjects and recipients:
  - `0mcQC5tfTKYwBhjzMn66pT` — `[mirror-help.arrayhash.com]: You've got a new order: #13047` — `admin@mirror-help.arrayhash.com`.
  - `5CB3n5XA76PpXYKGnPuChQ` — `[mirror-help.arrayhash.com] Payment received for subscription #12655` — `slt-core@example.test`.
  - `52iBJa6IqDmQB7WegnsEAA` — `[mirror-help.arrayhash.com]: You've got a new order: #13050` — `admin@mirror-help.arrayhash.com`.
  - `7YBWKCgFiOa2WULju9KRMJ` — `[mirror-help.arrayhash.com] Payment received for subscription #12234` — `slt-core@example.test`.
  - `61uMOBgJpO2BBvu9HMUHyw` — `[mirror-help.arrayhash.com]: You've got a new order: #13063` — `admin@mirror-help.arrayhash.com`.
  - `5hm1LQfe2IKo0vIamcv9kd` — `[mirror-help.arrayhash.com] Payment received for subscription #11959` — `slt-core@example.test`.
- Action Scheduler: 15254 and 15267 completed via WP Cron; no action was forced.
- The reproducing order-pay page and paid receipt rendered normally; no relevant browser console error or failed network response was observed. The only console messages were routine WooCommerce dependency information and JQMIGRATE output.

## Scope notes and counterexamples

- The same D05 renewal successfully charged subscription 12655 with Mastercard 4444 before the later cross-subscription rewrite, proving the new token itself works.
- Control subscription 12234 correctly renewed with Visa 4242 and remained unchanged.
- The wrong rewrite is limited to another SLT subscription owned by the same customer; no non-SLT subscription or user was touched.
- This finding does not claim the account default changed: token 31 / Mastercard 4444 remained default. The defect is the silent payment-method rewrite on unrelated subscription 12655.

## D06 persisted impact

The next natural renewal proved that the silent rewrite affected a real charge, not just display metadata:

- Natural action `15633` for subscription `12655` was scheduled `2026-08-08 11:19:21Z` and completed via WP Cron at `11:20:17Z`; no action was forced.
- Relationship-exact order `13223` completed for `$9.00` at `2026-08-08 11:20Z` and remains linked to subscription `12655`.
- Safe token/source comparison (provider IDs not printed): order `13223`'s Stripe source and subscription `12655`'s current gateway source both match token `17`, Visa ending `4242`; neither matches token `31`, Mastercard ending `4444`, even though token `31` remains the account default.
- Exact messages: `5WyEqrEAQs56TRriWMtVPl`, `[mirror-help.arrayhash.com]: You've got a new order: #13223`, to admin; `0E69hBWfCpCsxIL6T7CyTL`, `[mirror-help.arrayhash.com] Payment received for subscription #12655`, to `slt-core@example.test`, body amount `$9.00` and next date 9 August 2026 16:44 UTC+6.
- This is a same-flow temporal counterexample: the prior renewal order `13047` successfully charged token `31` / Mastercard `4444`; after paying different subscription order `13063`, the next renewal `13223` charged token `17` / Visa `4242` instead.

## D07 recurrence — unrelated manual renewal payment writes into subscription 12655

The same cross-subscription attribution defect recurred on 2026-08-09. SLT-MYA-04's relationship-exact order `13442` completed for subscription `12655` with transaction `ch_3U2PByJG5OzSNVs21G72Ggvi` at `05:22:41Z`. Later, manual payment of relationship-exact order `13466` for subscription `11959` completed with transaction `ch_3U2TD7JG5OzSNVs200AFSQM2`. Subscription `12655` was then silently overwritten to `_last_gateway_transaction_id=ch_3U2TD7JG5OzSNVs200AFSQM2`, and received notes `13470`/`13471` saying Stripe payment was confirmed for unrelated order `13466`.

The exact `12655` cycle/order set itself did not gain a second order, and its payment count/next date remained `5` / `2026-08-10 10:44:10Z`; this isolates the recurrence to cross-subscription gateway metadata and note attribution rather than a duplicate renewal. Evidence: `/home/server-manager/slt-evidence/D07-afternoon-SLT-MYA-04-cross-subscription.txt`.
