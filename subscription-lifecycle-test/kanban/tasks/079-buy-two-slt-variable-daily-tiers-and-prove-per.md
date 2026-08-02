---
id: 79
title: Buy two SLT Variable Daily tiers and prove per-variation config lands on the subscription
status: todo
priority: high
created: 2026-08-02T03:43:09.833133473+02:00
updated: 2026-08-02T03:43:20.709400932+02:00
tags:
    - checkout
    - day-05
    - has-conflicts
due: "2026-08-07"
estimate: 2h
depends_on:
    - 71
    - 10
    - 11
    - 12
class: standard
---

> **SLT-CHK-11** · group `checkout` · scheduled **D05** (2026-08-07)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`high` · session/cart collision (persistent cart)** — with `SLT-CHK-01`, `SLT-CHK-14`, `SLT-LIFE-04`, `SLT-CHK-13`, `SLT-MYA-02`, `SLT-ADM-02`

- *Problem:* Audit C09's fix - one named agent-browser session per task - isolates GUEST carts only. WooCommerce persists a logged-in customer's cart to user meta (_woocommerce_persistent_cart_<blog_id>) and restores it into any session that authenticates as that user. Several tasks therefore share a cart despite having distinct session names: on D0 slt-core is used concurrently by SLT-CHK-01 (cust-SLT-CHK-01), SLT-CHK-14 (core-CHK14) and SLT-LIFE-04 (life04); on D2 slt-trial by SLT-CHK-15 (trial-CHK15) and SLT-EML-09 (cust-SLT-EML-09); on D4/D5 slt-core by SLT-CHK-13 (core-CHK13), SLT-CHK-11 (core-CHK11), SLT-MYA-02 and SLT-ADM-02. A leftover subscription line leaking across sessions makes allow_multiple_in_cart=false reject the next add-to-cart for the wrong reason, or - worse - a two-subscription cart reaches checkout and the wrong subscription is created.
- *Required fix:* Add a standing rule to the isolation contract: never run two tasks concurrently under the same slt-* login, and serialise same-account tasks within a day (the calendar's intra-day ordering is binding, not advisory). Every task that logs in must, as its first browser action after login, assert the cart is EMPTY and treat a non-empty cart as a STOP condition with an issue filed - not as something to silently empty. Add a WP-CLI pre-flight to same-account days: `wp user meta get <uid> _woocommerce_persistent_cart_1 --allow-root` must be empty before the task's checkout, and empty again at teardown.

---
## Objective
Buy two `SLT Variable Daily` tiers — Starter (day/1, $6.00) on block checkout, Plus (day/2, $11.00 + $4.00 signup fee) on the classic harness page — and prove each variation's period, interval, price and fee land verbatim on its own subscription. Trialist and Zero Probe get cart previews only.

## Scope
- Gateway: Stripe test
- Checkout: both
- Account: existing
- Plugins: free-only

## Preconditions
- `SLT-PROD-08` complete; quote the four variation IDs from `slt-catalog-registry`.
- `SLT-SETUP-01` (classic harness pages), `SLT-SETUP-02`, `SLT-SETUP-03` (`slt-core` + billing address).
- `one_per_customer=false`, so auto-migrate (`CartValidationTrait.php:140-148`) is unreachable: two tiers of one parent give two independent subs.
- Session `core-CHK11`; cart empty first and last.

## Test data
| Item | Value |
|---|---|
| Product | SLT Variable Daily (`slt-variable-daily`), attr `SLT Tier` |
| Account | slt-core / `SltQa!2026#Pass` |
| Card | 4242 4242 4242 4242 |
| Starter | $6.00 today; day/1; next payment 2026-08-08 |
| Plus | $11.00 + fee $4.00 = **$15.00** today; day/2; next payment 2026-08-09; renewal $11.00, no fee |
| Trialist / Zero Probe | preview only |

## Steps
1. `PREV1=$(mailpit-agent latest-id)`.
2. `agent-browser --session core-CHK11 open "https://mirror-help.arrayhash.com/my-account/"` -> `snapshot -i` -> log in as `slt-core`.
3. Open `/slt-variable-daily` -> `snapshot -i`; select each `SLT Tier` value in turn and screenshot the price + subscription summary.
4. Add **Trialist** -> `/cart/` -> snapshot total -> remove. Repeat for **Zero Probe**, recording verbatim what a $0 recurring line does (added / refused / notice).
5. Select **Starter** -> **Add to cart** -> `/checkout/` -> `snapshot -i`; confirm $6.00, no fee row; pay Stripe 4242 -> **Place Order**. Record order + subscription ID.
6. Empty cart, add **Plus**, open `/slt-classic-cart` -> `snapshot -i` (fee row + $15.00), then `/slt-classic-checkout` -> pay 4242 -> **Place Order**.
7. Both subs: `wp post meta list <SUB_ID> --keys=_product_id,_variation_id,_billing_period,_billing_interval,_recurring_amount,_signup_fee,_trial_length,_next_payment_date,_renewal_action_id --allow-root`.
8. Per sub compute `k = crc32('arraysubs-spread-'.SUBID) % 21600` (php -r); derive invoice `due+k−6h`, charge `due+k`.
9. wp-admin -> Tools -> Scheduled Actions (Pending): screenshot the `arraysubs_generate_renewal_invoice` + `arraysubs_process_renewal` rows for both IDs; compare with step 8.
10. Empty cart; `agent-browser close --session core-CHK11`.
11. Watch: 08-08 Starter renews $6.00; 08-09 Plus renews $11.00 **with no fee line**.

## Expected results
1. Two orders `processing`/`completed` at $6.00 and $15.00.
2. Starter sub: `_variation_id`=Starter ID, `_product_id`=parent ID, `_billing_period=day`, `_billing_interval=1`, `_recurring_amount=6.00`, `_signup_fee` empty/0, `_trial_length=0`, `arraysubs-active`, `_next_payment_date` 2026-08-08 at checkout clock time.
3. Plus sub: `_billing_interval=2`, `_recurring_amount=11.00`, `_signup_fee=4.00`, `_next_payment_date` 2026-08-09.
4. The two `_variation_id` values differ; neither sub inherited the other's config.
5. Four distinct front-end summaries: $6.00/day; $11.00/2 days + $4.00 fee; $9.00/day after a 3-day trial; Zero Probe.
6. Trialist cart total $0.00; Zero Probe behaviour recorded.
7. Both legs pending at the step-8 timestamps, not at the bare `_next_payment_date`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | new_subscription | Starter paid | slt-core@example.test | `is active` | `mailpit-agent wait-new "$PREV1" 180 "is active"` |
| 2 | admin_new_subscription | Starter paid | admin | `New subscription #` | `mailpit-agent list 50` |
| 3 | new_subscription + admin_new_subscription | Plus paid | slt-core / admin | `is active` / `New subscription #` | `mailpit-agent list 50` |
| 4 | NONE EXPECTED | step 4 previews | — | — | latest-id unchanged over step 4 |

## Evidence to capture
- `SLT-CHK-11-01..04-tier-<name>.png`, `-05-trialist-cart.png`, `-06-zero-probe-cart.png`, `-07-block-starter.png`, `-08-classic-cart-fee.png`, `-09-scheduled-actions.png`.
- Order/subscription/variation IDs, meta dumps, offsets, Mailpit IDs, console+network errors from block checkout and the Stripe UPE iframe.

## Pass criteria
- [ ] Orders placed at exactly $6.00 and $15.00
- [ ] Per-variation period, interval, price, fee land on the matching sub
- [ ] `_variation_id` correct and distinct on both
- [ ] Fee charged once on Plus, absent on Starter
- [ ] Trialist preview $0.00; Zero Probe recorded
- [ ] Renewal legs at the offset-adjusted times
- [ ] Emails 1-3 captured; negative 4 holds

## Isolation / teardown
- Two live subs to the watch (Starter daily from 08-08, Plus every 2 days from 08-09); cancelled by `SLT-SETUP-99A` on D10.
- Nothing global changed; cart emptied; only `core-CHK11` closed. Trialist left unpurchased.

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
