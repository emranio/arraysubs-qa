---
id: 2
title: 'Classic checkout parity: same SLT Daily Core purchase, meta diffed field-by-field against CHK-01'
status: done
priority: critical
created: 2026-08-02T03:43:03.051717636+02:00
updated: 2026-08-05T21:37:49.277450219+02:00
started: 2026-08-02T15:27:09.960911311+02:00
completed: 2026-08-02T15:27:09.960911311+02:00
tags:
    - checkout
    - day-00
due: "2026-08-02"
estimate: 1h15m
depends_on:
    - 1
    - 10
class: standard
---

> **SLT-CHK-02** · group `checkout` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Repeat SLT-CHK-01 on the **classic** `[woocommerce_checkout]` page and prove the surface changes nothing: stable subscription contract fields must match CHK-01, while identity, payment, schedule-action IDs and timestamps are validated by shape. Preserve a raw diff as diagnostic evidence and a normalized invariant comparison as the pass assertion.

## Scope
- Gateway: Stripe test
- Checkout: classic
- Account: new registered (created here)
- Plugins: both

## Preconditions
- SLT-CHK-01 done; `/home/server-manager/slt-evidence/SLT-CHK-01-sub-meta.txt` exists.
- `/slt-classic-checkout` (SLT-SETUP-01) works; page 8 untouched.
- **Deliberate deviation:** buyer is `slt-core2`, not `slt-core`. At the frozen `one_per_customer=false` baseline, a second `slt-core` checkout would create a duplicate rather than migrate; that would make the parity/control records ambiguous. This task therefore **creates** `slt-core2` and leaves the CHK-01 owner untouched.
- Run after 12:00 site, same day as CHK-01, before any renewal fires (both records must read `_completed_payments=1`).

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core, day/1, $10.00 |
| Account | slt-core2 / slt-core2@example.test / `SltQa!2026#Pass` (Customer); billing 1 SLT Way, Dhaka BD 1207 |
| Card | 4242 4242 4242 4242, `12/34`, CVC 123 |
| Session | `cust-SLT-CHK-02` |

## Steps
1. `PRE=$(mailpit-agent latest-id)`; record `SUBCOUNT_BEFORE`.
2. As admin at `user-new.php` create `slt-core2`, **Send User Notification unticked**, role Customer; set its Customer billing address. Record `UID`.
3. `agent-browser --session cust-SLT-CHK-02 open ".../my-account/"` → log in as `slt-core2`; open `/cart/`, assert EMPTY; open `/product/slt-daily-core/` → **Add to cart**.
4. Open `/slt-classic-checkout` → `snapshot -i`; confirm **classic** markup (`#order_review`, `.woocommerce-billing-fields`), **Total $10.00**, no tax row, billing prefilled; shots `-01-classic.png` and `-02-gateways.png` (compare the gateway list with CHK-01's).
5. Select **Credit Card (Stripe)**, fill the card, **Place order**; wait for order-received; record `ORDER`; shot `-03-received.png`; capture `console`/`network`.
6. `mailpit-agent wait-new "$PRE" 180 "is active"`; inspect and classify the complete owner-filtered delta after `$PRE`.
7. Resolve the exactly one subscription ID from `$ORDER`'s `_subscription_ids`, cross-check `_parent_order_id`, customer, product, and the recorded count delta, then publish it under canonical alias **`SUB_CORE2`**; never select by recency. Dump `wp post meta list "$SUB_CORE2" --allow-root` to `/home/server-manager/slt-evidence/SLT-CHK-02-sub-meta.txt` and save the raw diagnostic diff against CHK-01 as `/home/server-manager/slt-evidence/SLT-CHK-02-diff.txt`. Separately compare the invariant keys listed in expected result 3 as a key-sorted map, using the effective value for duplicated metadata rows.
8. Compute `k` for `$SUB` (REF-01 §0); check **Tools → Scheduled Actions** → Pending for `$SUB`; shot `-04-pending.png`.
9. Append `UID`/`SUB_CORE2`/`ORDER` to the registry; close this session.
10. D1/D2 daily-watch handoff (not a remaining criterion on this completed checkout card), force nothing: verify this subscription's own schedule — invoice action `13702` at 2026-08-03 13:04:26Z (19:04:26 site) and renewal action `13703` at 2026-08-03 19:04:26Z (2026-08-04 01:04:26 site). Do not treat CHK-01's earlier clock as this record's cadence.

## Expected results
1. `slt-core2` exists with role `customer`; the checkout created exactly one new `arraysubs_data` post, status `arraysubs-active`.
2. Order `completed`, `total_amount=10.00`, `currency=USD`, `payment_method=stripe`, zero tax items; no PHP notice or console error; page 8 still renders the block checkout. WooCommerce auto-completes a paid virtual-only order.
3. Equal effective values in both subscriptions: `_billing_period`, `_billing_interval`, `_subscription_length`, `_trial_length`, `_trial_period`, `_subscription_price`, `_recurring_amount`, `_currency`, `_signup_fee`, `_product_id`, `_variation_id`, `_quantity`, `_payment_method`, `_payment_method_title`, `_payment_gateway`, `_gateway_status`, `_payment_method_type`, `_payment_method_brand`, `_payment_method_last4`, `_payment_method_expiry_month`, `_payment_method_expiry_year`, `_completed_payments`, `_enable_different_renewal_price`, `_different_renewal_price`, `_different_renewal_price_after`.
4. Expected dynamic differences are validated by shape rather than byte equality: customer/order IDs and emails, start/next/last-payment timestamps, Stripe customer/payment-method/transaction IDs, `_billing_address`, cached-product `cached_at`, and both renewal action IDs. The raw diff may also expose duplicate-row-count differences; record those in an issue file without failing otherwise-equal effective values.
5. `_next_payment_date = _start_date + 24h`; `_parent_order_id=$ORDER`; order `_subscription_ids=[$SUB]`; AS legs at `+k` and `+k−6h` as in CHK-01.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC New order | paid checkout | admin | `New order #$ORDER` | Complete owner-filtered delta after `$PRE`; save/show the exact matching id |
| 2 | WC Completed order | paid virtual-only order → completed | slt-core2@example.test | `is on its way` | Complete owner-filtered delta after `$PRE`; save/show the exact matching id |
| 3 | `new_subscription` | → `arraysubs-active` | slt-core2@example.test | `subscription #$SUB is active` | `mailpit-agent wait-new "$PRE" 180 "is active"` |
| 4 | `admin_new_subscription` | same | admin | `New subscription #$SUB` | Complete owner-filtered delta after `$PRE`; save/show the exact matching id |
| 5 | WP New User Registration | admin user creation | admin | `New User Registration` | Unticking notification suppresses only the customer account email; the admin registration notice is still expected |
| 6 | NONE EXPECTED | user creation + signup | — | — | no customer `Your account on`; as CHK-01, no `Invoice for subscription`/`Payment received`/`renews soon` |

## Evidence to capture
- Shots 01–04; `UID`, `SUB`, `ORDER`, `k`; `SLT-CHK-02-sub-meta.txt`, `-diff.txt`; `PRE` + Mailpit IDs.

## Pass criteria
- [x] `slt-core2` created; exactly one admin registration notice and no customer account mail; classic shortcode checkout rendered
- [x] One new `arraysubs-active` sub; order `completed`, $10.00, no tax line
- [x] Normalized invariant comparison is empty; raw dynamic diff is retained as diagnostic evidence
- [x] `_next_payment_date = _start_date + 24h`; AS legs at `+k` / `+k−6h`
- [x] Mails 1–5 present; row-6 negatives hold

## Isolation / teardown
- Creates `slt-core2`: add it to the account matrix and SETUP-99B's deletion list; it is bound to SLT Daily Core. The sub stays live for the watch, cancelled by SETUP-99A on D10. Nothing global changed; cart left empty.

---

### Verified environment facts (2026-08-01/02 — do not re-derive)

- **Nothing fires at `_next_payment_date`.** Every scheduled leg is shifted by
  `crc32('arraysubs-spread-'.$subscription_id) % 21600` (0-6 h). Charge fires at `due + offset`,
  invoice at `due + offset - 6h`. The stored date never moves. **Assert a window, not a point.**
- Currency `USD`. **Taxes are OFF** (`woocommerce_calc_taxes = no`) — never assert a tax line.
- Orders use **HPOS** (`wp_wc_orders`), not `wp_posts`.
- `woocommerce_enable_guest_checkout = yes`, but ArraySubs force-requires registration for
  **subscription** carts via `woocommerce_checkout_registration_required`
  (`SubscriptionCheckout/Services/Hooks.php:103`, `CheckoutHelpersTrait.php:93-100`).
- WooCommerce **grouped** products have zero handling in either plugin — grouped tasks are
  exploratory: document behaviour, do not assert a spec.
- WP-Cron runs every minute from `/etc/cron.d/mirror-help-arrayhash-wordpress`. Scheduled actions
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence and do not force a natural-watch action.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run a bare or `--hooks=` Action Scheduler drain. Run one known action ID at a time only when the task explicitly authorizes it and after the required queue pre-flight; natural-watch actions are never forced.
- Evidence goes under `/home/server-manager/slt-evidence/` using task-key-prefixed filenames.

---

## Execution verdict — 2026-08-02 — PASS WITH FINDING

- Admin browser creation produced user `357`, `slt-core2`, role `customer`, with matching complete billing and shipping profiles. Unticking customer notification correctly suppressed the customer account mail; WordPress's expected admin registration mail was `2RsG3GSQ2AoDMTJpx88byu`.
- The real `/slt-classic-checkout/` shortcode surface rendered classic billing fields and order review, four gateways, USD `10.00`, no tax row, prefilled billing, and Stripe's secure payment frame.
- Checkout created exactly one subscription: canonical `SUB_CORE2=11991`, `arraysubs-active`; parent order `11990` is `completed`, USD `10.00`, Stripe, `created_via=checkout`, zero tax items. Classic checkout XHR and order-received document both returned HTTP `200`; browser errors were empty.
- Linkage is exact: `_parent_order_id=11990`, `_order_ids=[11990]`, order `_subscription_ids=[11991]`.
- Start `2026-08-02 13:23:13Z`; due `2026-08-03 13:23:13Z` (86,400 seconds); `_completed_payments=1`; `k=20473`. Pending invoice action `13702` is `2026-08-03 13:04:26Z`; pending renewal action `13703` is `2026-08-03 19:04:26Z`.
- All 25 normalized invariant effective values equal the block control; `/home/server-manager/slt-evidence/SLT-CHK-02-normalized-invariant-diff.txt` is empty (`[]`). The raw diagnostic diff is retained at `SLT-CHK-02-diff.txt`.
- Checkout emitted exactly four more messages: customer completed order `1YZVmwHb9a4SO5bRpI8YqI`, admin new order `7kiceFoiQW9yKiv786UFIb`, customer active subscription `5TxT9DhBqSYWcfHBEdFms4`, admin new subscription `2h3RVWZLXMuqfcfPs39iym`. No customer account, renewal, invoice, reminder, trial or failure mail appeared.
- The raw surface difference—classic subscription omitted `_shipping_address`—was fixed and closed in `issues/done-low-SLT-CHK-02-classic-checkout-omits-shipping-address-meta.md`. Gateway duplicate-row behavior is closed in `issues/done-critical-plugin-SLT-OBS-01-duplicate-gateway-postmeta-rows.md`; `11991` had no duplicate keys.
- Evidence: `/home/server-manager/slt-evidence/SLT-CHK-02-00-empty-cart.png`, `-01-classic.png`, `-02-gateways.png`, `-02b-ready.png`, `-03-received.png`, `-04-pending.png`, both meta/diff files, and registry page `11847`. Persistent cart is empty.

## D1 invoice-leg watch addendum — 2026-08-03 — PASS

- Final pre-gate snapshot at `18:59:57` site: action `13702` pending with no attempt, subscription `11991` active with `_completed_payments=1` and no pending renewal order, Mailpit baseline `3scBdxmEgq298byftBwV3Q`.
- Action `13702` completed naturally at `13:05:07Z` (`19:05:07` site), 41 seconds after its `13:04:26Z` schedule. Logs read `action started via WP Cron` / `action complete via WP Cron`; no drain or forced run was issued.
- It created exactly one linked renewal order, `12343`: `wc-pending`, USD `10.00`, customer `357`, Stripe, empty `created_via`, cycle `2`, scheduled date `2026-08-03 13:23:13Z`, and exact `_subscription_id` / `_subscription_renewal` linkage to `11991`.
- The single line is product `11927`, quantity `1`, subtotal/total `10`, tax `0`. Subscription `11991` now carries `_pending_renewal_order_id=12343`; `_completed_payments=1` and its due date remain unchanged until the charge leg.
- Mailpit stayed at `3scBdxmEgq298byftBwV3Q`, proving no invoice or other mail was emitted. Charge action `13703` remains pending for `2026-08-04 01:04:26` site and stays assigned to the D2 watch.
- Evidence: `/home/server-manager/slt-evidence/SLT-CHK-02-D01-invoice-facts.txt`.

## D2 natural charge watch addendum — 2026-08-04 — PASS

- `SUB_CORE2_REN1_PRE=28jnpTDTOBQs0wXw7mWbNz` was captured at `01:00:56` site while action `13703` was pending/unattempted, renewal order `12343` was `wc-pending`, and subscription `11991` was active with one completed payment.
- Action `13703` completed naturally at `19:05:18Z` (`01:05:18` site), 52 seconds after its `19:04:26Z` schedule. Its logs explicitly read `action started via WP Cron` and `action complete via WP Cron`; no drain or forced action was issued.
- Exact cycle-2 order `12343` is now `wc-completed`, USD `10.00`, customer `357`, Stripe transaction `ch_3U0RAdJG5OzSNVs20lBvl0ex`, with `_created_via` absent, exact `_subscription_id=_subscription_renewal=11991`, and scheduled date `2026-08-03 13:23:13Z`. Its single line is product `11927`, quantity `1`, subtotal/total `10`, tax `0`.
- Subscription `11991` remains active, advanced `_completed_payments` from `1` to `2`, cleared its pending renewal order, set `_last_payment_date=2026-08-03 19:05:13Z`, and advanced `_next_payment_date` exactly +24 hours to `2026-08-04 13:23:13Z`. Next actions are invoice `14208` at `13:04:26Z` and charge `14209` at `19:04:26Z`.
- The exact post-baseline mail set is admin new order `26mX3SXgKs5wioBCZQsFUD` and customer payment-success `6fzJg6YALlBNfbNPe6f79F`; no renewal invoice, reminder, failure, signup, or customer WooCommerce processing/completed mail appeared.
- Browser evidence is `/home/server-manager/slt-evidence/SLT-CHK-02-05-renewal-order-D2.png` and `SLT-CHK-02-06-actions-D2.png`; both were visually reviewed and expose no card number. Full facts: `/home/server-manager/slt-evidence/SLT-CHK-02-D02-charge-facts.txt`.

## Shipping-snapshot fix verification — 2026-08-14 — PASS

- The original finding was reproduced from subscription `11991`, order `11990`,
  customer `357`, and the live admin detail route before changing code. The
  classic virtual order has no shipping fields although the customer profile is
  complete; the admin view reported `No shipping address on file`.
- Shared core checkout creation now falls back to the authenticated customer's
  saved WooCommerce shipping profile only when the order itself has no street
  address. Order shipping remains authoritative and billing is never used as a
  substitute.
- A real `/slt-classic-checkout/` BACS checkout created exact order `26774` and
  subscription `26775`. The order remained a shipping-empty classic virtual
  order; the subscription gained exactly one snapshot matching the saved
  customer profile, and its admin detail rendered that address with no browser
  errors.
- Exact-linkage cleanup removed `26774`/`26775`; no matching scheduler actions
  remain and aggregate subscription/order counts returned to baseline. Full
  reasoning, proof, mail IDs, and screenshots are in
  `issues/done-low-SLT-CHK-02-classic-checkout-omits-shipping-address-meta.md`.
