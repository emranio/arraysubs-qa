---
id: 38
title: SLT-PROD-03 Create SLT Trial Four Day, the trial product with an in-window reminder boundary
status: done
priority: high
created: 2026-08-02T03:43:06.193016054+02:00
updated: 2026-08-05T21:37:49.444495854+02:00
started: 2026-08-03T21:29:02.696206986+02:00
completed: 2026-08-03T21:29:02.696206986+02:00
tags:
    - setup
    - products
    - day-02
due: "2026-08-04"
estimate: 30m
depends_on:
    - 10
class: standard
---

> **SLT-PROD-03** · group `catalog` · scheduled **D02** (2026-08-04)

> Read `README.md` (environment + isolation contract), `calendar.md` (this day's exact
> ordering — it is binding, not advisory) and `plan-audit.md` before starting.

## Objective
Provide the trial product whose 3-days-before reminder boundary lands inside the window. `SLT-CHK-15` buys it after 12:00 site on D2 (2026-08-04), putting the boundary on D3 (2026-08-05) and conversion on D6 (2026-08-08). The reminder action may exist, but the trial-status guard means the mail must not send; `SLT-EML-09` owns that assertion. The product also carries `trials.require_payment_method = true`, so the card must still be collected on a $0.00 order.

## Scope
- Gateway: N/A
- Checkout: N/A (creation and storefront verification only)
- Account: N/A (creation only)
- Plugins: free-only

## Preconditions
- SLT-SETUP-01 complete.
- Deliberate deviation from "e.g. 2 days": 4 days is the shortest trial that leaves a valid 3-days-before reminder window. SLT-PROD-02 keeps the 2-day case as the suppressed-reminder negative.
- Baseline `trials.require_payment_method=true` and `trials.one_trial_per_customer=false` are unchanged.

## Test data
| Item | Value |
|---|---|
| Product | SLT Trial Four Day / slug `slt-trial-four-day` |
| Account | N/A |
| Coupon | N/A |
| Card | N/A |
| Amounts | Regular price $12.00; charge today $0.00; first paid charge $12.00 at the recorded `trial_end+k` gate late 2026-08-08 or shortly after local midnight 2026-08-09; renewal $12.00/day |

## Steps
1. Capture `mailpit-agent latest-id`.
2. `agent-browser --session admin-SLT-PROD-03 open "https://mirror-help.arrayhash.com/wp-admin/post-new.php?post_type=product"` -> `snapshot -i`.
3. **Product title**: `SLT Trial Four Day`. **Description**: `SLT window product. 4-day free trial, card required. Delete on 2026-08-15.`
4. Type **Simple product**; tick **Virtual**; tick **Subscription [ArraySubs]**.
5. **General** tab: **Regular price ($)** = `12.00`.
6. **Subscription [ArraySubs]** tab: **Billing Period** = `Day`; **Billing Interval** = `1`; **Subscription Length** = `0`; **Trial Length** = `4`; **Trial Period** = `Day`; **Sign-up Fee ($)** empty; **Different Renewal Price** unticked. Capture `SLT-PROD-03-01-subscription-tab.png`.
7. Confirm again that the **Flexible Renewal Sync** section is hidden while a trial is set; capture `SLT-PROD-03-02-flex-hidden-by-trial.png`.
8. Slug `slt-trial-four-day`. Publish. Reload and re-verify.
9. `wp post meta list <ID> --keys=_is_subscription,_subscription_period,_subscription_interval,_trial_length,_trial_period,_regular_price,_signup_fee --allow-root`.
10. Before any storefront or downstream checkout access, append only this parent product ID to Shop Access rule `rule_1784662676378_maa3te08s` under `exclusion_product_ids` through **Member Access → Shop Access**. Preserve every other field and every prior exclusion; re-read the raw option and require the ID exactly once.
11. As `--session guest-SLT-PROD-03`, open `https://mirror-help.arrayhash.com/product/slt-trial-four-day/?slt-cache-bust=<timestamp>` and confirm the trial is advertised in the price/schedule summary; capture `SLT-PROD-03-03-frontend-trial-summary.png`. Do not add to cart.
12. Append the ID and verified Shop Access exclusion to the registry.

## Expected results
1. Published simple + virtual + subscription, slug `slt-trial-four-day`.
2. `_trial_length=4`, `_trial_period=day`, `_subscription_period=day`, `_subscription_interval=1`, `_regular_price=12.00`.
3. Flex sync section hidden; `_arraysubs_flex_sync_enabled` absent.
4. Parent product ID is present exactly once in the preserved Shop Access exclusion list before storefront access, and the product page shows the 4-day free trial in the subscription summary.
5. Date arithmetic to be used by the buying task: trial start = the 2026-08-04 checkout timestamp; `_trial_end_date` = start + 4 days = 2026-08-08; the reminder action gate is `trial_end−3d+k` (normally 2026-08-05, but it may cross local midnight); the invoice and charge gates are `trial_end+k−6h` and `trial_end+k`. Action presence is recorded; reminder-mail absence is the binding expectation while status is `arraysubs-trial`.

## Emails expected
| # | Email | Trigger point | Recipient | Subject contains | Verify with |
|---|---|---|---|---|---|
| 1 | NONE EXPECTED | Product publish | — | — | Complete delta after the step-1 baseline; zero task-attributable mail, while unrelated/background mail is allowed and classified |

## Evidence to capture
- Screenshots: `SLT-PROD-03-01-subscription-tab.png`, `SLT-PROD-03-02-flex-hidden-by-trial.png`, `SLT-PROD-03-03-frontend-trial-summary.png`.
- Product ID; meta list output; raw Shop Access rule showing the ID exactly once.

## Pass criteria
- [x] Published with trial 4 day and price 12.00
- [x] Flex sync hidden by trial
- [x] Front end advertises the trial
- [x] Metas exactly as listed
- [x] Parent product ID is present exactly once in the preserved Shop Access exclusion list before storefront access
- [x] Zero mail

## Isolation / teardown
- State handoff: `SLT-CHK-15` buys this exactly once as `slt-trial`, only after its `SLT Free Signup Daily` purchase has been captured, so the two trial subscriptions are distinguishable by product. The logical trial end is 2026-08-08, while activation occurs through whichever live path wins at or after the recorded `trial_end+k` gate. Downstream mail contract: trial-started is required; `SLT-EML-09` records whether activation came from the renewal path or the 02:00 site-time bulk converter and scores `trial_converted` accordingly. A trial-ending/renews-soon message is not expected.
- Restores: close only `admin-SLT-PROD-03` and `guest-SLT-PROD-03`; SLT-SETUP-99A restores the exact pre-window Shop Access rule snapshot. Product deleted by SLT-SETUP-99B.

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

## Execution verdict — 2026-08-04 — PASS

- Published parent `12380`, `SLT Trial Four Day` / `slt-trial-four-day`, as a simple virtual day/1 subscription at USD `12.00` with a four-day trial, no signup fee, no length cap, and no different renewal price.
- Reloaded UI and WP-CLI agree on day/1, trial `4`/day, `_signup_fee=0`, `_regular_price=12.00`, and absent `_arraysubs_flex_sync_enabled`. The unchecked flex form control is not visible (`offsetParent === null`), so no flex section is offered while the trial is configured.
- Through the real Member Access UI, appended only parent `12380` to `rule_1784662676378_maa3te08s`; the fresh raw option contains it exactly once and preserves all prior rule fields/exclusions.
- The real product page visibly advertises `$12.00 / day` and `4 days free trial`. The guest cart stayed at zero and product `12380` appears in zero orders.
- Mailpit stayed exactly at `6fzJg6YALlBNfbNPe6f79F`; browser errors were empty and no new finding was observed.
- Evidence: `/home/server-manager/slt-evidence/SLT-PROD-03-facts.txt` and screenshots `SLT-PROD-03-01` through `-03`.
