---
id: 15
title: 'Guest to new account: block checkout mints slt-guest-d0 mid-flow and owns order + subscription'
status: done
priority: critical
created: 2026-08-02T03:43:04.362506442+02:00
updated: 2026-08-05T21:37:49.301996129+02:00
started: 2026-08-03T09:47:07.068748701+02:00
completed: 2026-08-03T09:47:07.068748701+02:00
tags:
    - checkout
    - day-01
due: "2026-08-03"
estimate: 1h
depends_on:
    - 1
    - 5
    - 12
class: standard
---

> **SLT-CHK-03** · group `checkout` · scheduled **D01** (2026-08-03)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove the guest → new account path. WooCommerce guest checkout is enabled site-wide, but ArraySubs forces registration for subscription carts when `checkout.auto_create_account=true`; therefore this cart must offer no anonymous continuation and the block checkout must mint `slt-guest-d0@example.test`, with the new user owning both order and subscription.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: guest → new
- Plugins: both

## Preconditions
- SLT-PROD-01 and SLT-CHK-01 done (CHK-01 is the baseline).
- **No user with email `slt-guest-d0@example.test` and no user login `slt.guest` may exist** — SETUP-03 reserved the address and the current WooCommerce-generated login so this task creates them. Probe both values explicitly; abort if either exists.
- `checkout.auto_create_account=true` (SETUP-02 frozen table). Unique session so no other cart leaks in. Run after 12:00 site.
- `wp option get woocommerce_enable_guest_checkout --allow-root` must print `yes`; the subscription-cart registration filter, not a disabled WooCommerce option, is the mechanism under test.
- `woocommerce_registration_generate_username=yes` and `woocommerce_registration_generate_password=yes`; with billing name `SLT Guest`, the current WooCommerce username contract is `slt.guest` and no checkout password field is expected.

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core, day/1, $10.00 |
| Account | none → `slt-guest-d0@example.test`, minted at checkout |
| Billing | SLT Guest, 1 SLT Way, Dhaka BD 1207 |
| Card | 4242 4242 4242 4242, `12/34`, CVC 123 |
| Session | `guest-SLT-CHK-03` (unique) |

## Steps
1. `PRE=$(mailpit-agent latest-id)`; record `SUBCOUNT_BEFORE`; confirm the reserved email and generated login are both unused. Require `woocommerce_enable_guest_checkout=yes`, `woocommerce_registration_generate_username=yes`, and `woocommerce_registration_generate_password=yes`.
2. `agent-browser --session guest-SLT-CHK-03 open "https://mirror-help.arrayhash.com/product/slt-daily-core/"` → `snapshot -i`; confirm logged-out. Open `/cart/`, assert EMPTY; back to the product → **Add to cart**; reopen `/cart/`; shot `-01-cart.png`.
3. Open `/checkout/` → `snapshot -i`; record what the account area offers; shot `-02-account.png`.
4. Enter `slt-guest-d0@example.test`, fill billing, set a password if asked (record whether it asks).
5. Select **Credit Card (Stripe)**, fill the UPE iframe, **Place Order**; wait for order-received; record `ORDER`; shot `-03-received.png`; capture `console`/`network`.
6. `mailpit-agent wait-new "$PRE" 180 "is active"`; inspect the complete delta after `$PRE` and map every task-owned message by exact order/user/recipient/subject, classifying unrelated/background mail separately.
7. `wp user get slt-guest-d0@example.test --fields=ID,user_login,user_email,roles --format=json --allow-root` → record `UID`, generated login, and role.
8. Resolve `SUB` from `get_post_meta($ORDER, '_subscription_ids', true)`, require exactly one ID, and cross-check `_parent_order_id=$ORDER`, `_customer_id=$UID`, plus total subscription count `SUBCOUNT_BEFORE+1`; never select by recency. Under HPOS this linkage remains in `wp_postmeta`, so do not use `WC_Order::get_meta('_subscription_ids')`, which does not expose it. Dump the subscription meta to `/home/server-manager/slt-evidence/SLT-CHK-03-sub-meta.txt`; read the `wp_wc_orders` row for `$ORDER` (note `customer_id`).
9. Compute `k` (REF-01 §0). Load the documented admin auth state into isolated session `admin-SLT-CHK-03`; open **Tools → Scheduled Actions** → Pending for `$SUB`; shot `-04-pending.png`.
10. In the same session open `/my-account/subscriptions/` — already logged in, subscription listed; shot `-05-myaccount.png`. Then open `/cart/`, require the post-checkout cart and the new user's persistent-cart meta to be EMPTY, and capture `SLT-CHK-03-06-cart-empty-after.png`.
11. Append `UID`/`SUB`/`ORDER` to the registry; close only `guest-SLT-CHK-03` and `admin-SLT-CHK-03`. Publish the next-morning renewal expectation to the daily watch, which owns that observation and forces nothing. This purchase card closes after its checkout criteria, empty-cart proof, and registry handoff are complete.

## Expected results
1. No anonymous path: no "continue as guest" option for this cart; record the wording and whether a password field appeared.
2. Exactly one new user: WooCommerce-generated login `slt.guest` (derived from the supplied billing name under the frozen username-generation setting), `user_email=slt-guest-d0@example.test`, role `customer`; no password field appears because password generation is enabled.
3. `wp_wc_orders.customer_id = $UID` (not `0`), `completed`, `total_amount=10.00`, `currency=USD`, `payment_method=stripe`, zero tax items. This is the same paid virtual-only product as CHK-01/02, so WooCommerce auto-completes it.
4. Exactly one new `arraysubs_data` post, `arraysubs-active`, `_customer_id=$UID`, `_customer_email` = the guest address.
5. Structure matches CHK-01: `_billing_period=day`, `_billing_interval=1`, `_subscription_length=0`, `_trial_length=0`, `_subscription_price`=`_recurring_amount`=`10`, `_completed_payments=1`, `_parent_order_id=$ORDER`, order `_subscription_ids=[$SUB]`.
6. `_next_payment_date = _start_date + 24h`; `_gateway_customer_id` `^cus_`; `_gateway_payment_method_id` `^pm_`; AS legs at `+k`, `+k−6h`.
7. The session is authenticated after checkout; My Account lists the subscription.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC Customer new account | account minted at checkout | slt-guest-d0 | `account has been created` | Complete owner-filtered delta after `$PRE`; save/show the exact matching id |
| 2 | WC New order | paid checkout | admin | `New order #$ORDER` | Complete owner-filtered delta after `$PRE`; save/show the exact matching id |
| 3 | WC Completed order | virtual-only order → completed | slt-guest-d0 | `is on its way` | Complete owner-filtered delta after `$PRE`; save/show the exact matching id |
| 4 | `new_subscription` | → `arraysubs-active` | slt-guest-d0 | `subscription #$SUB is active` | `mailpit-agent wait-new "$PRE" 180 "is active"` |
| 5 | `admin_new_subscription` | same | admin | `New subscription #$SUB` | Complete owner-filtered delta after `$PRE`; save/show the exact matching id |
| 6 | NONE EXPECTED | signup | — | — | no `Invoice for subscription`/`Payment received`/`renews soon`/`free trial` |

## Evidence to capture
- Shots 01–06; `UID`, generated login, `SUB`, `ORDER`, `k`; `SLT-CHK-03-sub-meta.txt`; order row with `customer_id`; exact post-meta linkage; all three WooCommerce option probes; `PRE` + Mailpit IDs.

## Pass criteria
- [ ] WooCommerce guest checkout option is `yes`, but no anonymous path is offered for this subscription cart; account auto-created as login `slt.guest` at the reserved address, role `customer`, with no password field under generated-password mode
- [ ] Order owned by `$UID`, `completed`, $10.00, no tax line
- [ ] One `arraysubs-active` sub owned by `$UID`, structurally = CHK-01
- [ ] `_next_payment_date = _start_date + 24h`; AS legs at `+k` / `+k−6h`
- [ ] Mails 1–5 present incl. the account mail; row 6 negatives hold
- [ ] Cart proved empty before checkout and again after checkout
- [ ] New user's persistent-cart meta is empty after checkout; exact order linkage identifies the sole new subscription

## Isolation / teardown
- Creates the reserved `slt-guest-d0@example.test` account with generated login `slt.guest` — record its ID in the registry and add it to SETUP-99B's deletion list. `slt-guest-d5@example.test` stays reserved. The sub stays live for the watch, cancelled by SETUP-99A on D10. Close both task-keyed browser sessions.

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

### D01 execution — 2026-08-03

**PASS after plan corrections C57–C59.** Preflight at site time `2026-08-03 13:29:26` proved `woocommerce_enable_guest_checkout=yes`, both WooCommerce username/password generation options `yes`, no reserved email/login, `SUBCOUNT_BEFORE=362`, and product `11927`. The logged-out block checkout exposed `Log in` plus a required email field, no continue-as-guest control, and no password field.

Stripe Card completed order `12205` for USD 10.00 and created user `359` (`slt.guest`, `slt-guest-d0@example.test`, customer) plus sole linked subscription `12221`. The HPOS order row is `wc-completed`, customer `359`, Stripe, zero tax; exact `get_post_meta(12205, '_subscription_ids', true)` is `[12221]`. Subscription `12221` is `arraysubs-active`, parent order `12205`, product `11927`, completed payments `1`, start `2026-08-03 07:36:51Z`, next payment `2026-08-04 07:36:51Z`, with Stripe `cus_`/`pm_` identifiers. `k=18141`; pending invoice/charge actions `14010`/`14011` are at `2026-08-04 06:39:12Z` and `12:39:12Z`.

The complete delta after `PRE=54ptwgV061UN4AvINXNxtx` contained exactly the five owned messages: account `7WxfbdQcRCMbRmoBryL0oh`, admin order `4QC9m0yLbZ27n3Vzu6qW5f`, completed order `0AUX9fWMqtc0SBEooTCn45`, customer subscription `74RSFpm1BDzl336q7fkO0K`, and admin subscription `1c1GDfyKNHlSEsmQz6pN5J`; no forbidden lifecycle mail appeared. Browser errors were empty; the known dependency warning is already covered by `issues/SLT-CHK-01-wc-blocks-data-dependency-warning.md`. The session was authenticated after checkout, My Account listed only `#12221`, and both the browser cart and serialized persistent cart were empty. Consolidated evidence: `/home/server-manager/slt-evidence/SLT-CHK-03-facts.txt` and all six task screenshots.
