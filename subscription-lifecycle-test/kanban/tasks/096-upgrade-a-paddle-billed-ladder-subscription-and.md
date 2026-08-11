---
id: 96
title: Upgrade a Paddle-billed ladder subscription and prove the Paddle-side price is not updated
status: done
priority: high
created: 2026-08-02T03:43:11.086851725+02:00
updated: 2026-08-08T16:53:25.585106698+02:00
started: 2026-08-08T16:53:25.585105756+02:00
completed: 2026-08-08T16:53:25.585105756+02:00
tags:
    - plan-switching
    - day-06
due: "2026-08-08"
estimate: 1h 45m
depends_on:
    - 60
    - 26
    - 12
claimed_by: trail-storm
claimed_at: 2026-08-08T16:53:25.585106598+02:00
class: standard
---

> **SLT-SW-05** · group `switching` · scheduled **D06** (2026-08-08)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.
## Objective
Buy **SLT Plan Basic** ($5.00 day/1) as `slt-paddle` through the Paddle sandbox, upgrade it to **SLT Plan Pro** ($15.00 day/1) from the portal, and answer the question that matters for a remote-billed gateway: is the Paddle-side plan/price kept in step? Per SLT-REF-09 the switch path is gateway-blind — `Hooks::onPlanSwitchCompleted()` only re-captures payment context and logs, and nothing calls `PaddleApiClient::updateSubscription()`. Prove Paddle still bills $5.00 while ArraySubs believes $15.00.

## Scope
- Gateway: Paddle sandbox
- Checkout: block (page 8); order-pay for the proration
- Account: existing (`slt-paddle`)
- Plugins: both (Paddle is pro)

## Preconditions
- SLT-SETUP-05 done (Paddle selectable, global sync OFF); SLT-PROD-11 done. The rungs carry no flex sync, so Paddle is offered.
- `slt-paddle` is Paddle-only and has not bought this product before.

## Test data
| Item | Value |
|---|---|
| Purchase | SLT Plan Basic $5.00 day/1 via Paddle, card `4242…4242` |
| Switch | Basic $5.00 → Pro $15.00 (upgrade, Branch A) |
| Portal | `/my-account/view-subscription/<S-PADDLE>/`, session `cust-SLT-SW-05` |
| Paddle API | Capture the API key non-interactively from `woocommerce_arraysubs_paddle_settings` with WP-CLI plus `jq`, as specified in the steps; never print it, use it only for the sandbox subscription GET, and unset it at teardown |

## Steps
1. In `cust-SLT-SW-05`, log in as `slt-paddle`; require the browser cart and serialized persistent-cart user meta to be empty, record exact subscription/order counts, and capture the empty cart. Set `M0=$(mailpit-agent latest-id)` immediately before adding the product. Buy exact SLT Plan Basic on the block checkout choosing **Paddle**; capture the unpopulated checkout total before opening the overlay, never capture populated hosted payment fields, and capture only the safe return/order-received state afterward. Record numeric parent order from that receipt.
2. The order stays **pending** until `transaction.completed` arrives; poll in checks no longer than 60 seconds through a documented 10-minute cutoff until the subscription is `arraysubs-active` and `_gateway_paddle_subscription_id` is set. Resolve sole numeric PSUB from the parent order's `_subscription_ids` post-meta JSON with a strict guard, require reverse parent/customer/product linkage plus exact `+1` order/subscription counts, and never select by recency. Record both date metas, Paddle ids and the complete exact checkout-mail delta by order/subscription/recipient, then prove the browser and persistent carts empty again.
3. From the WP root capture the secret without printing it: `PADDLE_API_KEY="$(wp option get woocommerce_arraysubs_paddle_settings --format=json --allow-root | jq -r '.api_key // empty')"`; require `test -n "$PADDLE_API_KEY"`. Record only the required Paddle fields from `GET /subscriptions/PSUB`: `status`, `price.id`, `unit_price.amount`, `next_billed_at`; do not save the full response or any management URL. Dump the `_arraysubs_gateway_paddle_*` metas on **both** rungs.
4. Portal → **Change Plan** → **Upgrade/Downgrade** → **Select** SLT Plan Pro; record T1 and the preview rows (`credit=round(5r,2)`, `charge=round(15r,2)`, `net`); Confirm.
5. The response must be `requires_payment: true` + `checkout_url`; land on order-pay, record numeric PRO-ORDER-P from the exact switch response, screenshot its unpopulated gateway list, set `SW05_PAY_PRE=$(mailpit-agent latest-id)`, and pay through Paddle without capturing populated hosted fields. **If Paddle cannot render or settle there, that is the finding**: preserve the exact response/order/console/network/API evidence, create the standalone issue, mark the remote-renewal branch `UNVERIFIED (switch payment unavailable)`, restore no data because nothing global changed, close the exact sessions, independently review the available branch, and move through `review` to `done`. Never manually mark the order Completed or edit gateway/subscription metas to manufacture a source subscription.
6. Re-dump subscription + exact order metas; require PRO-ORDER-P's customer/switch linkage, read the notes and the pro payment log line "Payment context updated after plan switch"; re-run the step-3 field selection and diff those fields. Poll immutable `SW05_PAY_PRE` in repeated calls no longer than 60 seconds through the 10-minute settlement cutoff, inspect its complete delta, allow only WooCommerce mail linked to PRO-ORDER-P, and require no lifecycle/plan-switch mail. From the sanitized Paddle response record the exact `next_billed_at`, publish its `next_billed_at−300s` deadline, then `unset PADDLE_API_KEY`. Close `cust-SLT-SW-05`; do not retain it across days.
7. At the final watch phase inside exact `[next_billed_at−300s, next_billed_at)`, publish `SW05_PADDLE_RENEW_PRE=$(mailpit-agent latest-id)` and that timestamp to the registry. After the remote charge settles (D7 late gate or D8 morning), poll the immutable baseline in repeated calls no longer than 60 seconds through the 10-minute cutoff, inspect the complete delta, record the amount Paddle charged, and require/show any exact payment-success message for PSUB. Resolve any renewal order from the exact Paddle transaction/PSUB relationship and require its reverse link; never use recency. Re-read `_next_payment_date` and the sanitized remote price/transaction fields. In fresh `admin-SLT-SW-05-R1` capture the exact order/subscription evidence, close it, independently review all available phases, create `issues/SLT-SW-05-paddle-price-not-updated-after-plan-switch.md` when the live `$5.00` remote vs `$15.00` local divergence is confirmed, then move the card through `review` to `done` with Review empty. The issue must include task/stage/plan path; products, PSUB, parent/proration/renewal order, action/transaction/message IDs; user ID/login/email/role; exact routes/sessions/gates; reproduction; expected/actual; sanitized API/meta/order/Mailpit proof; and no secret or full card number.

## Expected results
1. Local records switch exactly as on Stripe: `_product_id`=Pro, `_recurring_amount`=`15.00`, day/1 kept, title rewritten, `_arraysubs_switch_processed=yes`, one `type=upgrade` history entry, `_next_payment_date` unchanged. `_gateway_paddle_subscription_id` still equals PSUB — the switch neither cancels nor recreates the remote subscription.
2. **The Paddle side is unchanged**: same `price.id`, `unit_price.amount` ($5.00) and `next_billed_at` — no switch path calls `updateSubscription()`. This is the headline result (an expected negative, not a crash).
3. SLT Plan Pro gets Paddle metas only if the proration checkout triggered `ensureProductSynced()`; record whether `_arraysubs_gateway_paddle_product_id` appeared — a synced *product* is not a *price* change.
4. For the watch: the next `transaction.completed` bills **$5.00** while ArraySubs shows $15.00, and `syncNextPaymentDate()` overwrites `_next_payment_date` from `next_billed_at` without rescheduling the AS legs. `processRenewalPayment()` stays a no-op — no local charge, no retries.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | Purchase/activation mail for the initial order | webhook confirms | slt-paddle@example.test | `Order #` / activated | record ids after M0 |
| 2 | NONE from the switch | steps 4-5 | — | — | no `arraysubs_send_plan_switch_email` listener |
| 3 | WooCommerce order mail for PRO-ORDER-P | order paid | slt-paddle@example.test | `Order #<PRO-ORDER-P>` | complete delta after `SW05_PAY_PRE` |
| 4 | payment_successful (if emitted by the remote renewal path) | exact Paddle `next_billed_at` settlement | slt-paddle@example.test | exact switched subscription id | complete delta after `SW05_PADDLE_RENEW_PRE`; record presence/absence against live order state |

## Evidence to capture
- `SLT-SW-05-01-checkout.png`, `-02-preview.png`, `-03-order-pay.png`, `-04-after.png`; PSUB; both sanitized Paddle field summaries; all metas; `SW05_PAY_PRE`, `SW05_PADDLE_RENEW_PRE`, exact Mailpit ids; before/after cart proof; console errors.

## Pass criteria
- [ ] Paddle purchase reaches `arraysubs-active` with `_gateway_paddle_subscription_id` set
- [ ] Preview and proration order match the Branch-A formula, as on Stripe
- [ ] Local records switch to Pro at $15.00; `_next_payment_date` unchanged
- [ ] Paddle API diff documented; the $5.00-vs-$15.00 divergence filed as an issue
- [ ] No switch email; only the listed order mails; exact remote-renewal delta reconciled
- [ ] No manual order/meta completion fallback; all available phases close exact sessions and independent review reaches `done` with Review empty

## Isolation / teardown
- `slt-paddle` now owns this ladder subscription as well as SLT Paddle Daily; never touch it with Stripe. SLT-SETUP-99A must cancel it on D10 so the Paddle subscription is closed, not orphaned.

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

## D06 execution note — 2026-08-08 evening

FAIL on the authored switch-payment branch. The initial isolated Paddle purchase succeeded: completed parent order `13343` (`$5.00`) resolved strictly to sole active subscription `13344` for user `352` / product `12608`; sanitized remote state was active at `500 USD`, price `pri_01kzgv8etdnw9y2b7jba5fgz93`, with `next_billed_at=2026-08-09T14:30:08.245157Z`. The complete `M0=5b8qiXy0QkUsv3k1hfOqzc` delta was the exact four expected activation/order messages (`2q4IFNTlyufy9dvxsS00SW`, `1W7WSRxBTamT20qybEjhS8`, `2CmzIX5G9Ynh1FjSBiWXu4`, `6FvTzIMPjYmvIokCrDGCkQ`).

The portal preview displayed `$5.00` credit, `$15.00` new-plan charge, and `$10.00` due, while its exact generated proration order `13354` was pending for `$9.90`. On its order-pay route, selected Paddle returned the exact alert `Paddle checkout setup failed: No valid items found for Paddle checkout.` The two bounded same-order POST attempts returned HTTP `200` without any Paddle sandbox `transaction-checkout` request. Product `12611` still had no Paddle catalog meta, order `13354` had no transaction, and `SW05_PAY_PRE=6FvTzIMPjYmvIokCrDGCkQ` remained Mailpit latest. Subscription `13344` stayed active Basic at `$5.00`, unprocessed, with unchanged local/remote date and price. No manual completion/meta fallback was used; both carts were empty and `cust-SLT-SW-05` was closed.

Per step 5, the remote-renewal branch is `UNVERIFIED (switch payment unavailable)` and the execution card closes after independent review rather than remaining future-gated. Full proof: `/home/server-manager/slt-evidence/SLT-SW-05-D06-execution.txt`; findings: `issues/critical-plugin-SLT-SW-05-paddle-order-pay-no-valid-items.md` and `issues/light-plugin-SLT-SW-05-preview-total-disagrees-with-order-pay.md`.

## D09 watch-only recurrence note — 2026-08-11 evening

Card remains reviewed/done; no setup or failed switch leg was repeated. PASS for the natural
unswitched Basic `$5.00` charge, exact completed relationship order `13758`, one-day schedule
advance, exact admin/customer mail pair, and canceled/unattempted local action `16852`.
FAIL for recurring persistence/logging defects; existing issue records updated only:
`issues/light-plugin-SLT-SW-05-paddle-renewal-leaves-subscription-last-transaction-stale.md`,
`issues/light-plugin-SLT-MYA-03-paddle-renewal-order-omits-transaction-meta-and-line-item.md`,
`issues/critical-plugin-SLT-ADM-06-renewal-orders-missing-arraysubs-subscription-id.md`, and
`issues/light-plugin-SLT-IMP-05-paddle-routine-webhooks-logged-as-unhandled-warnings.md`. Full proof:
`/home/server-manager/slt-evidence/SLT-SW-05-D09-natural-basic-renewal.txt`.
