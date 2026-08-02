---
id: 59
title: SLT-PROD-10 Create SLT Box Daily (pro Subscription Box) plus its three eligible children
status: todo
priority: high
created: 2026-08-02T03:43:08.133330777+02:00
updated: 2026-08-02T03:43:18.551979027+02:00
tags:
    - setup
    - products
    - day-03
    - has-conflicts
due: "2026-08-05"
estimate: 1h 30m
depends_on:
    - 10
class: standard
---

> **SLT-PROD-10** · group `catalog` · scheduled **D03** (2026-08-05)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

### ⚠ Conflict resolutions that apply to this task

**`unrated` · impossible-timing** — with `SLT-SETUP-01`, `SLT-SETUP-02`, `SLT-SETUP-03`, `SLT-SETUP-04`, `SLT-SETUP-05`, `SLT-PROD-01`

- *Problem:* 25 of the 26 authored tasks are stamped day 0. Sixteen of them are multi-step browser-driven product builds (each: open post-new, set 6-15 fields across 3 tabs, publish, reload, verify, meta-dump, guest front-end check, registry append), two are long audit tasks with 15-20 probe steps each (SLT-SYN-01, SLT-SYN-02), one is a live Stripe purchase with a settings flip and restore (SLT-SYN-04), and on top of that D0 is also where the plan's isolation notes demand the D0 purchases of SLT-PROD-01/02/03/06/07/10/12/13/14/15/16. There is a hard serial chain inside the day as well: SETUP-01 -> SETUP-02 -> PROD-16 -> SETUP-05 -> SYN-04, and SETUP-01 -> SETUP-02 -> PROD-14 -> SYN-03 -> SYN-04. This is not executable in one day, and it wastes D4-D9 which sit empty.
- *Required fix:* Adopt the rebalanced D0-D10 calendar in schedule_notes. Only products with a genuinely date-bound purchase stay on D0 (SLT-PROD-01, 06, 07, 13 plus the three foundation tasks); everything else is spread D1-D5 with its purchase deadline recomputed so it still fits the window. Peak day drops from 25 tasks (~30h) to 7 tasks (~7h) and the median day is ~4.5h, inside the 1.6x band.

**`unrated` · impossible-timing** — with `SLT-SETUP-99`, `SLT-PROD-14`, `SLT-SYN-04`, `SLT-PROD-15`

- *Problem:* SLT-SETUP-99 is scheduled on d10 (2026-08-11) and cancels + permanently deletes every SLT subscription, order, product and user, but the automated renewal watch runs to D12 (2026-08-13) and several subscriptions have renewals due after D10: SLT Flex Daily Two Seg and SLT Flex Daily Next Cycle renew 2026-08-11, the SLT Flex Variable Daily Full/Next Cycle variations renew 2026-08-12, the SLT-SYN-04 globally-synced day/3 subscription renews 2026-08-13, and SLT Box Daily renews 2026-08-11. Any dunning ladder started on D8-D10 also cancels at +3 days, i.e. 2026-08-11..08-13. Deleting on D10 destroys exactly the tail evidence D11 and D12 exist to collect. The task's own precondition notices the clash and then leaves it to the operator.
- *Required fix:* Split SLT-SETUP-99 into two tasks. SLT-SETUP-99A (D10, 2026-08-11): Part 1 settings restore + jq diff, plus cancel ONLY the subscriptions whose evidence is complete (all day/1 workhorses: SLT Daily Core, SLT Signup Fee Daily, SLT Renewal Price Step, SLT Paddle Daily, the plan-ladder rungs, SLT Free Signup Daily, SLT Trial Four Day, SLT Variable Daily tiers) so D11/D12 are not polluted by daily-renewal noise. SLT-SETUP-99B (2026-08-13, after the D12 watch check has been captured): Parts 2-4, cancel the remaining tail cohort and delete all artifacts. Settings restore is safe on D10 because it only affects NEW subscriptions.

**`unrated` · same-account-collision** — with `SLT-SETUP-05`, `SLT-PROD-02`, `SLT-PROD-04`, `SLT-PROD-09`, `SLT-PROD-12`, `SLT-PROD-13`

- *Problem:* Ten tasks perform cart previews as `--session guest` and each one ends with 'empty the cart'. agent-browser sessions are keyed by name, so every one of these tasks shares ONE cart. Run on the same day (as authored, all on d0) they interleave: a leftover subscription line from SLT-PROD-04 makes SLT-PROD-09's probe-B multi-subscription refusal fire for the wrong reason; SLT-PROD-10's box add-to-cart explicitly EMPTIES the cart first, silently wiping another task's staged preview; SLT-SETUP-05's gateway accordion reading can be taken against a cart that still holds a flex product, which hides Paddle and produces a false failure of its own pass criterion.
- *Required fix:* Give every task its own browser session name: `--session guest-SLT-PROD-04`, `--session guest-SLT-SETUP-05`, etc. Each cart-touching task must additionally assert the cart is EMPTY as its first action and empty it again as its last action, capturing both in evidence. Close only its own session (`agent-browser close --session <name>`); reserve `agent-browser close --all` for the last task of the day.

**`high` · dependency-inversion (product creation after first consumer)** — with `SLT-PROD-04`, `SLT-PROD-05`, `SLT-PROD-08`, `SLT-PROD-09`, `SLT-PROD-11`, `SLT-PROD-15`

- *Problem:* The corrected calendar in plan-audit places several catalog tasks later than the first new-index task that depends on them. SLT-SETUP-04 (coupons) is D3 but SLT-CPN-01/02 need it on D1 18:00-19:00. SLT-PROD-05 is D3 but SLT-LIFE-05 buys it on D1. SLT-PROD-16 is D1 but SLT-DUN-01 (corrected to D2 13:00) and SLT-CHK-04 (D2) need it, and SLT-MYA-05 needs it on D2 morning. SLT-PROD-09 is D5 but SLT-CPN-04 (D3) and SLT-CHK-12 (D5) depend on it. SLT-PROD-10 and SLT-PROD-11 are D4 but SLT-CHK-13 (D4), SLT-CHK-10 (D5) and SLT-SW-09 (D4, which explicitly says PROD-11 must be done 'before this task starts on D4') need them earlier in the day or before. SLT-PROD-08 is D5 but SLT-CHK-11 buys its variations on D5. SLT-PROD-15 is D2 and SLT-SYN-13 buys its variations on D2 - correct only if SYN-02's audit sits strictly between them.
- *Required fix:* Adopt the rebalanced calendar in this report: SETUP-04 and PROD-05 to D1 morning; PROD-16 to D1 morning (ahead of SETUP-05, which also gains PROD-14 as a dependency per audit C03); PROD-02/03/09/15 and SYN-02 to D2 morning; PROD-04/10/11 to D3 after the SYN-04 bracket closes; PROD-08 to D4 morning. Add an explicit intra-day ordering line to every day's calendar row ('creations and audits before 12:00, purchases after 12:00') and make it a pass criterion that each consuming task quotes the creating task's registry entry.

---
## Objective
Build the pro Subscription Box end to end through the Configure Box modal, honouring the eligibility rules enforced by `BoxConfig::isEligibleChildProduct()`: children must be SIMPLE products; non-subscription children are always eligible; subscription children must match the box period AND interval exactly and must not use a different renewal price. The box itself is priced dynamically — the saver clears `_regular_price`/`_sale_price`/`_price`, sets `_sold_individually=yes`, forces `_trial_length=0` and `_signup_fee=0`, and deletes `_enable_renewal_price`.

## Scope
- Gateway: both
- Checkout: both
- Account: N/A (creation only)
- Plugins: pro-required

## Preconditions
- SLT-SETUP-01 complete. `arraysubs_has_module('SubscriptionBox') === 1` verified.
- Box schedule day/2 is chosen so the box renews twice inside the window (2026-08-03, 2026-08-05, 2026-08-07 ...) with no time travel, and so a matching day/2 subscription child is possible.

## Test data
| Item | Value |
|---|---|
| Product | SLT Box Daily / slug `slt-box-daily` (type Subscription Box [ArraySubs]) |
| Children | SLT Box Item A ($4.00, non-sub), SLT Box Item B ($6.00, non-sub), SLT Box Sub Item ($5.00, day/2 subscription) |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | box total is dynamic; a 2-item selection of A+B = $10.00 recurring every 2 days before discounts |

## Steps
1. Capture `mailpit-agent latest-id`.
2. Create `SLT Box Item A`: new product, **Simple product**, **Virtual**, do NOT tick Subscription, **Regular price ($)** `4.00`, slug `slt-box-item-a`, Publish.
3. Create `SLT Box Item B` the same way at `6.00`, slug `slt-box-item-b`.
4. Create `SLT Box Sub Item`: **Simple product**, **Virtual**, tick **Subscription [ArraySubs]**, **Regular price ($)** `5.00`, Subscription tab: **Billing Period** `Day`, **Billing Interval** `2`, **Subscription Length** `0`, **Trial Length** `0`, no signup fee, **Different Renewal Price** unticked. Slug `slt-box-sub-item`. Publish. (Interval 2 is mandatory — a mismatch makes it invisible to the box product search.)
5. New product: title `SLT Box Daily`. **Description**: `SLT window product. Pro subscription box, bills every 2 days. Delete on 2026-08-11.`
6. Set the product type dropdown to **Subscription Box [ArraySubs]**. The General tab now shows the **Subscription Box Details** panel.
7. Click **Configure Box** to open the `Configure Subscription Box` modal.
8. In **Box Schedule**: **Billing Period** = `Day`; **Billing Interval** = `2`; **Subscription Length** = `0`; leave **Keep signup fees** UNCHECKED (none of the children carry a fee, and unchecked keeps the recurring total clean).
9. Move to **Box Steps**: **Add Step**, set the step title to `Pick your items`. **Add Element** of type product and select `SLT Box Item A`; add a second product element for `SLT Box Item B`; add a third product element and search for `SLT Box Sub Item` — it must appear because its day/2 cycle matches. As a negative probe, search for `SLT Daily Core` (day/1) and `SLT Renewal Price Step` (different renewal price) and confirm neither is offered; screenshot the empty result.
10. Move to **Discounts & Freebies**: leave the discount type at `none` for the baseline box (later tasks may clone it if range pricing needs coverage).
11. Move to **Flexible Renewal Sync**: leave it DISABLED for this box. A day/2 nominal cycle is below `SegmentPlan::MIN_CYCLE_DAYS = 3`, so `syncRenewalSyncMeta()` would write a plan that `getConfig()` then rejects; keeping it off avoids a meaningless half-state.
12. **Save Configuration**, then **Publish** the product with slug `slt-box-daily`.
13. Reload and confirm the read-only summary shows `Billing: every 2 days · until cancelled`, `Signup fees: Not charged`, `Flexible renewal sync: Store default`, and 1 step / 3 elements.
14. `wp post meta list <BOX_ID> --keys=_arraysubs_subscription_box,_is_subscription,_subscription_period,_subscription_interval,_subscription_length,_trial_length,_signup_fee,_sold_individually,_regular_price,_price,_arraysubs_box_config --allow-root`.
15. As `--session guest`, open `https://mirror-help.arrayhash.com/slt-box-daily` -> `snapshot -i` -> click the box launcher button and step through the overlay wizard, selecting A x1 and B x1; read the computed total; then close the overlay WITHOUT adding to cart.
16. Append the box ID and three child IDs to the registry.

## Expected results
1. Three children published: A ($4.00) and B ($6.00) as plain simple products, Sub Item as a day/2 simple subscription at $5.00.
2. `SLT Box Daily` published with type `arraysubs_subscription_box`, `_arraysubs_subscription_box=yes`, `_is_subscription=yes`.
3. Engine meta mirrored from the modal: `_subscription_period=day`, `_subscription_interval=2`, `_subscription_length=0`; forced values `_trial_length=0`, `_signup_fee=0`, `_sold_individually=yes`; `_regular_price` and `_price` EMPTY; `_enable_renewal_price` absent.
4. `_arraysubs_box_config` holds valid JSON with one step and three elements.
5. `_arraysubs_flex_sync_enabled` is absent (sync left off in the modal).
6. The product search inside the modal offered `SLT Box Sub Item` but NOT `SLT Daily Core` (cycle mismatch) and NOT `SLT Renewal Price Step` (different renewal price).
7. The storefront wizard opens, accepts A+B and shows a recurring total of `$10.00` every 2 days; no cart item is created.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | All four product publishes and the wizard preview | — | — | `mailpit-agent latest-id` unchanged from step 1 |

## Evidence to capture
- Screenshots: `SLT-PROD-10-01-modal-schedule.png`, `SLT-PROD-10-02-modal-steps.png`, `SLT-PROD-10-03-ineligible-search-empty.png`, `SLT-PROD-10-04-admin-summary.png`, `SLT-PROD-10-05-frontend-wizard-total.png`.
- Box ID + three child IDs; full meta dump; the `_arraysubs_box_config` JSON; REST errors from `arraysubs/v1/` during the modal (network tab).

## Pass criteria
- [ ] Three eligible children published with the exact prices/cycle
- [ ] Box published with type arraysubs_subscription_box and 1 step / 3 elements
- [ ] Engine meta mirrored and forced values correct (no stored price, sold individually)
- [ ] Ineligible products absent from the modal search (both reasons)
- [ ] Wizard computes $10.00 every 2 days for A+B
- [ ] Zero mail, nothing added to cart

## Isolation / teardown
- State handoff: buy as `slt-core`. Behaviour later tasks must assume: adding a box EMPTIES the cart first; contents are added to the order at zero cost while the box line carries the whole recurring total; free trials are switched off for everything inside a box; the frozen contents live on `_arraysubs_box_contents`.
- Restores: overlay closed, cart untouched. Box and all three children deleted by SLT-SETUP-99.

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
