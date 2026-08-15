# Payment migration regression preflight

- QA task: `#136 stage-0: Stripe and Paddle checkout preflight baseline`
- Agent: `cape-oaken`
- Staging origin: `https://mirror-help.arrayhash.com`
- Baseline cutoff: `2026-08-15 06:59:00 UTC` (`08:59 Europe/Berlin`)
- Source/plugin activation/product/settings changes during baseline collection: none
- Temporary checkout window: enabled afterward through the authenticated browser under explicit root authorization; restoration is still pending

## Environment and gateway readiness

| Check | Result |
|---|---|
| WordPress / PHP | WordPress `7.0.2`; PHP CLI `8.3.31` |
| ArraySubs | Active, `1.8.12` |
| ArraySubsPro | Active, `1.1.3` |
| WooCommerce | Active, `10.9.4` |
| WooCommerce Stripe | Active, `10.8.4` |
| Stripe | Enabled, test mode, test publishable/secret/webhook fields present; ArraySubs secondary endpoint and secret present |
| Paddle | Enabled, sandbox/test mode, API key/client token/seller/webhook fields present |
| Gateway Health UI | Paddle and Stripe both `Connected (Test Mode)`; no browser errors |
| Other gateways | PayPal and Mollie disabled on this staging environment |

Secret-safe option fingerprints before the shared checkout window:

- `arraysubs_settings`: `ef5e20f24ae03fcab4967dbe713bb7c1fb2fb5667a3d01600e4c38ccf166b3ae`
- `woocommerce_stripe_settings`: `fb63cc191988edf4ba749d4f983e2004742823544b06101dc10f19cc7b7bfb87`
- `arraysubs_stripe_extras`: `55647a87f8d3ce8b75cde717b4923e53f61bbcc06ae47e03f81cf3f4c93cf289`
- `woocommerce_arraysubs_paddle_settings`: `bf12616dc7011f4fbe005e7b2f0e6f7af324a12ad8620a051554486d664a2a32`

No credential or token values were exported.

## Recommended products

| Purpose | ID | Product | Price/billing | Tax and shipping | Readiness |
|---|---:|---|---|---|---|
| Paddle subscription | `12112` | SLT Paddle Daily | `$11.00` every day | Taxable, virtual, no shipping | Published; sandbox price/product binding state `ready` |
| Stripe subscription | `200` | Standard Weekly | `$19.99` every week | Taxable, physical, shipping required | Published; storefront `Subscribe Now` verified |
| Monthly edge | `197` | Basic Monthly | `$29.99` every month | Taxable, physical, shipping required | Published |
| Normal mixed-cart item | `447` | Standard Tee | `$15.00` one-time | Taxable, physical, shipping required | Published; storefront `Add to cart` verified |
| Normal fallback item | `46` | Hat | `$12.00` one-time | Taxable, physical, shipping required | Published |

`Plain Mug` (`266`, `$10`) is not marked as a subscription, but retains old lifetime subscription metadata and is virtual. Standard Tee is the cleaner normal-item fixture.

## Checkout and cart

- Global `Allow Mixed Checkout`: enabled.
- `Allow Different Billing Cycles`: enabled.
- `Allow Multiple Subscriptions in Cart`: disabled.
- Cart page `7` is published and uses the WooCommerce Cart block.
- Checkout page `8` is published and uses the WooCommerce Checkout block.
- My Account page `9` is published and uses the WooCommerce shortcode.
- A clean guest browser added Standard Weekly and Standard Tee to the same cart.
- Cart displayed both items and an estimated total of `$34.99`.
- Block checkout displayed Credit / Debit Card (test mode), Paddle, Direct bank transfer, and Check payments; it retained both line items and total `$34.99`.
- Browser error collection was empty. Console output contained informational WooCommerce/JQMigrate messages only.

## Renewal sync baseline

Before the shared QA window:

- `Sync Renewals to Next Billing Cycle`: enabled.
- `First Charge`: `Charge the full recurring amount` (`full`).
- Renewals group SHA-256: `fce734a635d436d3a737b3b2dad18a0f196ab62656354140f7d4c172edce9a24`.

The shared window temporarily disables renewal sync so ordinary gateway purchases are not moved onto the next cycle boundary. The stored first-charge value remains `full`.

## Action Scheduler health

Snapshot at `2026-08-15 07:22:44 UTC`:

| Scope | Pending | Complete | Failed | Canceled | In progress/stuck |
|---|---:|---:|---:|---:|---:|
| All actions | 335 | 9,349 | 26 | 60 | 0 |
| ArraySubs hooks | 318 | 5,385 | 12 | 60 | 0 |

- ArraySubs pending actions already due: `0`.
- ArraySubs failures in the preceding 24 hours: `0`.
- The 12 historical ArraySubs failures are old fixtures from May-July 2026; latest was `2026-07-23 03:36:06 UTC`.
- Renewal generation, payment processing, reminders, gateway reconciliation, webhook cleanup, and Paddle sweeps all have future pending actions.

## Data baselines before concurrent checkout QA

Using the `06:59 UTC` cutoff to exclude the concurrently running Stripe/Paddle agents:

- WooCommerce orders: `692` total.
  - canceled 250; checkout-draft 92; completed 248; on-hold 16; pending 16; processing 48; refunded 22.
- ArraySubs subscriptions: `403` total.
  - active 19; canceled 355; expired 15; pending 14.
- Active subscriptions by stored gateway:
  - Stripe 13; Paddle 1; BACS 4; no gateway 1.
- Webhook events: `363` total; numeric ID range `136..1129`.
  - Stripe 257; Paddle 106.
  - Latest baseline event was numeric ID `1129` at `2026-08-15 02:20:31 UTC`; external event identifiers were recorded only as SHA-256 fingerprints.
- Mailpit latest baseline ID: `4NGz5mAhfB83ELxbV6TVsp`.
- Mailpit service was active/enabled and its localhost API/SMTP listeners were healthy.

Concurrent-flow context at `07:23-07:25 UTC`: checkout agents had already increased Gateway Health to Stripe 14 and Paddle 2, and the webhook table to 375. Those are not baseline values.

## Debug-log baseline

- Captured byte offset: `816551`.
- File mtime at capture: `2026-08-15 09:13:51 +02:00`.
- Latest line timestamp: `2026-08-15 07:13:51 UTC`.
- That latest line is a WP-CLI `eval` parse error from a QA diagnostic command, not an ArraySubs/ArraySubsPro runtime path.
- No new product runtime fatal/warning was present at the captured offset.

## Temporary shared checkout window

The initial rule blocked every fresh customer from every product because it required any active subscription. Root authorized a narrow browser mutation after the baseline was recorded.

Member Access rule:

- Route: `/wp-admin/admin.php?page=arraysubs-mainadmin#/members-access` -> `Shop Access`.
- Rule ID: `rule_1784662676378_maa3te08s` (`Private member store`).
- Original rule SHA-256: `48a238abb67869d4308fc3726dc5cd27237e1622b9ed6f9862057e0fac4526ac`.
- Original exclusions: `[]`.
- Temporary exclusions: `[200, 197, 12112, 447]`.
- Temporary rule SHA-256: `85054c03a983d839e876edcedb697d20359a6ca2a773bf39e91ffb9dd37ec0c5`.

General settings:

- Route: `/wp-admin/admin.php?page=arraysubs-mainadmin#/settings/general` -> `Renewal Sync`.
- Temporary change: `renewals.sync_to_billing_cycle` from `true` to `false`.
- Guard: `renewals.sync_first_charge_mode` remains `full`.

Proof of scope:

- Current temporary `arraysubs_settings` SHA-256: `d006100981521a27dae1a5f6cc547195c635a960ef8317dccd842d88f013d546`.
- Reconstructing only the original exclusions and sync boolean produces exactly the original settings SHA-256 `ef5e20f24ae03fcab4967dbe713bb7c1fb2fb5667a3d01600e4c38ccf166b3ae`.
- Reconstructing only the rule exclusions produces exactly the original rule SHA-256.
- Therefore no other semantic field changed.
- A brand-new cache-busted guest product request showed `Subscribe Now`; runtime evaluation for fresh Stripe user `463` returned purchasable `true`. No shared cache purge was required.

## Restoration proof

After both gateway matrices completed, the temporary window was restored through the same authenticated browser:

- `rule_1784662676378_maa3te08s.exclusion_product_ids`: `[200, 197, 12112, 447]` -> `[]`.
- `renewals.sync_to_billing_cycle`: `false` -> `true`.
- `renewals.sync_first_charge_mode`: remained `full`.
- Final `arraysubs_settings` SHA-256: `ef5e20f24ae03fcab4967dbe713bb7c1fb2fb5667a3d01600e4c38ccf166b3ae` — exact original match.
- Final Member Access rule SHA-256: `48a238abb67869d4308fc3726dc5cd27237e1622b9ed6f9862057e0fac4526ac` — exact original match.
- Final Stripe settings SHA-256: `fb63cc191988edf4ba749d4f983e2004742823544b06101dc10f19cc7b7bfb87` — unchanged.
- Final Stripe extras SHA-256: `55647a87f8d3ce8b75cde717b4923e53f61bbcc06ae47e03f81cf3f4c93cf289` — unchanged.
- Final Paddle settings SHA-256: `bf12616dc7011f4fbe005e7b2f0e6f7af324a12ad8620a051554486d664a2a32` — unchanged.
- All restoration checks passed, including empty exclusions, sync enabled, and first-charge mode preserved.
- Browser error collection remained empty after both saves.

## Evidence

- `screenshots/gateway-health.png`
- `screenshots/checkout-subscription-ready.png`
- `screenshots/cart-mixed-ready.png`
- `screenshots/checkout-mixed-cart-ready.png`
- `screenshots/checkout-mixed-payment-ready.png`
- `screenshots/qa-window-member-access-before-save.png`
- `screenshots/qa-window-member-access-saved.png`
- `screenshots/qa-window-renewal-sync-before-save.png`
- `screenshots/qa-window-renewal-sync-saved.png`
- `screenshots/restoration-member-access-before-save.png`
- `screenshots/restoration-member-access-saved.png`
- `screenshots/restoration-renewal-sync-before-save.png`
- `screenshots/restoration-renewal-sync-saved.png`

Preflight result: **PASS**. The temporary checkout window was restored exactly after the two gateway matrices.
