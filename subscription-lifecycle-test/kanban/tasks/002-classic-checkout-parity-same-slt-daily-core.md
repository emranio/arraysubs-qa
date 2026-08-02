---
id: 2
title: 'Classic checkout parity: same SLT Daily Core purchase, meta diffed field-by-field against CHK-01'
status: todo
priority: critical
created: 2026-08-02T03:43:03.051717636+02:00
updated: 2026-08-02T03:43:12.99945242+02:00
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
Repeat SLT-CHK-01 on the **classic** `[woocommerce_checkout]` page and prove the surface changes nothing: the subscription record must match CHK-01's apart from IDs, timestamps and identity. Output is an explicit `diff` of the two meta dumps.

## Scope
- Gateway: Stripe test
- Checkout: classic
- Account: new registered (created here)
- Plugins: both

## Preconditions
- SLT-CHK-01 done; `slt-evidence/SLT-CHK-01-sub-meta.txt` exists.
- `/slt-classic-checkout` (SLT-SETUP-01) works; page 8 untouched.
- **Deliberate deviation:** buyer is `slt-core2`, not `slt-core` — with `auto_migrate_on_checkout=true` a second `slt-core` purchase of this product would MIGRATE the CHK-01 subscription rather than create one (C08), killing the day/1 renewal spine. This task therefore **creates** `slt-core2`.
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
3. `agent-browser --session cust-SLT-CHK-02 open ".../my-account/"` → log in as `slt-core2`; open `/cart/`, assert EMPTY; open `/slt-daily-core` → **Add to cart**.
4. Open `/slt-classic-checkout` → `snapshot -i`; confirm **classic** markup (`#order_review`, `.woocommerce-billing-fields`), **Total $10.00**, no tax row, billing prefilled; shots `-01-classic.png` and `-02-gateways.png` (compare the gateway list with CHK-01's).
5. Select **Credit Card (Stripe)**, fill the card, **Place order**; wait for order-received; record `ORDER`; shot `-03-received.png`; capture `console`/`network`.
6. `mailpit-agent wait-new $PRE 180 "is active"`; `mailpit-agent list 20`.
7. Identify `SUB` (exactly one new `arraysubs_data` post); dump `wp post meta list $SUB` to `slt-evidence/SLT-CHK-02-sub-meta.txt`, `diff` it against CHK-01's dump, save `SLT-CHK-02-diff.txt`.
8. Compute `k` for `$SUB` (REF-01 §0); check **Tools → Scheduled Actions** → Pending for `$SUB`; shot `-04-pending.png`.
9. Append `UID`/`SUB`/`ORDER` to the registry; close this session.
10. D1 watch follow-up, force nothing: renews on CHK-01's cadence.

## Expected results
1. `slt-core2` exists with role `customer`; the checkout created exactly one new `arraysubs_data` post, status `arraysubs-active`.
2. Order `processing`, `total_amount=10.00`, `currency=USD`, `payment_method=stripe`, zero tax items; no PHP notice or console error; page 8 still renders the block checkout.
3. The diff differs **only** on `_customer_id`, `_customer_email`, `_parent_order_id`, `_order_ids`, `_start_date`, `_next_payment_date`, `_last_payment_date`, `_gateway_customer_id`, `_gateway_payment_method_id`; any other differing key is a finding.
4. Byte-identical in both dumps: `_billing_period`, `_billing_interval`, `_subscription_length`, `_trial_length`, `_subscription_price`, `_recurring_amount`, `_currency`, `_signup_fee`, `_product_id`, `_quantity`, `_payment_method`, `_completed_payments`.
5. `_next_payment_date = _start_date + 24h`; `_parent_order_id=$ORDER`; order `_subscription_ids=[$SUB]`; AS legs at `+k` and `+k−6h` as in CHK-01.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC New order | → processing | admin | `New order #$ORDER` | `list 20` |
| 2 | WC Processing order | → processing | slt-core2@example.test | `order has been received` | `list 20` |
| 3 | `new_subscription` | → `arraysubs-active` | slt-core2@example.test | `subscription #$SUB is active` | `wait-new $PRE 180 "is active"` |
| 4 | `admin_new_subscription` | same | admin | `New subscription #$SUB` | `list 20` |
| 5 | NONE EXPECTED | user creation + signup | — | — | notification unticked → no `Your account on`/`New user registration`; as CHK-01, no `Invoice for subscription`/`Payment received`/`renews soon` |

## Evidence to capture
- Shots 01–04; `UID`, `SUB`, `ORDER`, `k`; `SLT-CHK-02-sub-meta.txt`, `-diff.txt`; `PRE` + Mailpit IDs.

## Pass criteria
- [ ] `slt-core2` created, no notification mail; classic shortcode checkout rendered
- [ ] One new `arraysubs-active` sub; order `processing`, $10.00, no tax line
- [ ] Meta diff contains only the nine allowed keys
- [ ] `_next_payment_date = _start_date + 24h`; AS legs at `+k` / `+k−6h`
- [ ] Mails 1–4 present; row 5 negatives hold

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
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence before forcing.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run `wp action-scheduler run` without `--hooks=`; prefer a single action by ID.
- Evidence goes in `qa/subscription-lifecycle-test/evidence/<TASK-KEY>/`.
