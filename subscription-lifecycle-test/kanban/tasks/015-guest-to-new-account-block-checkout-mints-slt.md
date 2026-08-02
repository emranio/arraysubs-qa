---
id: 15
title: 'Guest to new account: block checkout mints slt-guest-d0 mid-flow and owns order + subscription'
status: todo
priority: critical
created: 2026-08-02T03:43:04.362506442+02:00
updated: 2026-08-02T03:43:14.198550645+02:00
tags:
    - checkout
    - day-01
    - has-conflicts
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

### ⚠ Conflict resolutions that apply to this task

**`medium` · contradictory-precondition (factually wrong)** — with `SLT-CHK-10`, `SLT-SETUP-03`

- *Problem:* SLT-CHK-03's objective and precondition assert 'a logged-out visitor cannot check out anonymously - woocommerce_enable_guest_checkout=no'. The README's verified environment baseline says the option is `yes`, and SLT-CHK-10 carries an explicit documentation correction ('That is FALSE - verified yes on 2026-08-02, alongside woocommerce_enable_signup_and_login_from_checkout=yes') and files an issue against SLT-SETUP-03 for the same claim. CHK-03 runs two days before CHK-10, so it will observe an offered guest path for a non-subscription cart, or reason about the wrong mechanism and file a false bug against the checkout registration force.
- *Required fix:* Rewrite CHK-03's objective and precondition to the correct mechanism: guest checkout IS enabled site-wide; registration is forced only for subscription carts, via woocommerce_checkout_registration_required (SubscriptionCheckout/Services/Hooks.php:103, CheckoutHelpersTrait.php:93-100) gated on checkout.auto_create_account=true AND cartHasSubscriptionCheckoutItems(). Keep the assertion 'no continue-as-guest option for THIS cart' and add step 1a: `wp option get woocommerce_enable_guest_checkout --allow-root` must print `yes`. Correct SLT-SETUP-03's objective in the catalog at the same time so CHK-10's issue is a confirmation rather than a discovery.

---
## Objective
Prove the guest → new account path. A logged-out visitor cannot check out anonymously — `woocommerce_enable_guest_checkout=no`, and ArraySubs forces registration on subscription carts — so the block checkout must mint `slt-guest-d0@example.test` and own the order and subscription.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: guest → new
- Plugins: both

## Preconditions
- SLT-PROD-01 and SLT-CHK-01 done (CHK-01 is the baseline).
- **No `slt-guest-d0` user may exist** — SETUP-03 reserved the address so this task creates it. Check `wp user list | grep slt-guest`; abort if present.
- `checkout.auto_create_account=true` (SETUP-02 frozen table). Unique session so no other cart leaks in. Run after 12:00 site.

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core, day/1, $10.00 |
| Account | none → `slt-guest-d0@example.test`, minted at checkout |
| Billing | SLT Guest, 1 SLT Way, Dhaka BD 1207 |
| Card | 4242 4242 4242 4242, `12/34`, CVC 123 |
| Session | `guest-SLT-CHK-03` (unique) |

## Steps
1. `PRE=$(mailpit-agent latest-id)`; record `SUBCOUNT_BEFORE`; confirm no `slt-guest-d0` user.
2. `agent-browser --session guest-SLT-CHK-03 open ".../slt-daily-core"` → `snapshot -i`; confirm logged-out. Open `/cart/`, assert EMPTY; back to the product → **Add to cart**; reopen `/cart/`; shot `-01-cart.png`.
3. Open `/checkout/` → `snapshot -i`; record what the account area offers; shot `-02-account.png`.
4. Enter `slt-guest-d0@example.test`, fill billing, set a password if asked (record whether it asks).
5. Select **Credit Card (Stripe)**, fill the UPE iframe, **Place Order**; wait for order-received; record `ORDER`; shot `-03-received.png`; capture `console`/`network`.
6. `mailpit-agent wait-new $PRE 180 "is active"`; `mailpit-agent list 20`; map every new message.
7. `wp user list --fields=ID,user_login,user_email,roles | grep slt-guest` → record `UID`, login, role.
8. Identify `SUB` (one new `arraysubs_data` post); dump its meta to `slt-evidence/SLT-CHK-03-sub-meta.txt`; read the `wp_wc_orders` row for `$ORDER` (note `customer_id`).
9. Compute `k` (REF-01 §0); **Tools → Scheduled Actions** → Pending for `$SUB`; shot `-04-pending.png`.
10. In the same session open `/my-account/subscriptions/` — already logged in, subscription listed; shot `-05-myaccount.png`.
11. Append `UID`/`SUB`/`ORDER` to the registry; close this session. Watch follow-up next morning, force nothing: it renews daily like CHK-01.

## Expected results
1. No anonymous path: no "continue as guest" option for this cart; record the wording and whether a password field appeared.
2. Exactly one new user: login derived from the address, `user_email=slt-guest-d0@example.test`, role `customer`.
3. `wp_wc_orders.customer_id = $UID` (not `0`), `processing`, `total_amount=10.00`, `currency=USD`, `payment_method=stripe`, zero tax items.
4. Exactly one new `arraysubs_data` post, `arraysubs-active`, `_customer_id=$UID`, `_customer_email` = the guest address.
5. Structure matches CHK-01: `_billing_period=day`, `_billing_interval=1`, `_subscription_length=0`, `_trial_length=0`, `_subscription_price`=`_recurring_amount`=`10`, `_completed_payments=1`, `_parent_order_id=$ORDER`, order `_subscription_ids=[$SUB]`.
6. `_next_payment_date = _start_date + 24h`; `_gateway_customer_id` `^cus_`; `_gateway_payment_method_id` `^pm_`; AS legs at `+k`, `+k−6h`.
7. The session is authenticated after checkout; My Account lists the subscription.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC Customer new account | account minted at checkout | slt-guest-d0 | `Your account on` | `list 20` |
| 2 | WC New order | → processing | admin | `New order #$ORDER` | `list 20` |
| 3 | WC Processing order | → processing | slt-guest-d0 | `order has been received` | `list 20` |
| 4 | `new_subscription` | → `arraysubs-active` | slt-guest-d0 | `subscription #$SUB is active` | `wait-new $PRE 180 "is active"` |
| 5 | `admin_new_subscription` | same | admin | `New subscription #$SUB` | `list 20` |
| 6 | NONE EXPECTED | signup | — | — | no `Invoice for subscription`/`Payment received`/`renews soon`/`free trial` |

## Evidence to capture
- Shots 01–05; `UID`, `SUB`, `ORDER`, `k`; `SLT-CHK-03-sub-meta.txt`; order row with `customer_id`; `PRE` + Mailpit IDs.

## Pass criteria
- [ ] No guest path offered; account auto-created at the reserved address, role `customer`
- [ ] Order owned by `$UID`, `processing`, $10.00, no tax line
- [ ] One `arraysubs-active` sub owned by `$UID`, structurally = CHK-01
- [ ] `_next_payment_date = _start_date + 24h`; AS legs at `+k` / `+k−6h`
- [ ] Mails 1–5 present incl. the account mail; row 6 negatives hold

## Isolation / teardown
- Creates the reserved `slt-guest-d0` account — record its ID in the registry and add it to SETUP-99B's deletion list. `slt-guest-d5@example.test` stays reserved. The sub stays live for the watch, cancelled by SETUP-99A on D10.

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
