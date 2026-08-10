---
id: 30
title: 'Classic checkout with Stripe SCA card: 3DS at signup, then requires_action on the off-session renewal'
status: done
priority: high
created: 2026-08-02T03:43:05.505522503+02:00
updated: 2026-08-05T21:37:49.363687834+02:00
started: 2026-08-05T21:02:05.892759613+02:00
completed: 2026-08-05T21:02:05.892759613+02:00
tags:
    - checkout
    - day-02
due: "2026-08-04"
estimate: 1h45m
depends_on:
    - 1
    - 10
    - 12
class: standard
---

> **SLT-CHK-05** · group `checkout` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Buy `SLT Daily Core` on the **classic** checkout with the 3DS card `4000 0027 6000 3184`, clear the on-session challenge, then prove what the saved method does on the next off-session renewal: per REF-09 it returns `requires_action`, treated as pending not failure, so the task also completes the verification link.

## Scope
- Gateway: Stripe test
- Checkout: classic
- Account: new registered (created here)
- Plugins: pro-required

## Preconditions
- SLT-CHK-01 done; `/slt-classic-checkout` works.
- **Creates account `slt-sca`**: `slt-core`, `slt-core2` and `slt-guest-d0` already own SLT Daily Core fixtures. Reusing one would create an ambiguous duplicate at `one_per_customer=false`, so repeats are forbidden.
- Run after 12:00 site, no later than D2. The renewal must fire by itself.

## Test data
| Item | Value |
|---|---|
| Product | SLT Daily Core, day/1, $10.00 |
| Account | slt-sca / slt-sca@example.test / `SltQa!2026#Pass`, Customer, billing Dhaka BD 1207 |
| Card | 4000 0027 6000 3184 (3DS), `12/34`, CVC 123 |
| Sessions | `cust-SLT-CHK-05`, `admin-SLT-CHK-05` |

## Steps
1. `USER_PRE=$(mailpit-agent latest-id)`; record `SUBCOUNT_BEFORE`. In `admin-SLT-CHK-05`, create `slt-sca` per SETUP-03 (notification unticked, Customer, billing address); record `UID`. Before any checkout, classify the setup-only mail delta after `USER_PRE`: exactly one admin-addressed `New User Registration` is allowed, and there must be no customer-addressed account/password mail. Record its ID, then set `PRE=$(mailpit-agent latest-id)` as the checkout-only baseline.
2. `agent-browser --session cust-SLT-CHK-05 open ".../my-account/"` → log in as `slt-sca`; `/cart/` EMPTY; `/product/slt-daily-core/` → **Add to cart**.
3. Open `/slt-classic-checkout`; confirm **Total $10.00**, no tax row; pick **Credit Card (Stripe)**; enter the 3DS card; **Place order**.
4. The 3DS modal is a Stripe iframe: re-`snapshot -i`, click **Complete authentication**; shot `-01-3ds.png`. On order-received record `ORDER`; shot `-02-received.png`.
5. `mailpit-agent wait-new "$PRE" 180 "is active"`; inspect the complete owner-filtered delta after `$PRE`. Read `LINK_JSON=$(wp post meta get "$ORDER" _subscription_ids --format=json --allow-root)`, resolve `SUB` only through a strict one-element numeric `jq -e` guard, and cross-check parent order/customer/product; never use `WC_Order::get_meta('_subscription_ids')` or select by recency on this HPOS runtime. Dump its meta to `/home/server-manager/slt-evidence/SLT-CHK-05-sub-meta.txt`; read the order row and `_gateway_*` keys.
6. Compute `k` (REF-01 §0); in `admin-SLT-CHK-05`, open **Tools → Scheduled Actions** → Pending for `$SUB`; shot `-03-pending.png`; record timestamps.
7. In the customer session reopen `/cart/`; prove it is EMPTY and the persistent-cart user meta is empty; capture `SLT-CHK-05-04-cart-empty.png`; close only `cust-SLT-CHK-05` and `admin-SLT-CHK-05` for this dated leg.
8. Publish `UID`, `SUB`, `ORDER`, `k`, `_next_payment_date`, both pending action IDs, `USER_PRE` plus the classified setup-mail ID, and the checkout-only `PRE` baseline to the registry as the hand-off to `SLT-REN-05`. Leave this card `in-progress`; **do not place another order**. Arm `SLT-REN-05` from this registry hand-off on D2; it intentionally has no board dependency on this card reaching `done`. The D3 observation, verification-link check, and payment are executed once by `SLT-REN-05`, and that evidence closes both cards.

## Expected results
1. Signup: order `completed`, `10.00` USD, `stripe`, zero tax (paid virtual-only checkout, matching CHK-01/02); one new `arraysubs_data` post, `arraysubs-active`, `_completed_payments=1`, `_next_payment_date=_start_date+24h`; AS legs at `+k`, `+k−6h`.
2. Saved method on both objects: `_gateway_customer_id` `^cus_`, `_gateway_payment_method_id` `^pm_`.
3. The off-session renewal returns `requires_action`: the renewal order stays **`pending`**, not `failed`; `_payment_retry_attempts` stays `0` (`handleManualPaymentPending()`).
4. Order meta `_arraysubs_payment_action_intent_id`/`_required_at`/`_url` and subscription meta `_arraysubs_payment_action_url`/`_intent_id` all set; the URL carries `wc-stripe-confirmation=1`.
5. Subscription still `arraysubs-active` here. After verification: renewal order paid, `_completed_payments=2`, `_next_payment_date` recomputed from `_renewal_scheduled_date` (start + 2 days), **not** from the payment time.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC New order + Completed order | paid virtual-only signup → completed | admin / slt-sca | `New order #$ORDER`, `is on its way` | Complete owner-filtered delta after `$PRE`; save/show exact matching ids |
| 2 | `new_subscription` + `admin_new_subscription` | → `arraysubs-active` | slt-sca / admin | `#$SUB is active`, `New subscription #$SUB` | `mailpit-agent wait-new "$PRE" 180 active` |
| 3 | `renewal_requires_verification` | renewal → requires_action | slt-sca | `Verify your subscription renewal #<numeric SUB>` | owned by `SLT-REN-05` using its recorded `PRE5B` baseline; this checkout task does not perform a second wait or renewal action |
| 4 | `payment_successful` | verification done | slt-sca | `Payment received for subscription #$SUB` | Complete owner-filtered renewal delta after the task's registered pre-charge baseline; save/show the exact matching id |
| 5 | NONE EXPECTED | renewal leg | — | — | requires_action is not a failure: no `Payment failed`, no admin failure mail, no `on hold` |

The one admin-only WordPress registration notice created before `PRE` is setup traffic, not checkout traffic. Any customer account/password message despite the unticked notification box is a finding and must be written as a standalone issue file.

## Evidence to capture
- Signup shots 01–04; `UID`, `SUB`, `ORDER`, `k`, AS timestamps; `SLT-CHK-05-sub-meta.txt`; `USER_PRE`, setup-mail ID, checkout-only `PRE`; final cart/persistent-cart proof. The renewal-order/action-meta/pay-page evidence comes from the linked `SLT-REN-05` observation leg and is cited here before closure.

## Pass criteria
- [ ] 3DS completed on classic; order `completed`, $10.00, no tax; `cus_`/`pm_` stored
- [ ] Renewal order `pending` (not `failed`), `_payment_retry_attempts=0`, sub still active
- [ ] Five action-context metas present with the confirmation URL
- [ ] Verification pays it: `_completed_payments=2`, next date = start + 2 days
- [ ] Mails 1–4 present; row 5 negatives hold
- [ ] Setup mail isolated before `PRE`; no customer account/password mail; final cart and persistent-cart meta empty

## Isolation / teardown
- Creates `slt-sca`: add to the account matrix and SETUP-99B's deletion list; it buys nothing else. `SLT-REN-05` places no order and operates only on this exact subscription. If verification is not done within a day the grace clock puts the sub on hold (cancel ≈ +4 days) — record it, do not mask it. Cancelled by SETUP-99A on D10.

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

[[2026-08-05]] Wed 21:02
UNVERIFIED (missed D02 execution window) on 2026-08-05.

This checkout task explicitly had to run after 12:00 site time and no later than D2. Live verification now finds no `slt-sca` user and no matching `SLT Daily Core` source subscription. Dependent rider #43 / `SLT-REN-05` was already closed as `UNVERIFIED (no source subscription)`, and the D03 suite report states that missing D2 SCA fixtures remain execution gaps unless a later authored recovery path explicitly permits creation. No such recovery path exists here, so this card closes without inventing a late replacement checkout or creating a new bug card.
