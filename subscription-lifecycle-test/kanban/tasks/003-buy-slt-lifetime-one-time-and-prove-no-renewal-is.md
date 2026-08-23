---
id: 3
title: Buy SLT2 Lifetime One Time and prove no renewal is ever scheduled (12-day negative control)
status: blocked
priority: critical
created: 2026-08-22T19:10:00+02:00
updated: 2026-08-22T22:35:27.879796918+02:00
tags:
    - cycle-2
    - granular
    - checkout
    - day-00
due: "2026-08-23"
estimate: 1h
depends_on:
    - 7
    - 11
    - 12
    - 131
class: standard
---

## Current execution blocker — 2026-08-23 site date

Blocked by critical shared issue `qa/issues` #1 / preflight task `131`. Lifetime product `31357` and customer `474` are ready, but the 12-day no-renewal control cannot begin with an invalid Stripe webhook preflight. No checkout/order/subscription/charge was attempted; retry immediately after task 131 passes.

> **SLT-CHK-14** · group `checkout` · scheduled **D00** (2026-08-23)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Buy `SLT2 Lifetime One Time` for $49.00 and prove the negative the 12-day watch leans on: the subscription activates but nothing is ever scheduled for it — no `_next_payment_date`, no `_end_date`, no invoice leg, no charge leg, no reminder, no renewal mail. `arraysubs_calculate_next_payment_from_date()` returns empty for `lifetime`, so `RenewalScheduler::schedule()` is never reached.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: existing
- Plugins: free-only

## Preconditions
- `SLT-PROD-07` complete: `_subscription_period=lifetime`, `_subscription_interval=1`, `_subscription_length=0`, `_regular_price=49.00`. Quote the product ID from the registry.
- `SLT-SETUP-02` baseline; `SLT-SETUP-03` (`slt2-core` + billing address).
- **Execute after 12:00 site time** (D0-D2 purchase-clock rule).
- Session `core-CHK14-SLT-CHK-14`; cart empty first and last.

## Test data
| Item | Value |
|---|---|
| Product | SLT2 Lifetime One Time (`slt2-lifetime-one-time`) |
| Account | slt2-core / `SltQa!2026#Pass` |
| Card | 4242 4242 4242 4242 |
| Today | **$49.00** once; renewals: none, ever |

## Steps
1. `PREV=$(mailpit-agent latest-id)`.
2. `agent-browser --session core-CHK14-SLT-CHK-14 open "https://mirror-help.arrayhash.com/my-account/"` -> log in `slt2-core`.
3. Open `/product/slt2-lifetime-one-time/` -> `snapshot -i`. Record the price/summary text verbatim; no "every N days" phrasing.
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
| 2 | WC Completed order | paid virtual-only order → completed | slt2-core@example.test | `is on its way` | Complete owner-filtered delta after `$PREV`; save/show the exact matching id |
| 3 | new_subscription | order paid | slt2-core@example.test | `is active` | `mailpit-agent wait-new "$PREV" 180 "is active"` |
| 4 | admin_new_subscription | order paid | admin | `New subscription #` | Complete owner-filtered delta after `$PREV`; save/show the exact matching id |
| 5 | NONE EXPECTED — renewal_reminder | ever | — | — | No `renews soon` naming this subscription, D0-D12 |
| 6 | NONE EXPECTED — renewal_invoice / payment_successful | ever | — | — | No `Invoice for subscription #<ID>`, no second `Payment received` |
| 7 | NONE EXPECTED — expiring_soon | ever | — | — | Nothing schedules `arraysubs_send_expiring_soon` on this build (REF-04 B1) |

## Evidence to capture
- `SLT-CHK-14-01-product.png`, `-02-cart.png`, `-03-checkout-gateways.png`, `-04-actions-empty.png`, `-05-admin-sub.png`, `-06-myaccount.png`.
- Order + subscription ID (post both to the registry so every watch day can re-assert the negative), meta dump, Mailpit IDs, console/network errors.

## Pass criteria
- [ ] Order total exactly $49.00
- [ ] Subscription active with `_billing_period=lifetime`
- [ ] `_next_payment_date` and `_end_date` empty
- [ ] No renewal/invoice/reminder action IDs on the sub
- [ ] Scheduled Actions shows nothing for this sub ID
- [ ] Admin and my-account show no recurring payment and no date/countdown
- [ ] Emails 1-4 captured; negatives 5-7 hold on the day of purchase

## Isolation / teardown
- Hands the watch canonical `SUB_LIFETIME` as its permanent negative control; that exact ID must be quoted in every daily report D1-D12.
- Nothing global changed; cart emptied; only `core-CHK14-SLT-CHK-14` closed. This negative control remains active through D12 and is cancelled/deleted by post-watch `SLT-SETUP-99B` only.

---

### Fresh-cycle validation contract

- Re-derive every ID, count, option value, gateway capability, scheduler timestamp, and email baseline on this run; no prior-cycle result is evidence.
- Create and mutate only registered `SLT2 ` / `slt2-*` fixtures. Legacy `SLT` and all non-SLT2 data are read-only controls.
- Automatic-gateway scope is Stripe and Paddle only. Stripe is the primary path; run Paddle parity wherever Paddle supports the behavior. Do not test or configure PayPal or Mollie.
- ArraySubs core must own its Stripe/Paddle integration, renewal, retry, webhook, REST, refund, and customer-payment services with Pro inactive; vendor host classes retain their expected ownership.
- Browser-required assertions use Vercel `agent-browser` with isolated task/role sessions and current snapshot refs. WP-CLI always includes `--allow-root`.
- Update the lifecycle card, the matching `qa/progress/` card, and `qa/issues/` for every new regression. Evidence belongs to this fresh cycle only.
