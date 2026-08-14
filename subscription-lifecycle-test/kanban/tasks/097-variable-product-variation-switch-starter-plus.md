---
id: 97
title: Variable-product variation switch (Starter→Plus) plus on-hold and pending-cancellation switch refusals
status: done
priority: high
created: 2026-08-02T03:43:11.150512461+02:00
updated: 2026-08-08T16:01:32.102008353+02:00
started: 2026-08-08T16:01:32.102007382+02:00
completed: 2026-08-08T16:01:32.102007382+02:00
tags:
    - plan-switching
    - day-06
due: "2026-08-08"
estimate: 2h 30m
depends_on:
    - 11
    - 12
    - 71
claimed_by: trail-storm
claimed_at: 2026-08-08T16:01:32.102008253+02:00
class: standard
---

> **SLT-SW-07** · group `switching` · scheduled **D06** (2026-08-08)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Cover the edges the ladder cannot reach: switching a **variable** subscription between variations of one parent, and switching while **on-hold** and while **pending-cancellation**. Starter ($6.00 day/1) → Plus ($11.00 day/2): sticker rises but daily rate falls (6.00 → 5.50, ±0.30 band) so it must classify **downgrade**, and the interval change forces the cycle-change branch — full new price charged, next payment re-anchored to now + 2 days.

## Scope
- Gateway: Stripe test
- Checkout: block
- Account: new registered — **this task creates `slt-switch3` / `slt-switch3@example.test`**
- Plugins: free-only

## Preconditions
- SLT-SETUP-02/03 and SLT-PROD-08 done. Sessions `admin-SLT-SW-07`, `customer-SLT-SW-07`; cart and persistent-cart meta empty first and last.
- Switch targets live on the VARIATION: `getAvailableSwitchOptions()` reads `_arraysubs_*_products` from `_variation_id` with no parent fallback.

## Test data
| Item | Value |
|---|---|
| Product | SLT Variable Daily: Starter $6.00 day/1 → Plus $11.00 day/2 (fee $4) |
| Account | slt-switch3 / `SltQa!2026#Pass` / Customer; card 4242 4242 4242 4242 |
| Amounts | signup $6.00; switch charge $11.00, credit round(6×dr,2), net max(0, 11 − credit), where dr = max(0, 1 − round((now − `_last_payment_date`)/86400, 2)) |

## Steps
1. `USER_PRE=$(mailpit-agent latest-id)`; in `admin-SLT-SW-07`, create `slt-switch3` as in SLT-SW-06 step 2. Classify exactly one admin-addressed `New User Registration` after `USER_PRE` and prove there is no customer account/password mail. Do not reuse this setup baseline for checkout.
2. In `admin-SLT-SW-07`, open SLT Variable Daily → **Variations** → **Starter** → **Subscription Plan Switching** → **Downgrade to** = **Plus** → **Save changes**; reload and verify `_arraysubs_downgrade_products` on the Starter variation.
3. In `customer-SLT-SW-07`, log in, require the browser cart and serialized persistent-cart meta to be empty, and record exact order/subscription counts. Set checkout-only `MP0=$(mailpit-agent latest-id)` immediately before adding the product. Open the product page, choose `SLT Tier` = **Starter**, add to cart, handle the frozen one-click redirect, capture the unpopulated $6.00 summary, fill 4242 without capturing populated hosted fields, and capture only the safe order-received page. Record numeric `ORDER_SW7_PARENT` from the receipt; resolve the sole numeric `SUB_ID` through its `_subscription_ids` post-meta JSON plus strict guard, require reverse parent/customer/parent-product/variation linkage and exact `+1` counts, then record `T0` (SW-06 §5 metas + `_variation_id`). Poll immutable `MP0` in repeated calls no longer than 60 seconds through the two-minute cutoff and classify the complete four-message WC/ArraySubs checkout set.
4. **Probe A.** Set `HOLD_PRE=$(mailpit-agent latest-id)`. In `admin-SLT-SW-07`, set the exact subscription **On hold** and poll the immutable baseline in calls no longer than 60 seconds through the two-minute cutoff for the on-hold mail; classify its complete owned delta. In `customer-SLT-SW-07`, reload `/my-account/view-subscription/<SUB_ID>/`, screenshot the Actions card.
5. Set `ACTIVE_PRE=$(mailpit-agent latest-id)`. In `admin-SLT-SW-07`, set it back to **Active** and poll the immutable baseline in calls no longer than 60 seconds through the two-minute cutoff for the reactivated mail; classify its complete owned delta. In the customer session confirm **Change Plan** returns and record whether `_next_payment_date` moved.
6. Set `SWITCH_PRE=$(mailpit-agent latest-id)` and exact pre-switch order count. Customer: **Change Plan** → select Plus → screenshot **Plan Change Summary** → **Confirm Plan Change** → open the exact returned `checkout_url`, record numeric `ORDER_SW7_CHANGE` from the response, capture its unpopulated gateway/total state, pay 4242 without capturing populated card fields, and capture only the safe paid receipt. Require exactly `+1` switch order, its customer/subscription/switch linkage, paid total/math, and no unrelated order; poll/classify the complete `SWITCH_PRE` delta in ≤60-second calls through the two-minute cutoff, allowing only the exact WooCommerce order mail and proving there is no ArraySubs plan-switch lifecycle mail.
7. Re-dump metas, diff vs `T0`, screenshot the page.
8. **Probe B.** Set `CANCEL_PRE=$(mailpit-agent latest-id)`. Customer: **Cancel Subscription** → reason **Just need a temporary break** → **Continue** → **Before You Go...** → **No thanks, continue to cancel**. Screenshot banner + Actions card; poll immutable `CANCEL_PRE` in calls no longer than 60 seconds through the two-minute cutoff and reconcile the exact pending-cancellation customer/admin messages.
9. Set `UNDO_PRE=$(mailpit-agent latest-id)`, click **Undo Scheduled Cancellation**, confirm **Change Plan** is back; inspect the complete bounded post-action delta and require no undo-attributable message. Dump the exact replacement invoice/charge rows, publish their IDs/gates for the watch, prove browser/persistent carts empty, close both task sessions, independently review all setup/purchase/status/switch/cancellation evidence, then move the card through `review` to `done` with Review empty. Any live defect goes only in `issues/SLT-SW-07-<concise-slug>.md` with task/stage/plan path; variation/product/user/subscription/parent/switch-order/action/message IDs; user login/email/role; exact routes/sessions/gates; reproduction; expected/actual; and UI/REST/meta/order/queue/Mailpit proof.

## Expected results
1. Starter's `_arraysubs_downgrade_products` holds exactly the Plus variation ID after the AJAX save + reload.
2. **Probe A:** while on-hold **Change Plan** is absent (server: "Plan switching is only available for active subscriptions"); no proration order; Active restores it.
3. Classified **downgrade** despite the higher sticker price (record the modal grouping and `_arraysubs_switch_type`); `ORDER_SW7_CHANGE` charges the full **$11.00**, credit round(6×dr,2), net max(0, 11 − credit), **no $4.00 fee line**, no tax line.
4. After payment `_variation_id`=Plus, `_recurring_amount=11.00`, day/2, `_next_payment_date` = switch + 2 days, legs re-queued at that date + the crc32 offset.
5. **Probe B:** stays `arraysubs-active`, `_waiting_cancellation=1`, `_cancellation_scheduled_date` = `_next_payment_date`, banner "scheduled to cancel on …", **Change Plan** gone (409). Undo clears both metas, re-queues the legs, restores it.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | full paid-checkout set | step 3 | customer, admin | active subscription, admin new subscription, WC new order, WC completed order | immutable-baseline polls ≤60 seconds through the two-minute cutoff plus complete delta |
| 2 | subscription_on_hold + subscription_reactivated | steps 4-5 | slt-switch3 | `is on hold` / `has been reactivated` | reconcile after `HOLD_PRE` / `ACTIVE_PRE` |
| 3 | pending_cancellation + admin copy | step 8 | customer, admin | `scheduled to cancel on` | immutable-baseline polls ≤60 seconds through the two-minute cutoff plus complete delta |
| 4 | WooCommerce order mail only for `ORDER_SW7_CHANGE`; NONE for undo | steps 6, 9 | customer/admin, then — | order subject; no plan-switch subject | Complete owner-filtered delta after `SWITCH_PRE`; complete delta after `UNDO_PRE` has zero undo-attributable mail, while unrelated mail is classified |
| 5 | WP New User Registration | setup before `MP0` | admin | `New User Registration` | exactly one after `USER_PRE`; zero customer account/password mail |

## Evidence to capture
- `SLT-SW-07-01-links.png`, `-02-onhold.png`, `-03-modal.png`, `-04-proration.png`, `-05-pending.png`, `-06-undo.png`; `SUB_ID`, variation IDs, order ids, `T0` diff, `USER_PRE`, setup-mail id, checkout-only `MP0`, later Mailpit ids, console errors

## Pass criteria
- [ ] Variation-level switch links save and drive the modal
- [ ] On-hold blocks switching, no proration order; Active restores it
- [ ] Classified `downgrade`; charge $11.00, credit round(6×dr,2), no fee/tax line
- [ ] After the switch: Plus, $11.00, day/2, next payment = switch + 2 days
- [ ] Pending cancellation blocks switching, undo restores it; emails 1-3 plus the switch-order mail present; no ArraySubs switch mail and no undo mail
- [ ] Setup mail isolated before `MP0`; no customer account/password mail
- [ ] Parent and switch orders/counts are exact and card-safe; sessions close and independent review reaches `done` with Review empty

## Isolation / teardown
- Ends with an active day/2 Plus subscription on slt-switch3, no pending cancellation or switch; leave it for the watch. Starter keeps its downgrade link — note it in the registry.
- Two admin status changes are made and reverted here; if hold/resume moves `_next_payment_date`, record before/after.
- Empty the cart, verify the persistent-cart meta is empty, and close only `admin-SLT-SW-07` and `customer-SLT-SW-07`.

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

[[2026-08-06]] Thu 20:37
Source-block note on 2026-08-06: prerequisite card 71 / SLT-PROD-08 closed on a product-creation failure, not on a usable published variable-product fixture. This card cannot start until SLT Variable Daily exists in a usable state with real variation IDs.

[[2026-08-08]] Sat 16:01
D6 final closeout: UNVERIFIED because the exact SLT Variable Daily fixture is unusable. Parent 13012 and intended variations 13013/13015/13017/13019 all remain trash; usable published parent count and related-subscription count are both zero; slt-switch3 does not exist and was not created late. Cache-busted storefront rendered Page not found in guest-SLT-SW-07; session closed. Evidence: /home/server-manager/slt-evidence/SLT-SW-07-D06-source-block.txt and /home/server-manager/slt-evidence/SLT-SW-07-no-source-storefront.png. Existing source finding, later resolved as a false-positive save attribution: issues/done-critical-plugin-SLT-PROD-08-variable-subscription-draft-is-trashed-on-save.md; no duplicate issue filed.
