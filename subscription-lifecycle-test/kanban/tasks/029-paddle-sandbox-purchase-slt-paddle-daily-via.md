---
id: 29
title: 'Paddle sandbox purchase: SLT Paddle Daily via overlay, webhook-paid order, next_billed_at override'
status: todo
priority: high
created: 2026-08-02T03:43:05.360878004+02:00
updated: 2026-08-02T03:43:15.799208378+02:00
tags:
    - checkout
    - day-02
    - has-conflicts
due: "2026-08-04"
estimate: 1h30m
depends_on:
    - 23
    - 26
class: standard
---

> **SLT-CHK-04** · group `checkout` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`low` · duplicate-coverage** — with `SLT-EML-15`, `SLT-REN-02`, `SLT-ADM-06`, `SLT-EML-03`, `SLT-EML-06`, `SLT-CHK-01`

- *Problem:* Six overlapping clusters, each spending an execution slot on a code path another task already proves. (a) SLT-EML-15, SLT-REN-02 and SLT-ADM-06 all read the same SUB_CORE renewal cycle: EML-15 reconciles the mail set, REN-02 asserts the schedule re-arm, ADM-06 asserts the order typing/linkage - three tasks, one cycle, three separate evidence sets. (b) SLT-EML-03's Stripe leg re-asserts REN-02's payment_successful and its Paddle leg re-asserts SLT-REN-04's. (c) SLT-EML-06 re-proves new_subscription + admin_new_subscription at a Stripe block checkout, which is SLT-CHK-01's email rows 3 and 4 verbatim, on a new account. (d) SLT-EML-07 and SLT-SW-10 both drive pending-cancellation -> cancelled -> reactivation and both assert the same four emails. (e) SLT-EML-04's four payment_failed pairs are exactly SLT-DUN-01 ER8 plus SLT-DUN-02 ER9. (f) SLT-SYN-11's 'flex section hidden for a Different Renewal Price product' repeats SLT-PROD-05 steps 7-9 and SLT-SYN-01's probe. (g) SLT-LIFE-02's Paddle 'no Renew Early control' negative repeats SLT-CHK-04 ER7 and SLT-PROD-16.
- *Required fix:* Keep one owner per assertion and make the others cite it. (a) EML-15 owns the reconciled mail set for the cycle and publishes it to the registry; REN-02 keeps only the schedule/offset/no-drift assertions; ADM-06 keeps only the HPOS meta assertions and drops its Related-Orders screenshot in favour of ADM-02's. (b) EML-03 keeps only the content assertions (amount, method row, UTC+6 next date, the Paddle ordering hazard) and cites REN-02/REN-04 for 'the renewal fired'. (c) EML-06 keeps only the gating-key and subject-string proof (emails.new_subscription.enabled, admin recipient resolution, the B4 dead-setting verdict) and cites CHK-01 for the checkout. (d) EML-07 owns the email set; SW-10 owns the reason-required / offers-declined / scheduled-cancel-timestamp / reactivation-scheduling-bug half and cites EML-07's mailpit ids. (e) EML-04 places no purchase and becomes the mail-content rider on the DUN ladder (attempt-number visibility, Pay Now link resolution, To: headers) - see the DUN re-day entry. (f) SYN-11 keeps only the force-set-meta half (isEnabled() true, getConfig() null, zero _renewal_sync_* on the subscription) and cites PROD-05 for the UI-absence screenshots. (g) LIFE-02 cites CHK-04's screenshot rather than re-driving the Paddle portal.

---
## Objective
Full Paddle sandbox purchase: `slt-paddle` buys `SLT Paddle Daily` ($11.00) through the block checkout and Paddle overlay. Paddle owns the schedule, so this proves the two-phase order (pending → paid by webhook), the Paddle meta, and how `next_billed_at` overrides the local date.

## Scope
- Gateway: Paddle sandbox
- Checkout: block
- Account: existing
- Plugins: pro-required

## Preconditions
- SLT-PROD-16 (`SLT Paddle Daily`) + SLT-SETUP-05 (Paddle readiness) done.
- `renewals.sync_to_billing_cycle` OFF and the product has no `_arraysubs_flex_sync_enabled`, else `maybeHideUnsupportedRenewalSyncGateways()` hides Paddle. Re-read that meta first.
- `slt-paddle` is Paddle-only. Run after 12:00 site, never inside D3's SYN-04 bracket.

## Test data
| Item | Value |
|---|---|
| Product | SLT Paddle Daily, day/1, $11.00 |
| Account | slt-paddle / `SltQa!2026#Pass` |
| Card | Paddle sandbox 4242 4242 4242 4242, future expiry |
| Session | `cust-SLT-CHK-04` |

## Steps
1. `PRE=$(mailpit-agent latest-id)`; record `SUBCOUNT_BEFORE`.
2. `agent-browser --session cust-SLT-CHK-04 open ".../my-account/"` → log in as `slt-paddle`; `/cart/` must be EMPTY; open `/slt-paddle-daily` → **Add to cart**.
3. Open `/checkout/` → `snapshot -i`; confirm **Total $11.00**, no tax row; shot `-01-gateways.png` — **Paddle must be listed**.
4. Select Paddle, **Place Order**. The overlay is a cross-origin iframe: re-`snapshot -i` once loaded, fill email/card/country, submit; shot `-02-overlay.png`.
5. On order-received record `ORDER`; shot `-03-received.png`; capture the pending notice verbatim and any console error.
6. Read `wp_wc_orders` for `$ORDER` at once; record the status **before** the webhook. Poll up to 5 min until it leaves `pending`; then `wait-new $PRE 300 "is active"` and `list 20`.
7. Identify `SUB`; dump its meta to `slt-evidence/SLT-CHK-04-sub-meta.txt`; record `_next_payment_date` as `NPD`.
8. Compute `k` (REF-01 §0). **Tools → Scheduled Actions** → Pending for `$SUB`; shot `-04-pending.png`; record both GMT timestamps against `NPD+k`/`NPD+k−6h`.
9. My Account → subscription detail; shot `-05-myaccount.png`; record whether **Renew Early** shows. Append `SUB`/`ORDER` to the registry; close the session.
10. Next-morning watch follow-up, force nothing: renewal #1 is Paddle-driven; the local `arraysubs_process_renewal` leg is a no-op noting "awaiting automatic charge from Paddle".

## Expected results
1. Paddle is offered for this cart; total `11.00` USD, zero tax items; right after Place Order the order is `pending` and the received page shows the Paddle notice.
2. After the webhook the order is paid (`processing`/`completed`) with `_paddle_transaction_id` + `_last_gateway_transaction_id`.
3. One new `arraysubs_data` post, `arraysubs-active`, `_payment_method=arraysubs_paddle`, `_recurring_amount=11`, `_currency=USD`, `_completed_payments=1`, `_parent_order_id=$ORDER`, `_subscription_ids=[$SUB]` on the order.
4. `_gateway_paddle_subscription_id` non-empty, `_gateway_paddle_payout_amount` set, no Stripe `_gateway_customer_id`/`_gateway_payment_method_id`.
5. `NPD` equals Paddle's `next_billed_at`, not necessarily `_start_date+24h` — record both and the difference.
6. **Known-fragile:** `syncNextPaymentDate()` writes meta only, so the AS legs may not match `NPD+k`/`NPD+k−6h`. Record the delta; a mismatch is an observation, not an auto-failure.
7. **Renew Early is hidden** despite `allow_early_renew=true`, because Paddle declares `early_renewal:false`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC New order | webhook pays order | admin | `New order #$ORDER` | `list 20` |
| 2 | WC Processing order | webhook pays order | slt-paddle | `order has been received` | `list 20` |
| 3 | `new_subscription` | → `arraysubs-active` | slt-paddle | `subscription #$SUB is active` | `wait-new $PRE 300 "is active"` |
| 4 | `admin_new_subscription` | same | admin | `New subscription #$SUB` | `list 20` |
| 5 | NONE EXPECTED | signup | — | — | no `Invoice for subscription`, no `Verify your subscription renewal` (Paddle `sca:false`), no `Payment failed` |

## Evidence to capture
- Shots 01–05; `SUB`, `ORDER`, `k`, `NPD`, `next_billed_at`, AS timestamps + delta; pre/post-webhook order rows; `SLT-CHK-04-sub-meta.txt`.

## Pass criteria
- [ ] Paddle offered; overlay completed; $11.00 USD, no tax
- [ ] Order seen `pending` first, then paid by webhook with Paddle transaction meta
- [ ] One `arraysubs-active` sub, `_payment_method=arraysubs_paddle`, Paddle sub id set
- [ ] `NPD` recorded against `next_billed_at`; AS-leg delta recorded
- [ ] Renew Early hidden, no Stripe token meta; mails 1–4 present; row 5 holds

## Isolation / teardown
- `SLT Paddle Daily` and `slt-paddle` stay Paddle-only all window. The sub stays live for the watch, cancelled by SETUP-99A on D10 — that also cancels the Paddle-side subscription, so never cancel early. Nothing global changed.

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
