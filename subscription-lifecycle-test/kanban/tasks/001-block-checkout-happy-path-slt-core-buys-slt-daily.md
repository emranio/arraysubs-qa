---
id: 1
title: 'Block checkout happy path: slt-core buys SLT Daily Core on Stripe 4242 (control record)'
status: todo
priority: critical
created: 2026-08-02T03:43:02.949662313+02:00
updated: 2026-08-02T03:43:12.907034595+02:00
tags:
    - checkout
    - day-00
    - has-conflicts
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

### ⚠ Conflict resolutions that apply to this task

**`critical` · duplicate-purchase / control-spine destruction** — with `SLT-REN-01`

- *Problem:* Both are tagged d0 and both place the SAME purchase: slt-core buys SLT Daily Core on the block checkout with Stripe 4242. CHK-01 step 3-6 and REN-01 step 3-4 are the same checkout. With multiple_subscriptions.auto_migrate_on_checkout=true (frozen baseline) the second checkout does not create a second subscription - CheckoutMigrationTrait migrates the existing one, rewriting _product_id/_recurring_amount and re-anchoring the schedule. That destroys the reference record (CHK-01's meta baseline for CHK-02's field-by-field diff) AND the day/1 renewal spine that REN-02, EML-02, EML-03, EML-05, EML-15, MYA-02, ADM-02, ADM-06 and the whole D1-D12 watch depend on. CHK-01's own precondition ('slt-core owns no SLT Daily Core sub and must never rebuy it - C08') is violated by REN-01.
- *Required fix:* Merge into one owner. SLT-CHK-01 is the sole purchaser and must execute inside REN-01's clock window (13:00-13:30 site, 2026-08-02) so both tasks' timing constraints are satisfied. SLT-REN-01 drops steps 1-5 and becomes an observation leg attached to CHK-01's SUB/ORDER: it keeps steps 6-11 (cron-not-CLI proof, AS leg timestamps, wp_actionscheduler_logs 'via WP Cron' assertion, D1/D2 follow-ups). Publish SUB_CORE=S1 and k to the registry from CHK-01. Add a hard precondition to REN-01: 'places no order'.

**`high` · session/cart collision (persistent cart)** — with `SLT-CHK-14`, `SLT-LIFE-04`, `SLT-CHK-11`, `SLT-CHK-13`, `SLT-MYA-02`, `SLT-ADM-02`

- *Problem:* Audit C09's fix - one named agent-browser session per task - isolates GUEST carts only. WooCommerce persists a logged-in customer's cart to user meta (_woocommerce_persistent_cart_<blog_id>) and restores it into any session that authenticates as that user. Several tasks therefore share a cart despite having distinct session names: on D0 slt-core is used concurrently by SLT-CHK-01 (cust-SLT-CHK-01), SLT-CHK-14 (core-CHK14) and SLT-LIFE-04 (life04); on D2 slt-trial by SLT-CHK-15 (trial-CHK15) and SLT-EML-09 (cust-SLT-EML-09); on D4/D5 slt-core by SLT-CHK-13 (core-CHK13), SLT-CHK-11 (core-CHK11), SLT-MYA-02 and SLT-ADM-02. A leftover subscription line leaking across sessions makes allow_multiple_in_cart=false reject the next add-to-cart for the wrong reason, or - worse - a two-subscription cart reaches checkout and the wrong subscription is created.
- *Required fix:* Add a standing rule to the isolation contract: never run two tasks concurrently under the same slt-* login, and serialise same-account tasks within a day (the calendar's intra-day ordering is binding, not advisory). Every task that logs in must, as its first browser action after login, assert the cart is EMPTY and treat a non-empty cart as a STOP condition with an issue filed - not as something to silently empty. Add a WP-CLI pre-flight to same-account days: `wp user meta get <uid> _woocommerce_persistent_cart_1 --allow-root` must be empty before the task's checkout, and empty again at teardown.

**`low` · duplicate-coverage** — with `SLT-EML-15`, `SLT-REN-02`, `SLT-ADM-06`, `SLT-EML-03`, `SLT-EML-06`, `SLT-EML-07`

- *Problem:* Six overlapping clusters, each spending an execution slot on a code path another task already proves. (a) SLT-EML-15, SLT-REN-02 and SLT-ADM-06 all read the same SUB_CORE renewal cycle: EML-15 reconciles the mail set, REN-02 asserts the schedule re-arm, ADM-06 asserts the order typing/linkage - three tasks, one cycle, three separate evidence sets. (b) SLT-EML-03's Stripe leg re-asserts REN-02's payment_successful and its Paddle leg re-asserts SLT-REN-04's. (c) SLT-EML-06 re-proves new_subscription + admin_new_subscription at a Stripe block checkout, which is SLT-CHK-01's email rows 3 and 4 verbatim, on a new account. (d) SLT-EML-07 and SLT-SW-10 both drive pending-cancellation -> cancelled -> reactivation and both assert the same four emails. (e) SLT-EML-04's four payment_failed pairs are exactly SLT-DUN-01 ER8 plus SLT-DUN-02 ER9. (f) SLT-SYN-11's 'flex section hidden for a Different Renewal Price product' repeats SLT-PROD-05 steps 7-9 and SLT-SYN-01's probe. (g) SLT-LIFE-02's Paddle 'no Renew Early control' negative repeats SLT-CHK-04 ER7 and SLT-PROD-16.
- *Required fix:* Keep one owner per assertion and make the others cite it. (a) EML-15 owns the reconciled mail set for the cycle and publishes it to the registry; REN-02 keeps only the schedule/offset/no-drift assertions; ADM-06 keeps only the HPOS meta assertions and drops its Related-Orders screenshot in favour of ADM-02's. (b) EML-03 keeps only the content assertions (amount, method row, UTC+6 next date, the Paddle ordering hazard) and cites REN-02/REN-04 for 'the renewal fired'. (c) EML-06 keeps only the gating-key and subject-string proof (emails.new_subscription.enabled, admin recipient resolution, the B4 dead-setting verdict) and cites CHK-01 for the checkout. (d) EML-07 owns the email set; SW-10 owns the reason-required / offers-declined / scheduled-cancel-timestamp / reactivation-scheduling-bug half and cites EML-07's mailpit ids. (e) EML-04 places no purchase and becomes the mail-content rider on the DUN ladder (attempt-number visibility, Pay Now link resolution, To: headers) - see the DUN re-day entry. (f) SYN-11 keeps only the force-set-meta half (isEnabled() true, getConfig() null, zero _renewal_sync_* on the subscription) and cites PROD-05 for the UI-absence screenshots. (g) LIFE-02 cites CHK-04's screenshot rather than re-driving the Paddle portal.

---
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
- `slt-core` owns no SLT Daily Core sub and must never rebuy it — `auto_migrate_on_checkout=true` (C08).

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
3. Open `/cart/`; assert EMPTY; shot `-01-cart.png`. Open `/slt-daily-core` → **Add to cart**; reopen `/cart/`; record subtotal + recurring line; shot `-02-cart2.png`.
4. Open `/checkout/`; confirm block checkout, prefilled billing, **Total $10.00**, no tax row; shot `-03-gateways.png` (list every gateway offered).
5. Pick **Credit Card (Stripe)**, fill the UPE iframe, record the default state of **Save payment information** unchanged.
6. **Place Order**; wait for `/checkout/order-received/<ORDER>/`; shot `-04-received.png`; record `ORDER`; capture `console`/`network` (JS errors, non-2xx on the Store API).
7. `mailpit-agent wait-new $PRE 180 "is active"`, then `mailpit-agent list 20`; map every new message to a row below.
8. Identify `SUB` (newest `arraysubs_data` post; exactly one new); dump `wp post meta list $SUB` to `slt-evidence/SLT-CHK-01-sub-meta.txt` (baseline for CHK-02); read the `wp_wc_orders` row and `_subscription_ids`/tax items for `$ORDER`.
9. Compute spread offset `k` for `$SUB` (REF-01 §0); **Tools → Scheduled Actions** → Pending, search `$SUB`; shot `-05-pending.png`; record both GMT timestamps.
10. My Account → Subscriptions detail; shot `-06-myaccount.png`; append IDs to the registry; close this session.
11. D1 watch follow-up, force nothing: renewal #1 fires at `_next_payment_date + k`, bills $10.00, advances the date 24 h.

## Expected results
1. Exactly one new `arraysubs_data` post, status `arraysubs-active`.
2. Order `processing` (virtual), `total_amount=10.00`, `currency=USD`, `payment_method=stripe`, zero tax items; Store API returned 200.
3. `_parent_order_id=$ORDER`, `_order_ids=["$ORDER"]`, order `_subscription_ids=[$SUB]`.
4. `_billing_period=day`, `_billing_interval=1`, `_subscription_length=0`, `_trial_length=0`, `_next_payment_date = _start_date + 24h` exactly, `_completed_payments=1`, `_last_payment_date` set.
5. `_subscription_price`=`_recurring_amount`=`10`, `_currency=USD`, `_signup_fee` empty/`0`, `_quantity=1`, `_customer_email` = slt-core's.
6. `_payment_method=stripe`, `_gateway_customer_id` `^cus_`, `_gateway_payment_method_id` `^pm_`, `_auto_renew` absent/`on`.
7. `arraysubs_process_renewal[$SUB]` at `_next_payment_date + k` (±60 s), `arraysubs_generate_renewal_invoice[$SUB]` at `+k − 6h`; stored date unshifted.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC New order | order → processing | admin | `New order #$ORDER` | `list 20` |
| 2 | WC Processing order | order → processing | slt-core@example.test | `order has been received` | `list 20` |
| 3 | `new_subscription` | → `arraysubs-active` | slt-core@example.test | `subscription #$SUB is active` | `wait-new $PRE 180 "is active"` |
| 4 | `admin_new_subscription` | same | admin | `New subscription #$SUB` | `list 20` |
| 5 | NONE EXPECTED | signup | — | — | invoice mail suppressed for automatic+auto-renew subs (REF-01 §4), `payment_successful` is renewal-only, reminder lead 3d > 1d cycle: no `Invoice for subscription`/`Payment received`/`renews soon`/`free trial`/`account on` |

## Evidence to capture
- Shots 01–06 in `/home/server-manager/slt-evidence/`; `SUB`, `ORDER`, `k`, both AS timestamps; `SLT-CHK-01-sub-meta.txt`; `PRE` + Mailpit IDs.

## Pass criteria
- [ ] One new subscription `arraysubs-active`; order `processing`, $10.00 USD, stripe, no tax line
- [ ] Two-way order/subscription linkage correct
- [ ] `_next_payment_date = _start_date + 24h`; schedule/amount meta exact; `_completed_payments=1`
- [ ] `cus_`/`pm_` meta stored; AS legs at `+k` and `+k − 6h`
- [ ] Exactly mails 1–4; row 5 negatives hold; no console errors

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
  fire on their own; **a renewal that does not fire is a real bug** — capture evidence before forcing.
- Give this task its own browser session (`agent-browser --session <role>-<TASK-KEY>`). Sessions are
  keyed by name and **share a cart**.
- Never run `wp action-scheduler run` without `--hooks=`; prefer a single action by ID.
- Evidence goes in `qa/subscription-lifecycle-test/evidence/<TASK-KEY>/`.
