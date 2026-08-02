---
id: 37
title: SLT-PROD-02 Create SLT Free Signup Daily, the $0.00-today free-signup-then-paid product
status: todo
priority: high
created: 2026-08-02T03:43:06.084008533+02:00
updated: 2026-08-02T03:43:16.518346185+02:00
tags:
    - setup
    - products
    - day-02
    - has-conflicts
due: "2026-08-04"
estimate: 40m
depends_on:
    - 10
class: standard
---

> **SLT-PROD-02** · group `catalog` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · shared-global-setting** — with `SLT-SYN-04`, `SLT-SETUP-05`, `SLT-SETUP-02`, `SLT-PROD-12`, `SLT-PROD-13`, `SLT-PROD-14`

- *Problem:* renewals.sync_to_billing_cycle is written by two tasks on the same authored day. SLT-SETUP-02 turns it OFF as a declared window-wide baseline; SLT-SYN-04 turns it back ON (steps 3-15) and only restores it at step 16. Every other day-0 task asserts the OFF baseline while sync is ON: SLT-SETUP-05 pass criterion 'Stripe AND Paddle both offered for SLT Daily Core' is guaranteed to FAIL because maybeHideUnsupportedRenewalSyncGateways() hides arraysubs_paddle on every non-trial, non-lifetime subscription cart once the global switch is on; the guest cart previews in SLT-PROD-01/02/04/09/12/13/14/15 would read altered first-charge amounts and midnight-boundary next-payment dates; and any checkout completed inside the ON window permanently writes _renewal_sync_enabled=yes plus the five _renewal_sync_* metas onto that subscription, which cannot be undone by restoring the setting. Secondary hazard: turning sync ON re-exposes the First Charge select that SLT-SETUP-02 step 3 deliberately never touched, so a careless Save on the General page can write sync_first_charge_mode explicitly.
- *Required fix:* Make SLT-SYN-04 the sole writer of sync_to_billing_cycle and give it an exclusive, fixed bracket: run it on D3 (2026-08-04) 09:00-11:00 site time only. No other SLT task may add to cart, reach checkout, place an order, save a product, or drain Action Scheduler inside that bracket. SLT-SYN-04 must (a) capture the jq settings dump before flipping, (b) never click the First Charge select, (c) restore the switch and prove the jq diff is empty before the bracket is released, (d) post the 'bracket closed' confirmation to the registry page. Schedule SLT-SETUP-05 on D1, two days ahead of the bracket, so its two-gateway assertion runs against the true OFF baseline.

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-01`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

**`unrated` · same-account-collision** — with `SLT-SETUP-05`, `SLT-PROD-04`, `SLT-PROD-09`, `SLT-PROD-10`, `SLT-PROD-12`, `SLT-PROD-13`

- *Problem:* Ten tasks perform cart previews as `--session guest` and each one ends with 'empty the cart'. agent-browser sessions are keyed by name, so every one of these tasks shares ONE cart. Run on the same day (as authored, all on d0) they interleave: a leftover subscription line from SLT-PROD-04 makes SLT-PROD-09's probe-B multi-subscription refusal fire for the wrong reason; SLT-PROD-10's box add-to-cart explicitly EMPTIES the cart first, silently wiping another task's staged preview; SLT-SETUP-05's gateway accordion reading can be taken against a cart that still holds a flex product, which hides Paddle and produces a false failure of its own pass criterion.
- *Required fix:* Give every task its own browser session name: `--session guest-SLT-PROD-04`, `--session guest-SLT-SETUP-05`, etc. Each cart-touching task must additionally assert the cart is EMPTY as its first action and empty it again as its last action, capturing both in evidence. Close only its own session (`agent-browser close --session <name>`); reserve `agent-browser close --all` for the last task of the day.

**`unrated` · duplicate-coverage** — with `SLT-SETUP-01`, `SLT-SETUP-05`, `SLT-SYN-04`, `SLT-PROD-04`, `SLT-PROD-09`

- *Problem:* SLT-SETUP-01 builds the classic cart/checkout harness pages (slt-classic-cart, slt-classic-checkout) and binds them on every task whose Scope says 'Checkout: classic' or 'both' - but not a single authored task actually visits them. SLT-SETUP-05 uses /checkout/ (block), SLT-SYN-04's Scope says 'Checkout: block' and it uses /checkout/, and every cart preview (SLT-PROD-02/04/09/12/13/14, SLT-SYN-03) uses /cart/ (block). The 'Checkout: both' scope declarations are therefore unbacked, and two published pages are created and torn down without being exercised.
- *Required fix:* Assign the classic surface explicitly rather than declaratively: route SLT-SYN-04's purchase through /slt-classic-checkout (it is a plain Stripe purchase and is the cleanest classic candidate), route SLT-PROD-04's qty-1/qty-2 signup-fee cart probes through /slt-classic-cart (fee rendering differs between block and classic), and change every remaining 'Checkout: both' to the surface actually used. Never repoint the site's real Cart/Checkout pages - the harness pages are the only permitted classic surface.

---
## Objective
Provide the "signup free" product. A true $0 recurring SIMPLE subscription is impossible on this build — `getPostedSubscriptionProductValidationErrors()` rejects any simple subscription save whose regular price is empty or <= 0, and `validateProduct()` additionally restores the old prices — so free signup is implemented the supported way: a priced product with a short trial, which `beforeCalculateTotals()` zeroes at checkout, giving $0.00 due today and a first real charge when the trial converts. The $0-recurring branch is probed separately on a VARIATION in SLT-PROD-08, where no such validation runs.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete.
- Baseline `trials.require_payment_method = true` is NOT changed by this window, so checkout still requires a card even though the total is $0.00 (`TrialCheckoutTrait::maybeSkipPaymentForTrial()` returns the setting value).
- Trial length 2 days is deliberate: `emails.trial_ending.days_before = 3` is LONGER than the trial, so the trial-ending reminder has no valid send window — that negative is the point of this product and is contrasted with SLT-PROD-03.

## Test data
| Item | Value |
|---|---|
| Product | SLT Free Signup Daily / slug `slt-free-signup-daily` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $8.00; expected charge today $0.00; expected first paid charge $8.00 at trial end; renewal $8.00/day |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Free Signup Daily`. **Description**: `SLT window product. Free signup via 2-day trial, then paid. Delete on 2026-08-11.`
4. Type **Simple product**; tick **Virtual**.
5. Tick **Subscription [ArraySubs]**.
6. **General** tab: **Regular price ($)** = `8.00`, Sale price empty.
7. **Subscription [ArraySubs]** tab: **Billing Period** = `Day`; **Billing Interval** = `1`; **Subscription Length** = `0`; **Trial Length** = `2`; **Trial Period** = `Day`; **Sign-up Fee ($)** = EMPTY (a signup fee would force payment and break the $0.00-today premise — `maybeSkipPaymentForTrial()` returns true unconditionally when `cartHasSignupFee()`); **Different Renewal Price** = unticked.
8. Confirm the **Flexible Renewal Sync to Next Billing Cycle** section is HIDDEN/disabled because a trial is configured (`$arraysubs_flex_section_hidden = ... || $arraysubs_flex_trial_length > 0`). Screenshot it as the trial-exclusivity negative.
9. Slug `slt-free-signup-daily`. Publish. Reload and re-verify the fields.
10. Verify meta: `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_trial_length,_trial_period,_signup_fee,_regular_price,_arraysubs_flex_sync_enabled --allow-root`.
11. As `--session guest`, open the product page and then `https://mirror-help.arrayhash.com/cart/?add-to-cart=<ID>`; read the cart totals from the snapshot WITHOUT proceeding to payment, then empty the cart.
12. Append the ID to the registry.

## Expected results
1. Published simple + virtual + subscription, slug `slt-free-signup-daily`.
2. `_trial_length=2`, `_trial_period=day`, `_subscription_period=day`, `_subscription_interval=1`, `_regular_price=8.00`, `_signup_fee` absent or `0`.
3. `_arraysubs_flex_sync_enabled` absent AND the flex section is not offered in the UI while the trial is set.
4. In the guest cart the line total for this item is `$0.00` and the cart total is `$0.00`; the item shows the trial/first-payment summary produced by `getSubscriptionTodayChargeSummary()`.
5. No `Subscription Signup Fee` fee row appears in the cart.
6. Cart emptied at the end; no order created.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish and cart preview | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-02-01-subscription-tab.png`, `SLT-PROD-02-02-flex-hidden-by-trial.png`, `SLT-PROD-02-03-cart-zero-total.png`.
- Product ID; meta list output; the exact cart total string.

## Pass criteria
- [ ] Published with trial 2 day and price 8.00
- [ ] Flex sync section hidden by the trial (exclusivity negative captured)
- [ ] Guest cart total is exactly $0.00 with no signup-fee row
- [ ] Metas exactly as listed
- [ ] Zero mail, cart left empty

## Isolation / teardown
- State handoff: buy this ONLY as `slt-trial`. It is the free-signup / $0-due-today path and also the negative case for the trial-ending reminder (2-day trial vs 3-day reminder lead time). Its trial converts on 2026-08-03 (start + 2 days), inside the window, so the trial-converted email and first paid charge are observable without time travel.
- Restores: cart emptied. Product deleted by SLT-SETUP-99.

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
