---
id: 15
title: 'Guest to new account: block checkout mints slt2-guest-d0 mid-flow and owns order + subscription'
status: todo
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - checkout
    - day-01
due: "2026-08-24"
estimate: 1h
depends_on:
    - 1
    - 5
    - 12
class: standard
---

> **SLT-CHK-03** · group `checkout` · scheduled **D01** (2026-08-24)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Prove the guest → new account path. WooCommerce guest checkout is enabled site-wide, but ArraySubs forces registration for subscription carts when `checkout.auto_create_account=true`; therefore this cart must offer no anonymous continuation and the block checkout must mint `slt2-guest-d0@example.test`, with the new user owning both order and subscription.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: guest → new
- Plugins: both

## Preconditions
- SLT-PROD-01 and SLT-CHK-01 done (CHK-01 is the baseline).
- **No user with email `slt2-guest-d0@example.test` and no user login `slt.guest` may exist** — SETUP-03 reserved the address and the current WooCommerce-generated login so this task creates them. Probe both values explicitly; abort if either exists.
- `checkout.auto_create_account=true` (SETUP-02 frozen table). Unique session so no other cart leaks in. Run after 12:00 site.
- `wp option get woocommerce_enable_guest_checkout --allow-root` must print `yes`; the subscription-cart registration filter, not a disabled WooCommerce option, is the mechanism under test.
- `woocommerce_registration_generate_username=yes` and `woocommerce_registration_generate_password=yes`; with billing name `SLT2 Guest`, the current WooCommerce username contract is `slt.guest` and no checkout password field is expected.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Daily Core, day/1, $10.00 |
| Account | none → `slt2-guest-d0@example.test`, minted at checkout |
| Billing | SLT2 Guest, 1 SLT2 Way, Dhaka BD 1207 |
| Card | 4242 4242 4242 4242, `12/34`, CVC 123 |
| Session | `guest-SLT-CHK-03` (unique) |

## Steps
1. `PRE=$(mailpit-agent latest-id)`; record `SUBCOUNT_BEFORE`; confirm the reserved email and generated login are both unused. Require `woocommerce_enable_guest_checkout=yes`, `woocommerce_registration_generate_username=yes`, and `woocommerce_registration_generate_password=yes`.
2. `agent-browser --session guest-SLT-CHK-03 open "https://mirror-help.arrayhash.com/product/slt2-daily-core/"` → `snapshot -i`; confirm logged-out. Open `/cart/`, assert EMPTY; back to the product → **Add to cart**; reopen `/cart/`; shot `-01-cart.png`.
3. Open `/checkout/` → `snapshot -i`; record what the account area offers; shot `-02-account.png`.
4. Enter `slt2-guest-d0@example.test`, fill billing, set a password if asked (record whether it asks).
5. Select **Credit Card (Stripe)**, fill the UPE iframe, **Place Order**; wait for order-received; record `ORDER`; shot `-03-received.png`; capture `console`/`network`.
6. `mailpit-agent wait-new "$PRE" 180 "is active"`; inspect the complete delta after `$PRE` and map every task-owned message by exact order/user/recipient/subject, classifying unrelated/background mail separately.
7. `wp user get slt2-guest-d0@example.test --fields=ID,user_login,user_email,roles --format=json --allow-root` → record `UID`, generated login, and role.
8. Resolve `SUB` from `get_post_meta($ORDER, '_subscription_ids', true)`, require exactly one ID, and cross-check `_parent_order_id=$ORDER`, `_customer_id=$UID`, plus total subscription count `SUBCOUNT_BEFORE+1`; never select by recency. Under HPOS this linkage remains in `wp_postmeta`, so do not use `WC_Order::get_meta('_subscription_ids')`, which does not expose it. Dump the subscription meta to `/home/server-manager/slt-evidence/SLT-CHK-03-sub-meta.txt`; read the `wp_wc_orders` row for `$ORDER` (note `customer_id`).
9. Compute `k` (REF-01 §0). Load the documented admin auth state into isolated session `admin-SLT-CHK-03`; open **Tools → Scheduled Actions** → Pending for `$SUB`; shot `-04-pending.png`.
10. In the same session open `/my-account/subscriptions/` — already logged in, subscription listed; shot `-05-myaccount.png`. Then open `/cart/`, require the post-checkout cart and the new user's persistent-cart meta to be EMPTY, and capture `SLT-CHK-03-06-cart-empty-after.png`.
11. Append `UID`/`SUB`/`ORDER` to the registry; close only `guest-SLT-CHK-03` and `admin-SLT-CHK-03`. Publish the next-morning renewal expectation to the daily watch, which owns that observation and forces nothing. This purchase card closes after its checkout criteria, empty-cart proof, and registry handoff are complete.

## Expected results
1. No anonymous path: no "continue as guest" option for this cart; record the wording and whether a password field appeared.
2. Exactly one new user: WooCommerce-generated login `slt.guest` (derived from the supplied billing name under the frozen username-generation setting), `user_email=slt2-guest-d0@example.test`, role `customer`; no password field appears because password generation is enabled.
3. `wp_wc_orders.customer_id = $UID` (not `0`), `completed`, `total_amount=10.00`, `currency=USD`, `payment_method=stripe`, zero tax items. This is the same paid virtual-only product as CHK-01/02, so WooCommerce auto-completes it.
4. Exactly one new `arraysubs_data` post, `arraysubs-active`, `_customer_id=$UID`, `_customer_email` = the guest address.
5. Structure matches CHK-01: `_billing_period=day`, `_billing_interval=1`, `_subscription_length=0`, `_trial_length=0`, `_subscription_price`=`_recurring_amount`=`10`, `_completed_payments=1`, `_parent_order_id=$ORDER`, order `_subscription_ids=[$SUB]`.
6. `_next_payment_date = _start_date + 24h`; `_gateway_customer_id` `^cus_`; `_gateway_payment_method_id` `^pm_`; AS legs at `+k`, `+k−6h`.
7. The session is authenticated after checkout; My Account lists the subscription.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC Customer new account | account minted at checkout | slt2-guest-d0 | `account has been created` | Complete owner-filtered delta after `$PRE`; save/show the exact matching id |
| 2 | WC New order | paid checkout | admin | `New order #$ORDER` | Complete owner-filtered delta after `$PRE`; save/show the exact matching id |
| 3 | WC Completed order | virtual-only order → completed | slt2-guest-d0 | `is on its way` | Complete owner-filtered delta after `$PRE`; save/show the exact matching id |
| 4 | `new_subscription` | → `arraysubs-active` | slt2-guest-d0 | `subscription #$SUB is active` | `mailpit-agent wait-new "$PRE" 180 "is active"` |
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
- Creates the reserved `slt2-guest-d0@example.test` account with generated login `slt.guest` — record its ID in the registry and add it to SETUP-99B's deletion list. `slt2-guest-d5@example.test` stays reserved. The sub stays live for the watch, cancelled by SETUP-99A on D11. Close both task-keyed browser sessions.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
