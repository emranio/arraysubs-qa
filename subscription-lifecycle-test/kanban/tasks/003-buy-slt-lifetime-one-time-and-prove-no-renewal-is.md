---
id: 3
title: Buy SLT Lifetime One Time and prove no renewal is ever scheduled (12-day negative control)
status: todo
priority: critical
created: 2026-08-02T03:43:03.18371421+02:00
updated: 2026-08-02T03:43:13.070896828+02:00
tags:
    - checkout
    - day-00
    - has-conflicts
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

### ⚠ Conflict resolutions that apply to this task

**`critical` · evidence-destruction / teardown vs watch window** — with `SLT-SETUP-99`, `SLT-CHK-13`, `SLT-EML-14`, `SLT-SYN-09`, `SLT-SYN-13`, `SLT-SYN-12`

- *Problem:* SLT-SETUP-99 is authored as a single d10 task that cancels AND permanently deletes every SLT subscription, order, product, coupon, page and user. With D10 = 2026-08-12 and the watch running to D12 = 2026-08-14, that deletes exactly the evidence D11 and D12 exist to collect. Events after D10: SUB_W1 + SUB_W (both week flex subs) renew 2026-08-14 00:00 site - the last scheduled events in the whole window and SYN-09's 'second charge full on the boundary' proof; the SLT-SYN-04 globally-synced day/3 subscription renews 08-14; SLT-SYN-13's Full and Next Cycle variations renew 08-13; SLT-CHK-13's Box Daily renews 08-12; SLT-CHK-14's lifetime negative control must be asserted on all 12 watch days including 08-13 and 08-14 (its own isolation note wrongly says '99A/99B'); SLT-EML-14 step 9 mandates a delta sweep on the morning of 08-14 and explicitly states 99B must not run before it, because a cancellation mail would contaminate the silence proof.
- *Required fix:* Split, as audit C06 directs, with the dates shifted +1. SLT-SETUP-99A on D10 (2026-08-12), after that morning's watch read and after SLT-DUN-05's recovery evidence is closed: Part 1 settings restore (five booleans, empty jq diff) plus cancellation of the COMPLETED-EVIDENCE COHORT ONLY - the day/1 workhorses (SLT Daily Core spine and its clones, Signup Fee Daily, Renewal Price Step, Paddle Daily, plan-ladder rungs, Free Signup Daily, Trial Four Day, Variable tiers, all CPN and CHK day/1 subs, IMP-03 concurrency subs, DUN-05's S2). No deletions. SLT-SETUP-99B on 2026-08-15 (Sat), strictly after the D12 watch report and SLT-EML-14's 08-14 delta are written: cancel the TAIL COHORT (both week flex subs, Sync Global Daily, SYN-13's two variation subs, SYN-12's two probes, SYN-14's qty sub, Box Daily, the lifetime controls, the flex month subs) then Parts 2-4 deletion. Correct SLT-CHK-14's and SLT-CHK-13's isolation notes to name 99B only. Publish the two cohort lists to the registry on D9 so the watcher can assert on D11/D12 that every 99A-cancelled subscription shows no renewal after its cancellation timestamp.

**`high` · session/cart collision (persistent cart)** — with `SLT-CHK-01`, `SLT-LIFE-04`, `SLT-CHK-11`, `SLT-CHK-13`, `SLT-MYA-02`, `SLT-ADM-02`

- *Problem:* Audit C09's fix - one named agent-browser session per task - isolates GUEST carts only. WooCommerce persists a logged-in customer's cart to user meta (_woocommerce_persistent_cart_<blog_id>) and restores it into any session that authenticates as that user. Several tasks therefore share a cart despite having distinct session names: on D0 slt-core is used concurrently by SLT-CHK-01 (cust-SLT-CHK-01), SLT-CHK-14 (core-CHK14) and SLT-LIFE-04 (life04); on D2 slt-trial by SLT-CHK-15 (trial-CHK15) and SLT-EML-09 (cust-SLT-EML-09); on D4/D5 slt-core by SLT-CHK-13 (core-CHK13), SLT-CHK-11 (core-CHK11), SLT-MYA-02 and SLT-ADM-02. A leftover subscription line leaking across sessions makes allow_multiple_in_cart=false reject the next add-to-cart for the wrong reason, or - worse - a two-subscription cart reaches checkout and the wrong subscription is created.
- *Required fix:* Add a standing rule to the isolation contract: never run two tasks concurrently under the same slt-* login, and serialise same-account tasks within a day (the calendar's intra-day ordering is binding, not advisory). Every task that logs in must, as its first browser action after login, assert the cart is EMPTY and treat a non-empty cart as a STOP condition with an issue filed - not as something to silently empty. Add a WP-CLI pre-flight to same-account days: `wp user meta get <uid> _woocommerce_persistent_cart_1 --allow-root` must be empty before the task's checkout, and empty again at teardown.

---
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
- Session `core-CHK14`; cart empty first and last.

## Test data
| Item | Value |
|---|---|
| Product | SLT Lifetime One Time (`slt-lifetime-one-time`) |
| Account | slt-core / `SltQa!2026#Pass` |
| Card | 4242 4242 4242 4242 |
| Today | **$49.00** once; renewals: none, ever |

## Steps
1. `PREV=$(mailpit-agent latest-id)`.
2. `agent-browser --session core-CHK14 open "https://mirror-help.arrayhash.com/my-account/"` -> log in `slt-core`.
3. Open `/slt-lifetime-one-time` -> `snapshot -i`. Record the price/summary text verbatim; no "every N days" phrasing.
4. **Add to cart** -> `/cart/` -> `snapshot -i`: total $49.00, no recurring schedule line, no signup fee.
5. `/checkout/` -> `snapshot -i`. Record which gateways the accordion offers (lifetime is never sync-eligible, so Paddle must appear beside Stripe). Pay Stripe 4242 -> **Place Order**.
6. Record order + subscription ID. `mailpit-agent wait-new "$PREV" 180 "is active"`.
7. `wp post meta list <SUB_ID> --keys=_product_id,_billing_period,_billing_interval,_recurring_amount,_next_payment_date,_end_date,_renewal_action_id,_renewal_invoice_action_id,_renewal_reminder_action_id --allow-root`.
8. Tools -> Scheduled Actions: search the sub ID across Pending and Complete, and in groups `arraysubs-renewals`/`-billing`/`-emails`; screenshot the empty result.
9. Open the sub in wp-admin (`edit.php?post_type=arraysubs_data`); screenshot the details panel — next payment must render empty/none, not a date.
10. Open the `/my-account/` subscription view -> screenshot; record whether any next-payment row is shown.
11. Empty cart; `close --session core-CHK14`.
12. Standing watch D1-D12: this sub must keep an empty `_next_payment_date`, zero scheduled actions, zero renewal orders, zero renewal mail. Any change is a critical bug.

## Expected results
1. Order total exactly **$49.00**, status `processing`/`completed`, no tax line, one line item.
2. Sub `arraysubs-active`, `_product_id`=lifetime product ID, `_billing_period=lifetime`, `_billing_interval=1`, `_recurring_amount=49.00`.
3. `_next_payment_date` is EMPTY (absent or empty string) and `_end_date` is absent/empty.
4. `_renewal_action_id`, `_renewal_invoice_action_id`, `_renewal_reminder_action_id` are all absent.
5. Scheduled Actions returns no row for this subscription ID in any state or group.
6. Admin and my-account render no next-payment date and no renewal countdown.
7. Checkout offered both Stripe and Paddle for this product (lifetime is not sync-eligible).

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription | order paid | slt-core@example.test | `is active` | `mailpit-agent wait-new "$PREV" 180 "is active"` |
| 2 | admin_new_subscription | order paid | admin | `New subscription #` | `mailpit-agent list 50` |
| 3 | NONE EXPECTED — renewal_reminder | ever | — | — | No `renews soon` naming this subscription, D0-D12 |
| 4 | NONE EXPECTED — renewal_invoice / payment_successful | ever | — | — | No `Invoice for subscription #<ID>`, no second `Payment received` |
| 5 | NONE EXPECTED — expiring_soon | ever | — | — | Nothing schedules `arraysubs_send_expiring_soon` on this build (REF-04 B1) |

## Evidence to capture
- `SLT-CHK-14-01-product.png`, `-02-cart.png`, `-03-checkout-gateways.png`, `-04-actions-empty.png`, `-05-admin-sub.png`, `-06-myaccount.png`.
- Order + subscription ID (post both to the registry so every watch day can re-assert the negative), meta dump, Mailpit IDs, console/network errors.

## Pass criteria
- [ ] Order total exactly $49.00
- [ ] Subscription active with `_billing_period=lifetime`
- [ ] `_next_payment_date` and `_end_date` empty
- [ ] No renewal/invoice/reminder action IDs on the sub
- [ ] Scheduled Actions shows nothing for this sub ID
- [ ] Admin and my-account show no next payment
- [ ] Emails 1-2 captured; negatives 3-5 hold on the day of purchase

## Isolation / teardown
- Hands the watch its permanent negative control; the sub ID must be quoted in every daily report D1-D12.
- Nothing global changed; cart emptied; only `core-CHK14` closed. Cancelled and deleted by `SLT-SETUP-99A`/`99B`.

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
