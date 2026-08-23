---
id: 2
title: 'Classic checkout parity: same SLT2 Daily Core purchase, meta diffed field-by-field against CHK-01'
status: blocked
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T22:35:27.705649691+02:00
tags:
    - cycle-2
    - granular
    - checkout
    - day-00
due: "2026-08-23"
estimate: 1h15m
depends_on:
    - 1
    - 10
    - 131
class: standard
---

## Current execution blocker — 2026-08-23 site date

Blocked by critical shared issue `qa/issues` #1 / preflight task `131`, and transitively by source task `1`. The classic checkout parity order must not be created until Stripe's missing secondary-webhook events are restored and the block-checkout control passes. No checkout/order/subscription/charge was attempted.

> **SLT-CHK-02** · group `checkout` · scheduled **D00** (2026-08-23)

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
- `/slt2-classic-checkout` (SLT-SETUP-01) works; page 8 untouched.
- **Deliberate deviation:** buyer is `slt2-core2`, not `slt2-core`. At the frozen `one_per_customer=false` baseline, a second `slt2-core` checkout would create a duplicate rather than migrate; that would make the parity/control records ambiguous. This task therefore **creates** `slt2-core2` and leaves the CHK-01 owner untouched.
- Run after 12:00 site, same day as CHK-01, before any renewal fires (both records must read `_completed_payments=1`).

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Daily Core, day/1, $10.00 |
| Account | slt2-core2 / slt2-core2@example.test / `SltQa!2026#Pass` (Customer); billing 1 SLT2 Way, Dhaka BD 1207 |
| Card | 4242 4242 4242 4242, `12/34`, CVC 123 |
| Session | `cust-SLT-CHK-02` |

## Steps
1. `PRE=$(mailpit-agent latest-id)`; record `SUBCOUNT_BEFORE`.
2. As admin at `user-new.php` create `slt2-core2`, **Send User Notification unticked**, role Customer; set its Customer billing address. Record `UID`.
3. `agent-browser --session cust-SLT-CHK-02 open ".../my-account/"` → log in as `slt2-core2`; open `/cart/`, assert EMPTY; open `/product/slt2-daily-core/` → **Add to cart**.
4. Open `/slt2-classic-checkout` → `snapshot -i`; confirm **classic** markup (`#order_review`, `.woocommerce-billing-fields`), **Total $10.00**, no tax row, billing prefilled; shots `-01-classic.png` and `-02-gateways.png` (compare the gateway list with CHK-01's).
5. Select **Credit Card (Stripe)**, fill the card, **Place order**; wait for order-received; record `ORDER`; shot `-03-received.png`; capture `console`/`network`.
6. `mailpit-agent wait-new "$PRE" 180 "is active"`; inspect and classify the complete owner-filtered delta after `$PRE`.
7. Resolve the exactly one subscription ID from `$ORDER`'s `_subscription_ids`, cross-check `_parent_order_id`, customer, product, and the recorded count delta, then publish it under canonical alias **`SUB_CORE2`**; never select by recency. Dump `wp post meta list "$SUB_CORE2" --allow-root` to `/home/server-manager/slt-evidence/SLT-CHK-02-sub-meta.txt` and save the raw diagnostic diff against CHK-01 as `/home/server-manager/slt-evidence/SLT-CHK-02-diff.txt`. Separately compare the invariant keys listed in expected result 3 as a key-sorted map, using the effective value for duplicated metadata rows.
8. Compute `k` for `$SUB` (REF-01 §0); check **Tools → Scheduled Actions** → Pending for `$SUB`; shot `-04-pending.png`.
9. Append `UID`/`SUB_CORE2`/`ORDER` to the registry; close this session.
10. D1/D2 daily-watch handoff (not a remaining criterion on this checkout card), force nothing: publish this subscription's live invoice/charge action IDs and timestamps derived from its own `_next_payment_date` and `k`. Do not reuse CHK-01's clock or any authored ID.

## Expected results
1. `slt2-core2` exists with role `customer`; the checkout created exactly one new `arraysubs_data` post, status `arraysubs-active`.
2. Order `completed`, `total_amount=10.00`, `currency=USD`, `payment_method=stripe`, zero tax items; no PHP notice or console error; page 8 still renders the block checkout. WooCommerce auto-completes a paid virtual-only order.
3. Equal effective values in both subscriptions: `_billing_period`, `_billing_interval`, `_subscription_length`, `_trial_length`, `_trial_period`, `_subscription_price`, `_recurring_amount`, `_currency`, `_signup_fee`, `_product_id`, `_variation_id`, `_quantity`, `_payment_method`, `_payment_method_title`, `_payment_gateway`, `_gateway_status`, `_payment_method_type`, `_payment_method_brand`, `_payment_method_last4`, `_payment_method_expiry_month`, `_payment_method_expiry_year`, `_completed_payments`, `_enable_different_renewal_price`, `_different_renewal_price`, `_different_renewal_price_after`.
4. Expected dynamic differences are validated by shape rather than byte equality: customer/order IDs and emails, start/next/last-payment timestamps, Stripe customer/payment-method/transaction IDs, `_billing_address`, cached-product `cached_at`, and both renewal action IDs. The raw diff may also expose duplicate-row-count differences; record those in the shared issue card without failing otherwise-equal effective values.
5. `_next_payment_date = _start_date + 24h`; `_parent_order_id=$ORDER`; order `_subscription_ids=[$SUB]`; AS legs at `+k` and `+k−6h` as in CHK-01.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC New order | paid checkout | admin | `New order #$ORDER` | Complete owner-filtered delta after `$PRE`; save/show the exact matching id |
| 2 | WC Completed order | paid virtual-only order → completed | slt2-core2@example.test | `is on its way` | Complete owner-filtered delta after `$PRE`; save/show the exact matching id |
| 3 | `new_subscription` | → `arraysubs-active` | slt2-core2@example.test | `subscription #$SUB is active` | `mailpit-agent wait-new "$PRE" 180 "is active"` |
| 4 | `admin_new_subscription` | same | admin | `New subscription #$SUB` | Complete owner-filtered delta after `$PRE`; save/show the exact matching id |
| 5 | WP New User Registration | admin user creation | admin | `New User Registration` | Unticking notification suppresses only the customer account email; the admin registration notice is still expected |
| 6 | NONE EXPECTED | user creation + signup | — | — | no customer `Your account on`; as CHK-01, no `Invoice for subscription`/`Payment received`/`renews soon` |

## Evidence to capture
- Shots 01–04; `UID`, `SUB`, `ORDER`, `k`; `SLT-CHK-02-sub-meta.txt`, `-diff.txt`; `PRE` + Mailpit IDs.

## Pass criteria
- [ ] `slt2-core2` created; exactly one admin registration notice and no customer account mail; classic shortcode checkout rendered
- [ ] One new `arraysubs-active` sub; order `completed`, $10.00, no tax line
- [ ] Normalized invariant comparison is empty; raw dynamic diff is retained as diagnostic evidence
- [ ] `_next_payment_date = _start_date + 24h`; AS legs at `+k` / `+k−6h`
- [ ] Mails 1–5 present; row-6 negatives hold

## Isolation / teardown
- Creates `slt2-core2`: add it to the account matrix and SETUP-99B's deletion list; it is bound to SLT2 Daily Core. The sub stays live for the watch, cancelled by SETUP-99A on D11. Nothing global changed; cart left empty.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
