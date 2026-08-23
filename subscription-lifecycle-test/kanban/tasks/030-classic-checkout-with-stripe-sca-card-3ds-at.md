---
id: 30
title: 'Classic checkout with Stripe SCA card: 3DS at signup, then requires_action on the off-session renewal'
status: todo
priority: high
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T19:10:00+02:00
tags:
    - cycle-2
    - granular
    - checkout
    - day-02
due: "2026-08-25"
estimate: 1h45m
depends_on:
    - 1
    - 10
    - 12
class: standard
---

> **SLT-CHK-05** · group `checkout` · scheduled **D02** (2026-08-25)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Buy `SLT2 Daily Core` on the **classic** checkout with the 3DS card `4000 0027 6000 3184`, clear the on-session challenge, then prove what the saved method does on the next off-session renewal: per REF-09 it returns `requires_action`, treated as pending not failure, so the task also completes the verification link.

## Scope
- Gateway: Stripe test
- Checkout: classic
- Account: new registered (created here)
- Plugins: core-owned Stripe automatic-payment/SCA path

## Preconditions
- SLT-CHK-01 done; `/slt2-classic-checkout` works.
- **Creates account `slt2-sca`**: `slt2-core`, `slt2-core2` and `slt2-guest-d0` already own SLT2 Daily Core fixtures. Reusing one would create an ambiguous duplicate at `one_per_customer=false`, so repeats are forbidden.
- Run after 12:00 site, no later than D2. The renewal must fire by itself.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Daily Core, day/1, $10.00 |
| Account | slt2-sca / slt2-sca@example.test / `SltQa!2026#Pass`, Customer, billing Dhaka BD 1207 |
| Card | 4000 0027 6000 3184 (3DS), `12/34`, CVC 123 |
| Sessions | `cust-SLT-CHK-05`, `admin-SLT-CHK-05` |

## Steps
1. `USER_PRE=$(mailpit-agent latest-id)`; record `SUBCOUNT_BEFORE`. In `admin-SLT-CHK-05`, create `slt2-sca` per SETUP-03 (notification unticked, Customer, billing address); record `UID`. Before any checkout, classify the setup-only mail delta after `USER_PRE`: exactly one admin-addressed `New User Registration` is allowed, and there must be no customer-addressed account/password mail. Record its ID, then set `PRE=$(mailpit-agent latest-id)` as the checkout-only baseline.
2. `agent-browser --session cust-SLT-CHK-05 open ".../my-account/"` → log in as `slt2-sca`; `/cart/` EMPTY; `/product/slt2-daily-core/` → **Add to cart**.
3. Open `/slt2-classic-checkout`; confirm **Total $10.00**, no tax row; pick **Credit Card (Stripe)**; enter the 3DS card; **Place order**.
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
| 1 | WC New order + Completed order | paid virtual-only signup → completed | admin / slt2-sca | `New order #$ORDER`, `is on its way` | Complete owner-filtered delta after `$PRE`; save/show exact matching ids |
| 2 | `new_subscription` + `admin_new_subscription` | → `arraysubs-active` | slt2-sca / admin | `#$SUB is active`, `New subscription #$SUB` | `mailpit-agent wait-new "$PRE" 180 active` |
| 3 | `renewal_requires_verification` | renewal → requires_action | slt2-sca | `Verify your subscription renewal #<numeric SUB>` | owned by `SLT-REN-05` using its recorded `PRE5B` baseline; this checkout task does not perform a second wait or renewal action |
| 4 | `payment_successful` | verification done | slt2-sca | `Payment received for subscription #$SUB` | Complete owner-filtered renewal delta after the task's registered pre-charge baseline; save/show the exact matching id |
| 5 | NONE EXPECTED | renewal leg | — | — | requires_action is not a failure: no `Payment failed`, no admin failure mail, no `on hold` |

The one admin-only WordPress registration notice created before `PRE` is setup traffic, not checkout traffic. Any customer account/password message despite the unticked notification box is a finding and must be written as a QA issue card.

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
- Creates `slt2-sca`: add to the account matrix and SETUP-99B's deletion list; it buys nothing else. `SLT-REN-05` places no order and operates only on this exact subscription. If verification is not done within a day the grace clock puts the sub on hold (cancel ≈ +4 days) — record it, do not mask it. Cancelled by SETUP-99A on D11.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
