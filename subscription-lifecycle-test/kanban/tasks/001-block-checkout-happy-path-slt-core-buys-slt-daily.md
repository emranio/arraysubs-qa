---
id: 1
title: 'Block checkout happy path: slt-core buys SLT Daily Core on Stripe 4242 (control record)'
status: done
priority: critical
created: 2026-08-02T03:43:02.949662313+02:00
updated: 2026-08-05T21:37:49.274081959+02:00
started: 2026-08-02T15:14:20.35381713+02:00
completed: 2026-08-02T15:14:20.35381713+02:00
tags:
    - checkout
    - day-00
due: "2026-08-02"
estimate: 1h
depends_on:
    - 10
    - 11
    - 12
    - 5
class: standard
---

> **SLT-CHK-01** · group `checkout` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
The reference purchase of the window: `slt-core` buys one `SLT Daily Core` ($10.00) on the block checkout (page 8, which stays block all window) with Stripe 4242. Later checkout tasks diff against it: subscription shape, order linkage, schedule meta, scheduler legs, token meta, mail set.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing
- Plugins: both

## Preconditions
- SLT-SETUP-01/02/03 + SLT-PROD-01 done; `slt-core` has a billing address.
- Run **after 12:00 site time** so the anniversary misses D3's SYN-04 bracket.
- `slt-core` owns no SLT Daily Core sub and must never rebuy it. At the frozen `one_per_customer=false` baseline, `auto_migrate_on_checkout=true` is inert; a rebuy would create a duplicate and make the control spine ambiguous (see SLT-CHK-08).

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core (`slt-daily-core`), day/1, $10.00 |
| Account | slt-core / `SltQa!2026#Pass` |
| Card | 4242 4242 4242 4242, `12/34`, CVC 123 |
| Session | `cust-SLT-CHK-01` |

## Steps
1. Record `SUBCOUNT_BEFORE` (`arraysubs_data` count) and `PRE=$(mailpit-agent latest-id)`.
2. `agent-browser --session cust-SLT-CHK-01 open ".../my-account/"` → `snapshot -i` → log in as `slt-core`.
3. Open `/cart/`; assert EMPTY; shot `-01-cart.png`. Open `/product/slt-daily-core/` → **Add to cart**; reopen `/cart/`; record subtotal + recurring line; shot `-02-cart2.png`.
4. Open `/checkout/`; confirm block checkout, prefilled billing, **Total $10.00**, no tax row; shot `-03-gateways.png` (list every gateway offered).
5. Pick **Credit Card (Stripe)**, fill the UPE iframe, record the default state of **Save payment information** unchanged.
6. **Place Order**; wait for `/checkout/order-received/<ORDER>/`; shot `-04-received.png`; record `ORDER`; capture `console`/`network` (JS errors, non-2xx on the Store API).
7. `mailpit-agent wait-new "$PRE" 180 "is active"`; inspect the complete delta after `$PRE` and map every task-owned message by exact order/user/recipient/subject, classifying unrelated/background mail separately.
8. Resolve `SUB` from `$ORDER`'s exact `_subscription_ids`, require exactly one ID, and cross-check `_parent_order_id=$ORDER`, `_customer_id`, `_product_id`, plus the recorded subscription-count delta; never select by recency. Dump `wp post meta list "$SUB" --allow-root` to `/home/server-manager/slt-evidence/SLT-CHK-01-sub-meta.txt` (baseline for CHK-02); read the `wp_wc_orders` row and `_subscription_ids`/tax items for `$ORDER`.
9. Compute spread offset `k` for `$SUB` (REF-01 §0); **Tools → Scheduled Actions** → Pending, search `$SUB`; shot `-05-pending.png`; record both GMT timestamps.
10. My Account → Subscriptions detail; shot `-06-myaccount.png`; append IDs to the registry; close this session.
11. D1 watch handoff (owned by `SLT-REN-01`, not a remaining criterion on this checkout card), force nothing: renewal #1 fires at `_next_payment_date + k`, bills $10.00, advances the date 24 h.

## Expected results
1. Exactly one new `arraysubs_data` post, status `arraysubs-active`.
2. Order `completed` (the paid cart contains only a virtual product), `total_amount=10.00`, `currency=USD`, `payment_method=stripe`, zero tax items; Store API returned 200.
3. `_parent_order_id=$ORDER`, `_order_ids=["$ORDER"]`, order `_subscription_ids=[$SUB]`.
4. `_billing_period=day`, `_billing_interval=1`, `_subscription_length=0`, `_trial_length=0`, `_next_payment_date = _start_date + 24h` exactly, `_completed_payments=1`, `_last_payment_date` set.
5. `_subscription_price`=`_recurring_amount`=`10`, `_currency=USD`, `_signup_fee` empty/`0`, `_quantity=1`, `_customer_email` = slt-core's.
6. `_payment_method=stripe`, `_gateway_customer_id` `^cus_`, `_gateway_payment_method_id` `^pm_`, `_auto_renew` absent/`on`.
7. `arraysubs_process_renewal[$SUB]` at `_next_payment_date + k` (±60 s), `arraysubs_generate_renewal_invoice[$SUB]` at `+k − 6h`; stored date unshifted.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC New order | paid checkout | admin | `New order #$ORDER` | Complete owner-filtered delta after `$PRE`; save/show the exact matching id |
| 2 | WC Completed order | paid virtual-only order → completed | slt-core@example.test | `is on its way` | Complete owner-filtered delta after `$PRE`; save/show the exact matching id |
| 3 | `new_subscription` | → `arraysubs-active` | slt-core@example.test | `subscription #$SUB is active` | `mailpit-agent wait-new "$PRE" 180 "is active"` |
| 4 | `admin_new_subscription` | same | admin | `New subscription #$SUB` | Complete owner-filtered delta after `$PRE`; save/show the exact matching id |
| 5 | NONE EXPECTED | signup | — | — | invoice mail suppressed for automatic+auto-renew subs (REF-01 §4), `payment_successful` is renewal-only, reminder lead 3d > 1d cycle: no `Invoice for subscription`/`Payment received`/`renews soon`/`free trial`/`account on` |

## Evidence to capture
- Shots 01–06 in `/home/server-manager/slt-evidence/`; `SUB`, `ORDER`, `k`, both AS timestamps; `SLT-CHK-01-sub-meta.txt`; `PRE` + Mailpit IDs.

## Pass criteria
- [x] One new subscription `arraysubs-active`; order `completed`, $10.00 USD, stripe, no tax line
- [x] Two-way order/subscription linkage correct
- [x] `_next_payment_date = _start_date + 24h`; schedule/amount meta exact; `_completed_payments=1`
- [x] `cus_`/`pm_` meta stored; AS legs at `+k` and `+k − 6h`
- [x] Exactly mails 1–4; row 5 negatives hold; no console errors

## Isolation / teardown
- Hands SLT-CHK-02 the meta baseline and the watch the day/1 renewal spine — **do not cancel before SLT-SETUP-99A on D10**. `slt-core` is bound to SLT Daily Core. Nothing global changed; cart left empty.

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

## Execution verdict — 2026-08-02 — PASS

- Exactly one new subscription was created: `SUB_CORE=11959`, status `arraysubs-active`, product `11927`, customer `347`.
- Parent order `11949` is `completed`, USD `10.00`, Stripe, `created_via=store-api`, with zero tax items. The plan formerly expected `processing`; corrected because WooCommerce auto-completes a paid virtual-only order.
- Linkage is exact: subscription `_parent_order_id=11949`, `_order_ids=[11949]`; legacy order postmeta `_subscription_ids` serializes `[11959]` under HPOS.
- `_start_date=2026-08-02 12:39:05Z`; `_next_payment_date=2026-08-03 12:39:05Z`; `_completed_payments=1`; amount, interval, length, customer and Stripe token fields match the contract.
- `k=10727`: pending invoice action `13693` is at `2026-08-03 09:37:52Z`; pending charge action `13694` is at `2026-08-03 15:37:52Z`.
- Exactly four messages followed the pre-checkout baseline: customer completed order `7J05Utc5WZypPfE7CFrri8`, admin new order `7mcZSlyf4vELxvYVsBREqv`, customer active subscription `7DNNy550OnzYosYbloypf7`, and admin new subscription `55FatLnmPyOxJGksTgxDWl`. No renewal, trial, reminder or failure mail appeared.
- Browser proof shows the order-received page and active portal record with next payment `3 August, 2026 6:39 PM (UTC+6)`, Visa `4242`, expiry `12/2034`, and an empty cart. Checkout Store API returned HTTP `200`; no browser errors were reported.
- Product observations are isolated in `issues/done-critical-plugin-SLT-OBS-01-duplicate-gateway-postmeta-rows.md` and `issues/done-low-SLT-CHK-01-wc-blocks-data-dependency-warning.md`; neither blocks this functional pass.
- Evidence: `/home/server-manager/slt-evidence/SLT-CHK-01-01-cart.png` through `-06-myaccount.png`, plus `/home/server-manager/slt-evidence/SLT-CHK-01-sub-meta.txt`. Registry page `11847` contains the control spine and D1 handoff.

### Issue re-investigation — 2026-08-14

The dependency warning was isolated to a false positive in WooCommerce `10.9.4`'s debug-only dependency detector. ArraySubs' cart integration already receives `wc-blocks-data-store` transitively from its declared `wc-blocks-checkout` dependency, and ArraySubsPro does not read `wcBlocksData`. Blocking all ArraySubs, ArraySubsPro, and Stripe scripts still produced the warning; blocking WooCommerce core's `cart-frontend.js` removed it. The detector's default 10-frame stack was exhausted by its own nested proxy getters, so it lost the registered WooCommerce caller and mislabeled it as inline. No product or vendor code change was warranted. A live cart control passed and the customer cart was returned to empty; full evidence and safety reasoning are in `issues/done-low-SLT-CHK-01-wc-blocks-data-dependency-warning.md`.
