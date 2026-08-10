---
id: 3
title: Buy SLT Lifetime One Time and prove no renewal is ever scheduled (12-day negative control)
status: done
priority: critical
created: 2026-08-02T03:43:03.18371421+02:00
updated: 2026-08-05T21:37:49.284231945+02:00
started: 2026-08-02T15:39:56.89708219+02:00
completed: 2026-08-02T15:39:56.89708219+02:00
tags:
    - checkout
    - day-00
due: "2026-08-02"
estimate: 1h
depends_on:
    - 7
    - 11
    - 12
class: standard
---

> **SLT-CHK-14** · group `checkout` · scheduled **D00** (2026-08-02)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Buy `SLT Lifetime One Time` for $49.00 and prove the negative the 12-day watch leans on: the subscription activates but nothing is ever scheduled for it — no `_next_payment_date`, no `_end_date`, no invoice leg, no charge leg, no reminder, no renewal mail. `arraysubs_calculate_next_payment_from_date()` returns empty for `lifetime`, so `RenewalScheduler::schedule()` is never reached.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing
- Plugins: free-only

## Preconditions
- `SLT-PROD-07` complete: `_subscription_period=lifetime`, `_subscription_interval=1`, `_subscription_length=0`, `_regular_price=49.00`. Quote the product ID from the registry.
- `SLT-SETUP-02` baseline; `SLT-SETUP-03` (`slt-core` + billing address).
- **Execute after 12:00 site time** (D0-D2 purchase-clock rule).
- Session `core-CHK14-SLT-CHK-14`; cart empty first and last.

## Test data
| Item | Value |
|---|---|
| Product | SLT Lifetime One Time (`slt-lifetime-one-time`) |
| Account | slt-core / `SltQa!2026#Pass` |
| Card | 4242 4242 4242 4242 |
| Today | **$49.00** once; renewals: none, ever |

## Steps
1. `PREV=$(mailpit-agent latest-id)`.
2. `agent-browser --session core-CHK14-SLT-CHK-14 open "https://mirror-help.arrayhash.com/my-account/"` -> log in `slt-core`.
3. Open `/product/slt-lifetime-one-time/` -> `snapshot -i`. Record the price/summary text verbatim; no "every N days" phrasing.
4. **Add to cart** -> `/cart/` -> `snapshot -i`: total $49.00, no recurring schedule line, no signup fee.
5. `/checkout/` -> `snapshot -i`. Record which gateways the accordion offers (lifetime is never sync-eligible, so Paddle must appear beside Stripe). Pay Stripe 4242 -> **Place Order**.
6. Record the order and publish the subscription's exact numeric ID under canonical alias **`SUB_LIFETIME`**. `mailpit-agent wait-new "$PREV" 180 "is active"`.
7. `wp post meta list <SUB_LIFETIME> --keys=_product_id,_billing_period,_billing_interval,_recurring_amount,_next_payment_date,_end_date,_renewal_action_id,_renewal_invoice_action_id,_renewal_reminder_action_id --allow-root`.
8. Tools -> Scheduled Actions: search the sub ID across Pending and Complete, and in groups `arraysubs-renewals`/`-billing`/`-emails`; screenshot the empty result.
9. Open ArraySubs → Subscriptions (`admin.php?page=arraysubs-mainadmin#/subscriptions`), search for the subscription ID, and click **View Details**; screenshot the details panel — next payment must render empty/none or an explicit no-recurring-payment message, never a date. (`post.php?post=<SUB_ID>&action=edit` is not a valid route for this post type.)
10. Open the `/my-account/` subscription view -> screenshot; record whether any next-payment row is shown.
11. Empty cart; `agent-browser --session core-CHK14-SLT-CHK-14 close`.
12. Standing watch D1-D12: this sub must keep an empty `_next_payment_date`, zero scheduled actions, zero renewal orders, zero renewal mail. Any change is a critical bug.

## Expected results
1. Order total exactly **$49.00**, status `processing`/`completed`, no tax line, one line item.
2. Sub `arraysubs-active`, `_product_id`=lifetime product ID, `_billing_period=lifetime`, `_billing_interval=1`, `_recurring_amount=49.00`.
3. `_next_payment_date` is EMPTY (absent or empty string) and `_end_date` is absent/empty.
4. `_renewal_action_id`, `_renewal_invoice_action_id`, `_renewal_reminder_action_id` are all absent.
5. Scheduled Actions returns no row for this subscription ID in any state or group.
6. Admin and my-account render no next-payment date and no renewal countdown; an explicit semantic label such as `No recurring payment` / `Lifetime Deal — No recurring payment` is valid.
7. Checkout offered both Stripe and Paddle for this product (lifetime is not sync-eligible).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | WC New order | paid checkout | admin | `New order #$ORDER` | Complete owner-filtered delta after `$PREV`; save/show the exact matching id |
| 2 | WC Completed order | paid virtual-only order → completed | slt-core@example.test | `is on its way` | Complete owner-filtered delta after `$PREV`; save/show the exact matching id |
| 3 | new_subscription | order paid | slt-core@example.test | `is active` | `mailpit-agent wait-new "$PREV" 180 "is active"` |
| 4 | admin_new_subscription | order paid | admin | `New subscription #` | Complete owner-filtered delta after `$PREV`; save/show the exact matching id |
| 5 | NONE EXPECTED — renewal_reminder | ever | — | — | No `renews soon` naming this subscription, D0-D12 |
| 6 | NONE EXPECTED — renewal_invoice / payment_successful | ever | — | — | No `Invoice for subscription #<ID>`, no second `Payment received` |
| 7 | NONE EXPECTED — expiring_soon | ever | — | — | Nothing schedules `arraysubs_send_expiring_soon` on this build (REF-04 B1) |

## Evidence to capture
- `SLT-CHK-14-01-product.png`, `-02-cart.png`, `-03-checkout-gateways.png`, `-04-actions-empty.png`, `-05-admin-sub.png`, `-06-myaccount.png`.
- Order + subscription ID (post both to the registry so every watch day can re-assert the negative), meta dump, Mailpit IDs, console/network errors.

## Pass criteria
- [x] Order total exactly $49.00
- [x] Subscription active with `_billing_period=lifetime`
- [x] `_next_payment_date` and `_end_date` empty
- [x] No renewal/invoice/reminder action IDs on the sub
- [x] Scheduled Actions shows nothing for this sub ID
- [x] Admin and my-account show no recurring payment and no date/countdown
- [x] Emails 1-4 captured; negatives 5-7 hold on the day of purchase

## Isolation / teardown
- Hands the watch canonical `SUB_LIFETIME` as its permanent negative control; that exact ID must be quoted in every daily report D1-D12.
- Nothing global changed; cart emptied; only `core-CHK14-SLT-CHK-14` closed. This negative control remains active through D12 and is cancelled/deleted by post-watch `SLT-SETUP-99B` only.

## Execution record — 2026-08-02

**PASS (D0 purchase and negative-control handoff).** `slt-core` bought product `11938` through the block checkout with the saved Stripe test card. Order `12002` completed for exactly `$49.00`; canonical `SUB_LIFETIME=12003` is active with `_billing_period=lifetime`, `_recurring_amount=49`, and one completed payment. `_next_payment_date` is present but empty; `_end_date` and all three renewal action-ID metas are absent. A direct all-state Scheduled Actions search and database query returned no rows for `12003`; the HPOS renewal-order query also returned no rows.

The admin detail screen renders `Next Payment: No recurring payment`, and My Account renders `Next Payment: Lifetime Deal — No recurring payment`; neither exposes a date or countdown. Checkout offered saved/new Stripe, Paddle, BACS, and check payment options. Mailpit captured exactly the four expected purchase messages: customer completed order `2WMiAxkWXaQPYT9CpZgDgQ`, admin new order `7kh7ORpnpyTDruBAPSBtD9`, customer active subscription `0ifwfwhOdw19sLPUtVdrEZ`, and admin new subscription `66P56OMuvYo2mpgVB7vOHm`.

Evidence: `/home/server-manager/slt-evidence/SLT-CHK-14-01-product.png` through `SLT-CHK-14-06-myaccount.png`, plus `/home/server-manager/slt-evidence/SLT-CHK-14-meta.txt`. Subscription `12003` is now the permanent lifetime negative control for the D1-D12 automated watch and remains in the tail cohort for `SLT-SETUP-99B` on 2026-08-15.

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
