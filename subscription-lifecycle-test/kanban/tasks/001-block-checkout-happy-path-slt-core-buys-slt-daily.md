---
id: 1
title: 'Block checkout happy path: slt2-core buys SLT2 Daily Core on Stripe 4242 (control record)'
status: blocked
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T22:35:27.314622846+02:00
tags:
    - cycle-2
    - granular
    - checkout
    - day-00
due: "2026-08-23"
estimate: 1h
depends_on:
    - 10
    - 11
    - 12
    - 5
    - 131
class: standard
---

## Current execution blocker — 2026-08-23 site date

Blocked by critical shared issue `qa/issues` #1 / preflight task `131`: the enabled Stripe test secondary webhook is missing required `payment_method.attached` and `customer.updated` events. Product `31340` and customer `474` are ready, but no checkout/order/subscription/charge was attempted. Retry only after issue #1 is fixed and task 131's live remote health check passes.

> **SLT-CHK-01** · group `checkout` · scheduled **D00** (2026-08-23)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
The reference purchase of the window: `slt2-core` buys one `SLT2 Daily Core` ($10.00) on the block checkout (page 8, which stays block all window) with Stripe 4242. Later checkout tasks diff against it: subscription shape, order linkage, schedule meta, scheduler legs, token meta, mail set.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing
- Plugins: both

## Preconditions
- SLT-SETUP-01/02/03 + SLT-PROD-01 done; `slt2-core` has a billing address.
- Run **after 12:00 site time** so the anniversary misses D3's SYN-04 bracket.
- `slt2-core` owns no SLT2 Daily Core sub and must never rebuy it. At the frozen `one_per_customer=false` baseline, `auto_migrate_on_checkout=true` is inert; a rebuy would create a duplicate and make the control spine ambiguous (see SLT-CHK-08).

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Daily Core (`slt2-daily-core`), day/1, $10.00 |
| Account | slt2-core / `SltQa!2026#Pass` |
| Card | 4242 4242 4242 4242, `12/34`, CVC 123 |
| Session | `cust-SLT-CHK-01` |

## Steps
1. Record `SUBCOUNT_BEFORE` (`arraysubs_data` count) and `PRE=$(mailpit-agent latest-id)`.
2. `agent-browser --session cust-SLT-CHK-01 open ".../my-account/"` → `snapshot -i` → log in as `slt2-core`.
3. Open `/cart/`; assert EMPTY; shot `-01-cart.png`. Open `/product/slt2-daily-core/` → **Add to cart**; reopen `/cart/`; record subtotal + recurring line; shot `-02-cart2.png`.
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
5. `_subscription_price`=`_recurring_amount`=`10`, `_currency=USD`, `_signup_fee` empty/`0`, `_quantity=1`, `_customer_email` = slt2-core's.
6. `_payment_method=stripe`, `_gateway_customer_id` `^cus_`, `_gateway_payment_method_id` `^pm_`, `_auto_renew` absent/`on`.
7. `arraysubs_process_renewal[$SUB]` at `_next_payment_date + k` (±60 s), `arraysubs_generate_renewal_invoice[$SUB]` at `+k − 6h`; stored date unshifted.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC New order | paid checkout | admin | `New order #$ORDER` | Complete owner-filtered delta after `$PRE`; save/show the exact matching id |
| 2 | WC Completed order | paid virtual-only order → completed | slt2-core@example.test | `is on its way` | Complete owner-filtered delta after `$PRE`; save/show the exact matching id |
| 3 | `new_subscription` | → `arraysubs-active` | slt2-core@example.test | `subscription #$SUB is active` | `mailpit-agent wait-new "$PRE" 180 "is active"` |
| 4 | `admin_new_subscription` | same | admin | `New subscription #$SUB` | Complete owner-filtered delta after `$PRE`; save/show the exact matching id |
| 5 | NONE EXPECTED | signup | — | — | invoice mail suppressed for automatic+auto-renew subs (REF-01 §4), `payment_successful` is renewal-only, reminder lead 3d > 1d cycle: no `Invoice for subscription`/`Payment received`/`renews soon`/`free trial`/`account on` |

## Evidence to capture
- Shots 01–06 in `/home/server-manager/slt-evidence/`; `SUB`, `ORDER`, `k`, both AS timestamps; `SLT-CHK-01-sub-meta.txt`; `PRE` + Mailpit IDs.

## Pass criteria
- [ ] One new subscription `arraysubs-active`; order `completed`, $10.00 USD, stripe, no tax line
- [ ] Two-way order/subscription linkage correct
- [ ] `_next_payment_date = _start_date + 24h`; schedule/amount meta exact; `_completed_payments=1`
- [ ] `cus_`/`pm_` meta stored; AS legs at `+k` and `+k − 6h`
- [ ] Exactly mails 1–4; row 5 negatives hold; no console errors

## Isolation / teardown
- Hands SLT-CHK-02 the meta baseline and the watch the day/1 renewal spine — **do not cancel before SLT-SETUP-99A on D11**. `slt2-core` is bound to SLT2 Daily Core. Nothing global changed; cart left empty.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
